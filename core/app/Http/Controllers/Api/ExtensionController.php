<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AccountListing;
use App\Models\SocialMedia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function __construct()
    {
        if (!headers_sent()) {
            @header("Access-Control-Allow-Origin: *");
            @header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
            @header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        }
    }

    /**
     * Helper to compute user validity text identical to web header
     */
    private function getUserValidity(User $user)
    {
        $expiryDate = $user->expires_at ?: ($user->created_at ? $user->created_at->addDays(30) : now()->addDays(30));
        $isExpired = $user->expires_at ? now()->greaterThanOrEqualTo($expiryDate) : false;
        if (!$user->expires_at && !$user->is_trial) {
            $isExpired = now()->greaterThanOrEqualTo($expiryDate);
        }

        $validityText = '';
        if ($user->is_trial) {
            if ($user->pending_trial_minutes > 0) {
                $validityText = 'Trial: Pending Start';
                $isExpired = false;
            } else {
                $diff = now()->diff(Carbon::parse($expiryDate));
                if ($isExpired) {
                    $validityText = 'Trial Expired';
                } elseif ($diff->days > 0) {
                    $validityText = 'Trial: ' . $diff->days . ' Days';
                } elseif ($diff->h > 0) {
                    $validityText = 'Trial: ' . $diff->h . ' Hours';
                } else {
                    $validityText = 'Trial: ' . $diff->i . ' Mins';
                }
            }
        } else {
            $daysRemaining = $isExpired ? 0 : (int) now()->startOfDay()->diffInDays(Carbon::parse($expiryDate)->startOfDay(), false);
            $validityText = $isExpired ? 'Expired' : ($daysRemaining . ' Days Remaining');
        }

        return [
            'is_expired'    => $isExpired,
            'validity_text' => $validityText,
            'expires_at'    => $expiryDate ? Carbon::parse($expiryDate)->toDateTimeString() : null,
        ];
    }

    /**
     * Return the account listings that this extension user is allowed to use.
     *
     * Every user receives only the concrete account_ids assigned to them.
     * Plan users are assigned one account per selected platform when the plan
     * is created/assigned; no plan-level pool is exposed to the client.
     */
    private function accessibleAccounts(User $user, bool $isAdmin = false)
    {
        $query = AccountListing::with('socialMedia')
            ->where('status', Status::LISTING_ACTIVE)
            ->whereHas('socialMedia', function ($q) {
                $q->active();
            });

        if ($isAdmin) {
            return $query;
        }

        $directIds = collect((array) ($user->account_ids ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($directIds)) {
            return $query->whereIn('id', $directIds);
        }

        // No plan and no direct assignment means no extension account access.
        return $query->whereRaw('1 = 0');
    }

    /**
     * Mobile App Login endpoint
     */
    public function mobileLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        if (!auth()->attempt([$loginType => $request->username, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username/email or password',
            ], 401);
        }

        $user = auth()->user();

        if ($user->status == Status::USER_BAN) {
            auth()->logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been banned by administrator.',
            ], 403);
        }

        $validity = $this->getUserValidity($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user'    => [
                'id'            => $user->id,
                'name'          => $user->fullname,
                'username'      => $user->username,
                'email'         => $user->email,
                'is_expired'    => $validity['is_expired'],
                'validity_text' => $validity['validity_text'],
                'expires_at'    => $validity['expires_at'],
            ],
        ]);
    }

    /**
     * Get only the platforms backed by explicit account assignments.
     */
    public function platforms(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'status'  => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validity = $this->getUserValidity($user);


        if (empty($user->account_ids)) {
            return response()->json([
                'success'       => true,
                'platforms'     => [],
                'validity_text' => $validity['validity_text'],
                'is_expired'    => $validity['is_expired'],
                'message'       => 'No platform accounts assigned. Contact admin.',
            ]);
        }

        if ($validity['is_expired']) {
            return response()->json([
                'success'       => true,
                'platforms'     => [],
                'validity_text' => $validity['validity_text'],
                'is_expired'    => true,
                'message'       => 'Your subscription is expired. Please contact administrator.',
            ]);
        }

        $isAdmin = auth()->guard('admin')->check()
            || session()->get('is_admin_testing') === true;

        // The account_ids allow-list is the complete server-side entitlement.
        $assignedAccounts = $this->accessibleAccounts($user, $isAdmin)->get();

        $platformCounters = [];
        $accountsList = $assignedAccounts;
        $countsByPlatform = $accountsList->groupBy('social_media_id')->map->count();

        $accounts = $accountsList
            ->map(function ($acc) use (&$platformCounters, $countsByPlatform) {
                $pId = $acc->socialMedia->id;
                $platformCounters[$pId] = ($platformCounters[$pId] ?? 0) + 1;
                $idx = $platformCounters[$pId];
                $totalCount = $countsByPlatform[$pId] ?? 1;

                $displayName = $totalCount > 1 
                    ? ($acc->socialMedia->name . ' ' . $idx) 
                    : $acc->socialMedia->name;

                $targetUrl = $acc->socialMedia->url;
                $targetDomain = $acc->socialMedia->domain;
                if (stripos($acc->socialMedia->name, 'flow') !== false || stripos($targetUrl, 'labs.google') !== false) {
                    $targetUrl = 'https://flow.google.com/';
                    $targetDomain = 'flow.google.com';
                }

                return [
                    'id'           => $acc->socialMedia->id,
                    'account_id'   => $acc->id,
                    'name'         => $acc->socialMedia->name,
                    'title'        => $displayName,
                    'display_name' => $displayName,
                    'url'          => $targetUrl,
                    'domain'       => $targetDomain,
                ];
            })
            ->values();

        return response()->json([
            'success'       => true,
            'platforms'     => $accounts,
            'validity_text' => $validity['validity_text'],
            'is_expired'    => $validity['is_expired'],
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache')
          ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }


    /**
     * Get cookies for a specific platform (by social_media_id)
     * The extension calls this when user clicks "Access" on a platform
     */
    public function getCookies(Request $request, $platformId, $accountId = null)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $isAdmin = auth()->guard('admin')->check()
            || session()->get('is_admin_testing') === true;


        if (!$isAdmin && empty($user->account_ids)) {
            return response()->json(['success' => false, 'message' => 'No plan accounts or direct accounts assigned'], 403);
        }
        
        $validity = $this->getUserValidity($user);
        if (!$isAdmin && $validity['is_expired']) {
            return response()->json(['success' => false, 'message' => 'Your subscription is expired. Please contact administrator.'], 403);
        }

        $targetAccountId = $accountId ?: $request->account_id;

        $query = $this->accessibleAccounts($user, $isAdmin);
        if ($targetAccountId) {
            $query->whereKey((int) $targetAccountId);
        } else {
            $query->where('social_media_id', (int) $platformId);
        }

        $account = $query->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => $targetAccountId
                    ? 'This account is not assigned to your user.'
                    : 'No account available for this platform.',
            ], $targetAccountId ? 403 : 404);
        }

        $cookies = $account->account_info;
        if (is_string($cookies)) {
            $cookies = json_decode($cookies, true);
        }

        $targetUrl = $account->socialMedia->url;
        $targetDomain = $account->socialMedia->domain;
        if (stripos($account->socialMedia->name, 'flow') !== false || stripos($targetUrl, 'labs.google') !== false) {
            $targetUrl = 'https://flow.google.com/';
            $targetDomain = 'flow.google.com';
        }

        return response()->json([
            'success'  => true,
            'platform' => [
                'name'   => $account->socialMedia->name,
                'url'    => $targetUrl,
                'domain' => $targetDomain,
            ],
            'cookies'  => $cookies ?? [],
        ]);
    }

    /**
     * Get logged in user info + plan
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('plan');
        $validity = $this->getUserValidity($user);

        $planData = null;
        if (!$validity['is_expired']) {
            if ($user->plan) {
                $planData = [
                    'id'   => $user->plan->id,
                    'name' => $user->plan->name,
                ];
            } elseif (!empty($user->account_ids)) {
                $planData = [
                    'id'   => 0,
                    'name' => 'Direct Access',
                ];
            }
        }

        return response()->json([
            'success'          => true,
            'required_version' => gs('min_extension_version') ?: '1.9.6',
            'force_update'     => (bool) gs('force_extension_update'),
            'download_url'     => getExtensionDownloadUrl(),
            'user'             => [
                'id'            => $user->id,
                'name'          => $user->fullname,
                'username'      => $user->username,
                'email'         => $user->email,
                'validity_text' => $validity['validity_text'],
                'is_expired'    => $validity['is_expired'],
                'plan'          => $planData,
            ],
        ]);
    }

    /**
     * Safe uninstall landing page for the Social extension.
     *
     * This endpoint must never redirect to a platform logout URL: doing that
     * would sign out every account sharing this account across multiple users.
     * The local browser session is wiped via content script watchdog and rolling
     * short-lived cookie clamp without any upstream platform logout calls.
     */
    public function uninstall(Request $request)
    {
        return redirect()->to('/user/dashboard')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Public extension version check endpoint
     */
    public function version()
    {
        return response()->json([
            'success'          => true,
            'required_version' => gs('min_extension_version') ?: '1.9.6',
            'force_update'     => (bool) gs('force_extension_update'),
            'download_url'     => getExtensionDownloadUrl(),
        ]);
    }
}
