<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlowGuardAccess;
use App\Models\FlowGuardSession;
use App\Models\User;
use Illuminate\Http\Request;

class FlowGuardController extends Controller
{
    public function index()
    {
        $pageTitle = 'Flow Guard Access';
        $users = User::query()
            ->leftJoin('flow_guard_access as fga', 'fga.user_id', '=', 'users.id')
            ->select('users.id', 'users.username', 'users.email', 'users.status', 'fga.enabled', 'fga.credit_limit', 'fga.credits_used', 'fga.generation_cost', 'fga.allowed_model', 'fga.expires_at')
            ->latest('users.id')->paginate(getPaginate());
        return view('admin.flow_guard.index', compact('pageTitle', 'users'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'credit_limit' => 'nullable|integer|min:0|max:1000000000',
            'generation_cost' => 'required|integer|min:1|max:1000000',
            'allowed_model' => 'nullable|string|max:191',
            'expires_at' => 'nullable|date',
        ]);

        $user = User::findOrFail($id);
        $access = FlowGuardAccess::updateOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => $request->boolean('enabled'),
                'credit_limit' => $data['credit_limit'] ?? null,
                'generation_cost' => (int) $data['generation_cost'],
                'allowed_model' => filled($data['allowed_model'] ?? null) ? trim((string) $data['allowed_model']) : null,
                'expires_at' => $data['expires_at'] ?? null,
            ]
        );

        if (!$access->enabled) {
            FlowGuardSession::where('user_id', $user->id)->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'updated_at' => now()]);
        }

        return back()->withNotify([['success', 'Flow Guard access updated.']]);
    }

    public function revokeSessions(int $id)
    {
        FlowGuardSession::where('user_id', $id)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);
        return back()->withNotify([['success', 'Flow Guard sessions revoked.']]);
    }
}
