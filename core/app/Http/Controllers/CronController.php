<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CronJob;
use App\Lib\CurlRequest;
use App\Constants\Status;
use App\Models\CronJobLog;
use App\Models\Transaction;
use App\Models\AccountListing;
use App\Models\BiddingListing;

class CronController extends Controller
{
    public function cron()
    {
        $general            = gs();
        $general->last_cron = now();
        $general->save();

        $crons = CronJob::with('schedule');

        if (request()->alias) {
            $crons->where('alias', request()->alias);
        } else {
            $crons->where('next_run', '<', now())->where('is_running', Status::YES);
        }
        $crons = $crons->get();
        foreach ($crons as $cron) {
            $cronLog              = new CronJobLog();
            $cronLog->cron_job_id = $cron->id;
            $cronLog->start_at    = now();
            if ($cron->is_default) {
                $controller = new $cron->action[0];
                try {
                    $method = $cron->action[1];
                    $controller->$method();
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            } else {
                try {
                    CurlRequest::curlContent($cron->url);
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            }
            $cron->last_run = now();
            $cron->next_run = now()->addSeconds($cron->schedule->interval);
            $cron->save();

            $cronLog->end_at = $cron->last_run;

            $startTime         = Carbon::parse($cronLog->start_at);
            $endTime           = Carbon::parse($cronLog->end_at);
            $diffInSeconds     = $startTime->diffInSeconds($endTime);
            $cronLog->duration = $diffInSeconds;
            $cronLog->save();
        }
        if (request()->target == 'all') {
            $notify[] = ['success', 'Cron executed successfully'];
            return back()->withNotify($notify);
        }
        if (request()->alias) {
            $notify[] = ['success', keyToTitle(request()->alias) . ' executed successfully'];
            return back()->withNotify($notify);
        }
    }


    public function auctionResult()
    {
        try {
            $accountListings = AccountListing::active()->pricingModelAuction()->where('auction_deadline', '<', today())->get();

            foreach ($accountListings as $accountListing) {

                $biddingWin    = BiddingListing::where('account_listing_id', $accountListing->id)->orderBy('amount', 'desc')->first();
                if (!$biddingWin) continue;
                $biddingLosses = BiddingListing::where('account_listing_id', $accountListing->id)->where('id', '!=', $biddingWin->id)->get();

                // For win
                if ($biddingWin) {
                    $accountList            = AccountListing::find($accountListing->id);
                    $accountList->buyer_id  = $biddingWin->user_id;
                    $accountList->buy_price = $biddingWin->amount;
                    $accountList->status    = Status::LISTING_SOLD;
                    $accountList->save();

                    if (userNotifyPermission($accountList->buyer, 'buy')) {
                        notify($biddingWin->user, 'ACCOUNT_BUYING', [
                            'title'         => $accountListing->title,
                            'buy_price'     => showAmount($biddingWin->amount,currencyFormat:false),
                            'pricing_model' => $accountListing->pricing_model == Status::AUCTION ? 'Auction' : 'Fixed',
                        ]);
                    }

                    $sellerUser           = User::find($biddingWin->accountListing->user_id);
                    $sellerUser->balance += $biddingWin->amount;
                    $sellerUser->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $sellerUser->id;
                    $transaction->amount       = $biddingWin->amount;
                    $transaction->post_balance = $sellerUser->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '+';
                    $transaction->details      = 'Account Sale';
                    $transaction->trx          = getTrx();
                    $transaction->remark       = 'account_sell';
                    $transaction->save();

                    $totalCharge = gs('fixed_charge') + ((gs('percentage_charge') / 100) * $biddingWin->amount);

                    $sellerUser->balance -= $totalCharge;
                    $sellerUser->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $sellerUser->id;
                    $transaction->amount       = $totalCharge;
                    $transaction->post_balance = $sellerUser->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '-';
                    $transaction->details      = 'Charge For Sale Account';
                    $transaction->trx          = getTrx();
                    $transaction->remark       = 'seller_fee';
                    $transaction->save();

                    if (userNotifyPermission($accountList->user, 'sell')) {
                        notify($sellerUser, 'ACCOUNT_SELLING', [
                            'title'         => $accountListing->title,
                            'sell_price'    => showAmount($accountListing->sell_price,currencyFormat:false),
                            'seller_fee'        => showAmount($totalCharge,currencyFormat:false),
                            'pricing_model' => $accountListing->pricing_model == Status::AUCTION ? 'Auction' : 'Fixed',
                        ]);
                    }
                }

                // For Loss
                foreach ($biddingLosses as $biddingLoss) {
                    $lossUser           = User::find($biddingLoss->user_id);
                    $lossUser->balance += $biddingLoss->amount;
                    $lossUser->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $lossUser->id;
                    $transaction->amount       = $biddingLoss->amount;
                    $transaction->post_balance = $lossUser->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '+';
                    $transaction->details      = 'Refund for unsuccessful bids ' . gs('cur_sym') . showAmount($biddingLoss->amount);
                    $transaction->trx          = getTrx();
                    $transaction->remark       = 'bid_refund';
                    $transaction->save();

                    if (userNotifyPermission($lossUser, 'refund')) {
                        notify($biddingLoss->user, 'BID_REFUND', [
                            'title'         => $accountListing->title,
                            'refund_amount' => showAmount($biddingLoss->amount,currencyFormat:false),
                            'pricing_model' => $accountListing->pricing_model == Status::AUCTION ? 'Auction' : 'Fixed',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore if auction table structure varies
        }
    }

    /**
     * Cron Job: Check Cookie Health for 1 account per scheduled run
     * Rate-limited to check 1 account per run to avoid Google / platform IP bans.
     */
    public function cookieCheck()
    {
        // Fetch ALL active accounts across active platforms to check whenever the cron schedule triggers
        $accounts = AccountListing::where('status', Status::LISTING_ACTIVE)
            ->whereHas('socialMedia', function ($q) {
                $q->active();
            })
            ->with('socialMedia')
            ->get();

        if ($accounts->isEmpty()) {
            if (request()->target == 'all' || request()->alias || request()->ajax()) {
                $notify[] = ['info', 'All accounts checked within the last 60 seconds.'];
                return back()->withNotify($notify);
            }
            return response()->json(['success' => true, 'message' => 'All accounts checked within the last 60 seconds.']);
        }

        $checkedCount = 0;
        $platformsToRebalance = [];
        $expiredAccountsNotified = [];

        foreach ($accounts as $acc) {
            // If the account is actively managed via Admin Extension live sync, SKIP cron cURL verification completely!
            // Fresh cookies are delivered directly by the admin's trusted browser, so no server cURL checking is needed.
            if ($acc->last_sync_source === 'admin_extension' || !empty($acc->last_synced_at)) {
                $acc->cookie_status = 1;
                $acc->cookie_check_error = null;
                $acc->save();
                $checkedCount++;
                continue;
            }

            $prevStatus = $acc->cookie_status;
            $result = $this->verifyAccountCookieHealth($acc);

            // Handle network timeouts gracefully without flipping cookie status falsely
            if (!$result['valid'] && !empty($result['is_network_error'])) {
                $acc->cookie_check_error = $result['error'];
                $acc->cookie_checked_at = now();
                $acc->save();
                $checkedCount++;
                continue;
            }

            $newStatus = $result['valid'] ? 1 : 0;

            $acc->cookie_status = $newStatus;
            $acc->cookie_check_error = $result['error'] ?: null;
            $acc->cookie_checked_at = now();

            if ($result['valid'] && !empty($result['account_name'])) {
                $dupMatch = \App\Http\Controllers\Admin\AccountListingController::checkDuplicateName($result['account_name'], $acc->social_media_id, $acc->id);
                if ($dupMatch) {
                    $acc->cookie_status = 0;
                    $acc->cookie_check_error = "Duplicate account: Verified name '{$dupMatch->title}' belongs to account ID {$dupMatch->id}.";
                    $acc->save();
                    $checkedCount++;
                    continue;
                }
                $acc->title = $result['account_name'];
            }

            $acc->save();

            if ($prevStatus !== $newStatus) {
                $platformsToRebalance[$acc->social_media_id] = true;
            }

            if ($newStatus === 0 && $prevStatus !== 0) {
                $expiredAccountsNotified[] = [
                    'account' => $acc,
                    'error' => $result['error'] ?: 'Cookie verification failed'
                ];
            }

            $checkedCount++;
        }

        // Use 'keep_manual' mode so users already on a working valid account are KEPT on their current account!
        // This prevents logging active users out during background cron checks!
        foreach (array_keys($platformsToRebalance) as $pId) {
            \App\Http\Controllers\Admin\SocialMediaController::executeLoadBalance($pId, 'keep_manual');
        }

        foreach ($expiredAccountsNotified as $item) {
            \App\Lib\WhatsappNotification::sendCookieExpiryNotification($item['account'], $item['error']);
        }

        $msg = "Checked cookies for {$checkedCount} active account(s).";

        if (request()->target == 'all' || request()->alias || request()->ajax()) {
            $notify[] = ['success', $msg];
            return back()->withNotify($notify);
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'checked_count' => $checkedCount
        ]);
    }

    /**
     * Helper to verify cookie health for an AccountListing
     * Performs a 100% live HTTP scan request without relying on local expiration timestamps.
     */
    public function verifyAccountCookieHealth($account)
    {
        $rawInfo = $account->account_info;
        if (is_string($rawInfo)) {
            $rawInfo = json_decode($rawInfo, true);
        }

        if (empty($rawInfo)) {
            return ['valid' => false, 'error' => 'No cookie data configured'];
        }

        $platformName = strtolower($account->socialMedia->name ?? '');
        $accountTitle = strtolower($account->title ?? '');
        $isGoogleFlow = str_contains($platformName, 'google') || str_contains($accountTitle, 'flow');

        // Convert array/object of cookies into standard header string
        // We DO NOT check local expiration dates; we scan live.
        $cookieHeaderParts = [];

        if (is_array($rawInfo)) {
            foreach ($rawInfo as $item) {
                $item = (array) $item;
                $name = $item['name'] ?? $item['key'] ?? null;
                $val  = $item['value'] ?? $item['val'] ?? null;
                $domain = strtolower($item['domain'] ?? '');

                if ($name && $val !== null) {
                    if ($isGoogleFlow) {
                        // Avoid HTTP 431 Request Header Fields Too Large and CookieMismatch errors
                        // Only include cookies meant for Google Flow / Google Root Auth
                        $isRelevantDomain = empty($domain) 
                            || $domain === '.google.com' 
                            || $domain === 'google.com' 
                            || str_contains($domain, 'flow.google.com') 
                            || str_contains($domain, 'labs.google')
                            || str_contains($domain, 'accounts.google.com');

                        if (!$isRelevantDomain) {
                            continue; // Skip adsense, docs, mail, wallet, etc.
                        }

                        // Skip bulky analytics trackers
                        if (str_starts_with($name, '_ga') || str_starts_with($name, '__utm') || $name === 'NID') {
                            continue;
                        }
                    }

                    $cookieHeaderParts[] = "$name=$val";
                }
            }
        }

        if (empty($cookieHeaderParts)) {
            return ['valid' => false, 'error' => 'Invalid cookie structure'];
        }

        $cookieHeaderString = implode('; ', $cookieHeaderParts);



        if ($isGoogleFlow) {
            // Direct inspection of flow.google.com homepage (new official URL)
            $ch = curl_init('https://flow.google.com/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Cookie: ' . $cookieHeaderString,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ]);

            $htmlResp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                return ['valid' => false, 'error' => 'Network error connecting to Google Flow: ' . $curlErr, 'is_network_error' => true];
            }

            // Redirected to login or unauthenticated landing
            if (str_contains($effectiveUrl, 'accounts.google.com') || str_contains($effectiveUrl, 'ServiceLogin') || str_contains($effectiveUrl, 'signin')) {
                return ['valid' => false, 'error' => 'Session expired (Redirected to Google Login)'];
            }

            if (str_contains($effectiveUrl, 'flow.google.com/about') && !str_contains($effectiveUrl, 'pli=1')) {
                return ['valid' => false, 'error' => 'Session expired (Redirected to Flow About page)'];
            }

            // If page contains flow app config or AiSandboxAngularFrontend
            $isFlowApp = str_contains($htmlResp, 'AiSandboxAngularFrontend') || str_contains($htmlResp, 'ppConfig') || str_contains($htmlResp, 'Google Flow');

            // Look for user email in page response
            $extractedName = null;
            if (preg_match('/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+)/', $htmlResp, $emailMatch)) {
                $userEmail = trim($emailMatch[1]);
                if (!str_ends_with($userEmail, '@google.com') || str_contains($htmlResp, '"oPEP7c"')) {
                    $extractedName = $userEmail;
                }
            }

            if ($isFlowApp && $httpCode === 200) {
                return ['valid' => true, 'error' => null, 'account_name' => $extractedName ?: $account->title];
            }


            // Optional fallback: Legacy NextAuth session check (for accounts still on labs.google)
            $chLegacy = curl_init('https://labs.google/fx/api/auth/session');
            curl_setopt($chLegacy, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chLegacy, CURLOPT_TIMEOUT, 6);
            curl_setopt($chLegacy, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chLegacy, CURLOPT_HTTPHEADER, [
                'Cookie: ' . $cookieHeaderString,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: application/json, text/html, */*'
            ]);
            $legacyResp = curl_exec($chLegacy);
            curl_close($chLegacy);

            $json = json_decode($legacyResp, true);
            if (is_array($json) && !empty($json['user'])) {
                $name = trim($json['user']['name'] ?? $json['user']['email'] ?? '');
                return ['valid' => true, 'error' => null, 'account_name' => $name];
            }

            return ['valid' => false, 'error' => 'Session expired (Unauthenticated on Google Flow)'];
        }

        $targetUrl = $account->socialMedia->url ?? 'https://flow.google.com/';
        $isChatGPT = str_contains($platformName, 'chatgpt') || str_contains($platformName, 'openai') || str_contains($accountTitle, 'chatgpt') || str_contains(strtolower($targetUrl), 'chatgpt.com') || str_contains(strtolower($targetUrl), 'openai.com');

        if ($isChatGPT) {
            $hasSessionToken = false;
            $extractedEmail = null;
            $isExpired = false;

            if (is_array($rawInfo)) {
                foreach ($rawInfo as $c) {
                    $c = (array) $c;
                    $cName = strtolower($c['name'] ?? $c['key'] ?? '');
                    $cVal = $c['value'] ?? $c['val'] ?? '';
                    $cExp = $c['expirationDate'] ?? $c['expires'] ?? null;

                    if ($cExp && is_numeric($cExp) && $cExp < time()) {
                        $isExpired = true;
                    }

                    if (str_contains($cName, 'session-token') || str_contains($cName, 'next-auth.session-token') || $cName === '__secure-next-auth.session-token') {
                        if (!empty($cVal)) {
                            $hasSessionToken = true;
                            // Attempt to parse JWT payload if readable
                            $parts = explode('.', $cVal);
                            if (count($parts) >= 2) {
                                $decodedPayload = @json_decode(@base64_decode($parts[1]), true);
                                if (is_array($decodedPayload)) {
                                    $extractedEmail = $decodedPayload['email'] ?? $decodedPayload['user']['email'] ?? $decodedPayload['name'] ?? null;
                                    if (!empty($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
                                        $isExpired = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!$hasSessionToken && !str_contains($cookieHeaderString, 'session-token') && !str_contains($cookieHeaderString, 'next-auth')) {
                return ['valid' => false, 'error' => 'No ChatGPT session token (__Secure-next-auth.session-token) found in cookies'];
            }

            if ($isExpired) {
                return ['valid' => false, 'error' => 'ChatGPT session token has expired'];
            }

            // Attempt session API call with modern Chrome headers
            $ch = curl_init('https://chatgpt.com/api/auth/session');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Cookie: ' . $cookieHeaderString,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: application/json',
                'sec-ch-ua: "Chromium";v="124", "Google Chrome";v="124"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && !empty($resp)) {
                $sessionData = @json_decode($resp, true);
                if (is_array($sessionData) && (!empty($sessionData['user']) || !empty($sessionData['accessToken']))) {
                    $email = $sessionData['user']['email'] ?? $sessionData['user']['name'] ?? $extractedEmail;
                    return ['valid' => true, 'error' => null, 'account_name' => $email ?: $account->title];
                }
            }

            // Cloudflare datacenter IP block returns 403 on server, but valid session token exists in cookie payload
            if ($hasSessionToken) {
                return ['valid' => true, 'error' => null, 'account_name' => $extractedEmail ?: $account->title];
            }

            return ['valid' => false, 'error' => 'Invalid ChatGPT session cookies'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Cookie: ' . $cookieHeaderString,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: application/json, text/html, */*',
            'Accept-Language: en-US,en;q=0.9',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'valid' => false,
                'error' => 'Network error: ' . $curlErr,
                'is_network_error' => true
            ];
        }

        // For other platforms, check HTTP status & redirect URL
        if (str_contains($effectiveUrl, 'accounts.google.com') || str_contains($effectiveUrl, 'ServiceLogin') || str_contains($effectiveUrl, 'signin') || str_contains($effectiveUrl, 'login')) {
            return ['valid' => false, 'error' => 'Session expired (Redirected to login page)'];
        }

        if (str_contains($response, 'Sign in') || str_contains($response, 'ServiceLogin') || str_contains($response, 'identifierInterface')) {
            return ['valid' => false, 'error' => 'Session expired (Login form detected)'];
        }

        if ($httpCode === 403 || $httpCode === 429) {
            // Check if Cloudflare / WAF blocked datacenter IP
            $hasAuthToken = false;
            $sessionKeys = ['session', 'token', 'auth', 'user', 'sid', 'login', 'phpsessid', 'jsessionid', 'c_user'];
            if (is_array($rawInfo)) {
                foreach ($rawInfo as $item) {
                    $item = (array) $item;
                    $name = strtolower($item['name'] ?? $item['key'] ?? '');
                    $val = $item['value'] ?? $item['val'] ?? '';
                    foreach ($sessionKeys as $sk) {
                        if (str_contains($name, $sk) && !empty($val)) {
                            $hasAuthToken = true;
                            break 2;
                        }
                    }
                }
            }
            if ($hasAuthToken) {
                return ['valid' => true, 'error' => null, 'account_name' => $account->title];
            }
        }

        if ($httpCode >= 400) {
            return ['valid' => false, 'error' => "HTTP Error Code $httpCode"];
        }

        $extractedName = null;
        $json = json_decode($response, true);
        if (is_array($json)) {
            $extractedName = $json['user']['name'] 
                ?? $json['user']['email'] 
                ?? $json['name'] 
                ?? $json['email'] 
                ?? $json['username'] 
                ?? null;
        }

        return ['valid' => true, 'error' => null, 'account_name' => is_string($extractedName) ? trim($extractedName) : null];
    }
}


