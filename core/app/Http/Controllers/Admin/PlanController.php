<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccountListing;

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

        return [
            'plan' => $plan ?: new Plan(['features' => []]),
            'accounts' => $accounts,
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
            ->with(['accounts' => fn ($query) => $query->with('socialMedia')])
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

        $notify[] = ['success', $msg];
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
