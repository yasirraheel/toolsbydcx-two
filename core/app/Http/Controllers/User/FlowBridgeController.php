<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ExtensionPairing;
use App\Models\GoogleFlowAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FlowBridgeController
 *
 * Called by the ToolsByDcx website JavaScript (injected by site-bridge.js) to
 * support automatic extension pairing without any manual code entry.
 *
 * Flow:
 * 1. Website JS receives ping from extension site-bridge
 * 2. Site-bridge calls SITE_AUTO_STATUS on background → background calls siteOnboarding
 * 3. siteOnboarding checks if paired; if not, generates a codeChallenge and sends
 *    SITE_PAIR request to website via window.postMessage
 * 4. Website JS calls POST /user/flow/pair-challenge with the codeChallenge
 * 5. We create a pairing record with the challenge and return a short-lived code
 * 6. Website JS sends code back to site-bridge → background calls /api/dcx-flow/pair
 *    with the code + codeVerifier (PKCE proof)
 */
class FlowBridgeController extends Controller
{
    /**
     * GET /user/flow/status
     * Returns the current logged-in user's flow eligibility for the website JS.
     * Called by the website page script to respond to extension's SITE_STATUS ping.
     */
    public function status(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['state' => 'login_required']);
        }
        // Check plan eligibility
        $eligible = $user->exp_date && now()->lessThanOrEqualTo($user->exp_date);
        if (!$eligible) {
            return response()->json(['state' => 'ineligible']);
        }
        // Check assigned Google account
        $account = GoogleFlowAccount::where('assigned_to_user_id', $user->id)
            ->where('status', 'active')->first();
        if (!$account) {
            return response()->json(['state' => 'ineligible']);
        }
        return response()->json([
            'state'  => 'ready',
            'userId' => $user->id,
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * POST /user/flow/pair-challenge
     * Creates or refreshes a pairing record for the logged-in user.
     * Returns a short-lived code that the extension will present to /api/dcx-flow/pair.
     *
     * Body: { codeChallenge: "<base64url SHA-256 of extension verifier>" }
     */
    public function pairChallenge(Request $request)
    {
        $request->validate([
            'codeChallenge' => 'required|string|min:43|max:128',
        ]);

        $user = auth()->user();

        // Verify the user has an active plan and assigned account
        $account = GoogleFlowAccount::where('assigned_to_user_id', $user->id)
            ->where('status', 'active')->first();
        abort_unless($account, 403, 'No active Google account is assigned to your profile.');

        $eligible = $user->exp_date && now()->lessThanOrEqualTo($user->exp_date);
        abort_unless($eligible, 403, 'Your plan has expired. Renew to use ToolsByDcx Flow.');

        return DB::transaction(function () use ($request, $user, $account) {
            // Revoke any existing unpaired (code still set) pairing records
            ExtensionPairing::where('user_id', $user->id)
                ->whereNotNull('pairing_code')
                ->delete();

            // Create a fresh pairing record with the challenge
            $code = Str::upper(Str::random(24)); // 24-char random code, upper-cased
            $pairing = ExtensionPairing::create([
                'user_id'                => $user->id,
                'google_flow_account_id' => $account->id,
                'pairing_code'           => $code,
                'code_challenge'         => $request->codeChallenge,
                'expires_at'             => now()->addMinutes(2), // short-lived
                'is_active'              => true,
            ]);

            return response()->json([
                'state'  => 'ready',
                'code'   => $code,
                'userId' => $user->id,
            ])->header('Cache-Control', 'no-store, private');
        });
    }
}
