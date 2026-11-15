<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccountListing;
use App\Models\GoogleFlowAccount;
use App\Services\FlowAccess;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    private function formData(?Plan $plan = null): array
    {
        if ($plan) {
            $plan->features = $this->normalizeFeatures($plan->features);
        }

        $accounts = AccountListing::with('socialMedia')
            ->orderBy('social_media_id')
            ->orderBy('title')
            ->get();

        $flowAccounts = GoogleFlowAccount::active()
            ->orderBy('label')
            ->orderBy('email')
            ->get(['id', 'label', 'email', 'status']);

        return [
            'plan' => $plan ?: new Plan(['features' => []]),
            'accounts' => $accounts,
            'flowAccounts' => $flowAccounts,
        ];
    }

    public function create()
    {
        return view('admin.plan.form', array_merge(
            ['pageTitle' => 'Create Subscription Plan'],
            $this->formData()
        ));
    }

    public function edit($id)
    {
        $plan = Plan::with('accounts')->findOrFail($id);

        return view('admin.plan.form', array_merge(
            ['pageTitle' => 'Update Subscription Plan'],
            $this->formData($plan)
        ));
    }

    public function index()
    {
        $pageTitle = 'Subscription Plans';
        $plans = Plan::withCount('users')
            ->with(['accounts' => fn ($query) => $query->with('socialMedia'), 'googleFlowAccount'])
            ->latest()
            ->paginate(getPaginate());
        $accounts = AccountListing::with('socialMedia')
            ->orderBy('social_media_id')
            ->orderBy('title')
            ->get();

        return view('admin.plan.index', compact('pageTitle', 'plans', 'accounts'));
    }

    public function store(Request $request, $id = null)
    {
        $request->validate([
            'name'  => 'required',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable',
            'account_ids' => 'nullable|array',
            'account_ids.*' => 'integer|exists:account_listings,id',
            'flow_access' => 'nullable|boolean',
            'google_flow_account_id' => [
                Rule::requiredIf($request->boolean('flow_access')),
                'nullable',
                'integer',
                Rule::exists('google_flow_accounts', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'tts_access' => 'nullable|boolean',
            'tts_credit_limit' => 'nullable|integer|min:0|max:1000000000',
        ]);

        if ($id) {
            $plan = Plan::findOrFail($id);
            $msg  = 'Plan updated successfully';
        } else {
            $plan = new Plan();
            $msg  = 'Plan created successfully';
        }

        $plan->name  = $request->name;
        $plan->price = $request->price;
        
        $plan->features = $this->normalizeFeatures($request->input('features', []));
        $plan->flow_access = $request->boolean('flow_access');
        $plan->google_flow_account_id = $plan->flow_access && $request->filled('google_flow_account_id')
            ? (int) $request->input('google_flow_account_id')
            : null;
        $plan->tts_access = $request->boolean('tts_access');
        $plan->tts_credit_limit = $plan->tts_access && $request->filled('tts_credit_limit')
            ? (int) $request->input('tts_credit_limit')
            : null;

        $plan->save();
        // Keep existing subscribers in sync when an administrator changes a
        // plan's TTS entitlement. Usage is preserved; only the limit/access
        // policy follows the updated plan.
        $plan->users()->update([
            'tts_access' => $plan->tts_access,
            'tts_credit_limit' => $plan->tts_access ? $plan->tts_credit_limit : null,
        ]);
        $accountIds = collect((array) $request->input('account_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $plan->accounts()->sync($accountIds);

        // A plan's selected Flow account is the source of truth for every
        // user on that plan. Keep existing subscribers in sync immediately.
        $flowAssignment = $plan->flow_access ? $plan->google_flow_account_id : null;
        $flowAssignmentWarnings = [];
        foreach ($plan->users()->get() as $user) {
            try {
                FlowAccess::assign($user, $flowAssignment ? (int) $flowAssignment : null, true);
            } catch (\Throwable $e) {
                $flowAssignmentWarnings[] = $user->username ?: $user->email;
            }
        }

        $notify[] = ['success', $msg];
        if ($flowAssignmentWarnings) {
            $notify[] = ['warning', 'Some existing plan users still need Flow assignment: ' . implode(', ', $flowAssignmentWarnings)];
        }
        return back()->withNotify($notify);
    }

    /**
     * Normalize current and legacy feature payloads into one flat list.
     * Older plans may contain a JSON array nested inside the JSON column or a
     * comma-joined string; both are safely converted when the plan is saved.
     */
    private function normalizeFeatures($value): array
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\r?\n/', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $features = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $features = array_merge($features, $this->normalizeFeatures($item));
                continue;
            }

            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }

            $decoded = json_decode($item, true);
            if (is_array($decoded)) {
                $features = array_merge($features, $this->normalizeFeatures($decoded));
                continue;
            }

            // Legacy forms sometimes stored all feature labels in one string.
            if (count($value) === 1 && str_contains($item, ',')) {
                $features = array_merge($features, preg_split('/\s*,\s*/', $item));
            } else {
                $features[] = $item;
            }
        }

        return collect($features)
            ->map(fn ($feature) => trim((string) $feature))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function status($id)
    {
        return Plan::changeStatus($id);
    }
}
