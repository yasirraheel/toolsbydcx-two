<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ExtensionHistory;
use App\Models\ExtensionPairing;
use App\Services\FlowAccess;
use Illuminate\Http\Request;

class ExtensionsController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Browser Extensions';
        $user = $request->user();

        // 1. ToolsByDcx Social Extension (Cookie-Based Accounts)
        $socialHistory = ExtensionHistory::where('type', 'social')->where('is_current', true)->first()
                      ?: ExtensionHistory::where('type', 'social')->orderByDesc('id')->first();
        $socialVersion = $socialHistory ? $socialHistory->version : '2.3.3';
        $socialDownloadUrl = route('extension.download', ['filename' => 'toolsbydcx-ext.zip']);
        $socialStorage = storage_path('app/public/extension');
        $socialExists = file_exists($socialStorage . '/toolsbydcx-ext.zip')
                     || ($socialHistory && file_exists($socialStorage . '/' . $socialHistory->filename))
                     || file_exists(public_path('toolsbydcx-ext.zip'))
                     || count(glob($socialStorage . '/toolsbydcx-ext*.zip') ?: []) > 0;

        // 2. ToolsByDcx Flow Extension (Google Flow Workspace)
        $flowHistory = ExtensionHistory::where('type', 'flow')->where('is_current', true)->first()
                    ?: ExtensionHistory::where('type', 'flow')->orderByDesc('id')->first();
        $flowVersion = $flowHistory ? $flowHistory->version : '1.0.3';
        $flowDownloadUrl = route('extension.download', ['filename' => 'toolsbydcx-flow.zip']);
        $flowExists = file_exists(public_path('toolsbydcx-extension.zip'))
                   || ($flowHistory && file_exists($socialStorage . '/' . $flowHistory->filename))
                   || file_exists($socialStorage . '/toolsbydcx-flow.zip')
                   || count(glob($socialStorage . '/toolsbydcx-flow*.zip') ?: []) > 0;

        // Assigned Google Flow account & pairings
        $flowAccount = $user->flow_account;
        $pairings = ExtensionPairing::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->get();
        $isExtensionPaired = $pairings->isNotEmpty();

        return view('templates.basic.user.extensions', compact(
            'pageTitle',
            'socialVersion',
            'socialDownloadUrl',
            'socialExists',
            'flowVersion',
            'flowDownloadUrl',
            'flowExists',
            'flowAccount',
            'pairings',
            'isExtensionPaired'
        ));
    }

    public function pair(Request $request)
    {
        $request->validate(['codeChallenge' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/']]);
        $pairing = FlowAccess::issue($request->user(), $request->input('codeChallenge'));
        return response()->json([
            'code' => $pairing->pairing_code,
            'expiresAt' => $pairing->expires_at->toIso8601String()
        ])->header('Cache-Control', 'no-store, private');
    }

    public function revoke(Request $request)
    {
        FlowAccess::revoke(ExtensionPairing::where('user_id', $request->user()->id));
        return back()->withNotify([['success', 'Extension connections revoked successfully.']]);
    }
}
