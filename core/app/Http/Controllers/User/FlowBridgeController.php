<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\GoogleFlowAccount;
use App\Services\FlowAccess;
use Illuminate\Http\Request;

class FlowBridgeController extends Controller
{
    /**
     * GET /user/flow/status or /flow/status
     * Returns the current logged-in user's flow eligibility for the website JS bridge.
     */
    public function status(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['state' => 'login_required']);
        }

        // Use FlowAccess::eligible() which validates status, email/sms verification, and unexpired plan
        if (!FlowAccess::eligible($user)) {
            return response()->json([
                'state'   => 'ineligible',
                'message' => 'An active, verified user plan is required.',
            ]);
        }

        // Verify that an active Google Flow account is assigned to this user
        $account = $user->flow_account;

        if (!$account) {
            return response()->json([
                'state'   => 'ineligible',
                'message' => 'No active Google Flow account assigned.',
            ]);
        }

        return response()->json([
            'state'  => 'ready',
            'userId' => $user->id,
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * POST /user/flow/pair-challenge or /flow/pair-challenge
     * Creates a pairing record with the PKCE challenge from the extension.
     */
    public function pairChallenge(Request $request)
    {
        $request->validate([
            'codeChallenge' => 'required|string|min:43|max:128',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['state' => 'login_required'], 401);
        }

        $userAgent = $request->header('User-Agent', '');
        $secChUa = $request->header('sec-ch-ua', '');
        $isEdge = str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'Edge/') || str_contains($userAgent, 'EdgA/') || str_contains($userAgent, 'EdgiOS/') || stripos($secChUa, 'Microsoft Edge') !== false;
        $isKiwi = stripos($userAgent, 'Kiwi') !== false || stripos($secChUa, 'Kiwi') !== false || $request->cookie('is_kiwi') === '1' || session()->get('is_kiwi') === true;

        if (!$isEdge && !$isKiwi) {
            return response()->json([
                'state'   => 'unsupported_browser',
                'message' => 'ToolsByDcx Flow is exclusively supported on Microsoft Edge and Kiwi Browser.',
            ], 403);
        }

        try {
            // FlowAccess::issue generates the code, binds the challenge, and revokes pending unpaired codes
            $pairing = FlowAccess::issue($user, $request->codeChallenge);

            return response()->json([
                'state'  => 'ready',
                'code'   => $pairing->pairing_code,
                'userId' => $user->id,
            ])->header('Cache-Control', 'no-store, private');
        } catch (\Exception $e) {
            return response()->json([
                'state'   => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
