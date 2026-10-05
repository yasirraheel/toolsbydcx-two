<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ExtensionPairing;
use App\Models\GoogleFlowAccount;
use App\Services\FlowAccess;
use Illuminate\Http\Request;

class FlowExtensionController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Flow Extension';
        $account = $request->user()->flow_account;
        $pairings = ExtensionPairing::where('user_id', $request->user()->id)->where('is_active', true)->where('expires_at', '>', now())->latest()->get();
        return view('templates.basic.user.flow_extension', compact('pageTitle', 'account', 'pairings'));
    }

    public function pair(Request $request)
    {
        $request->validate(['codeChallenge' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/']]);
        $pairing = FlowAccess::issue($request->user(), $request->input('codeChallenge'));
        return response()->json(['code' => $pairing->pairing_code, 'expiresAt' => $pairing->expires_at->toIso8601String()])->header('Cache-Control', 'no-store, private');
    }

    public function revoke(Request $request)
    {
        FlowAccess::revoke(ExtensionPairing::where('user_id', $request->user()->id));
        return back()->withNotify([['success', 'Extension connections revoked.']]);
    }
}
