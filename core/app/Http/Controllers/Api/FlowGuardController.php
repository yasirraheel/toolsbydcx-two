<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowGuardProject;
use App\Services\FlowGuardAccessService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FlowGuardController extends Controller
{
    public function __construct(private readonly FlowGuardAccessService $access)
    {
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:190',
            'password' => 'required|string|max:255',
            'installationId' => 'nullable|string|max:128',
        ]);

        $user = User::where(function ($query) use ($data) {
            $query->where('email', $data['username'])->orWhere('username', $data['username']);
        })->first();

        abort_unless($user && Hash::check($data['password'], $user->password), 401, 'Invalid username or password.');
        [$session, $token] = $this->access->issue($user, $request, $data['installationId'] ?? null);

        return response()->json([
            'token' => $token,
            'expires_at' => $session->expires_at->toIso8601String(),
            'status' => $this->access->payload($session),
        ]);
    }

    public function status(Request $request)
    {
        $session = $this->access->authenticate($request);
        return response()->json(['status' => $this->access->payload($session)]);
    }

    public function logout(Request $request)
    {
        $this->access->revoke($this->access->authenticate($request));
        return response()->json(['ok' => true]);
    }

    public function reserve(Request $request)
    {
        $session = $this->access->authenticate($request);
        $data = $request->validate([
            'amount' => 'nullable|integer|min:1|max:1000000',
            'idempotency_key' => 'nullable|string|max:128',
            'project_id' => 'nullable|string|max:191',
            'model' => 'nullable|string|max:100',
        ]);

        $reservation = $this->access->reserve(
            $session,
            $data['amount'] ?? null,
            $data['idempotency_key'] ?? null,
            array_filter(['project_id' => $data['project_id'] ?? null, 'model' => $data['model'] ?? null])
        );

        return response()->json([
            'reservation_id' => $reservation->id,
            'amount' => (int) $reservation->amount,
            'expires_at' => $reservation->expires_at->toIso8601String(),
            'status' => $this->access->payload($session),
        ]);
    }

    public function settle(Request $request, string $id)
    {
        $session = $this->access->authenticate($request);
        $data = $request->validate(['status' => 'required|in:committed,refunded']);
        $reservation = $this->access->settle($session, $id, $data['status']);
        return response()->json([
            'reservation_id' => $reservation->id,
            'status' => $reservation->status,
            'credits' => $this->access->payload($session)['access'],
        ]);
    }

    public function projects(Request $request)
    {
        $session = $this->access->authenticate($request);
        $projects = FlowGuardProject::where('user_id', $session->user_id)->latest('last_seen_at')->latest('updated_at')->get();
        return response()->json(['projects' => $projects->map(fn ($project) => $this->projectPayload($project))->values()]);
    }

    public function saveProject(Request $request)
    {
        $session = $this->access->authenticate($request);
        $data = $request->validate([
            'external_project_id' => 'required|string|max:191',
            'name' => 'nullable|string|max:255',
            'url' => ['required', 'url', 'max:2048', function ($attribute, $value, $fail) {
                if (!str_starts_with(strtolower((string) $value), 'https://flow.google.com/')) {
                    $fail('Project URLs must belong to flow.google.com.');
                }
            }],
        ]);

        $project = FlowGuardProject::updateOrCreate(
            ['user_id' => $session->user_id, 'external_project_id' => $data['external_project_id']],
            ['name' => $data['name'] ?? null, 'url' => $data['url'], 'last_seen_at' => now()]
        );

        return response()->json(['project' => $this->projectPayload($project)]);
    }

    private function projectPayload(FlowGuardProject $project): array
    {
        return [
            'id' => $project->id,
            'external_project_id' => $project->external_project_id,
            'name' => $project->name,
            'url' => $project->url,
            'last_seen_at' => $project->last_seen_at?->toIso8601String(),
        ];
    }
}
