<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccountListing;

class PlanController extends Controller
{
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
        
        $rawFeatures = $request->input('features', []);
        if (is_string($rawFeatures)) {
            $decodedFeatures = json_decode($rawFeatures, true);
            $rawFeatures = is_array($decodedFeatures)
                ? $decodedFeatures
                : preg_split('/\r?\n/', $rawFeatures);
        }
        $plan->features = collect((array) $rawFeatures)
            ->map(fn ($feature) => trim((string) $feature))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    public function status($id)
    {
        return Plan::changeStatus($id);
    }
}
