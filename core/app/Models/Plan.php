<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = [
        'features' => 'array',
        'frontend_visible' => 'boolean',
        'flow_access' => 'boolean',
        'google_flow_account_id' => 'integer',
        'tts_access' => 'boolean',
        'tts_credit_limit' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function googleFlowAccount()
    {
        return $this->belongsTo(GoogleFlowAccount::class, 'google_flow_account_id');
    }

    public function platforms()
    {
        return $this->belongsToMany(
            SocialMedia::class,
            'plan_social_media',
            'plan_id',
            'social_media_id'
        );
    }

    public function accounts()
    {
        return $this->belongsToMany(
            \App\Models\AccountListing::class,
            'plan_account_listing',
            'plan_id',
            'account_listing_id'
        )->with('socialMedia');
    }

    public function getIncludedResourcesAttribute()
    {
        return $this->platforms()
            ->where('social_media.status', \App\Constants\Status::ENABLE)
            ->get()
            ->pluck('name')
            ->unique();
    }

    /**
     * Return exactly the feature labels saved by the administrator.
     *
     * The admin form stores one label per row, while older records may still
     * contain nested JSON or newline/comma-separated values. Normalising here
     * gives every frontend plan card the same source of truth and prevents
     * view-level fallback copy from masquerading as configured features.
     */
    public function getFeatureListAttribute(): array
    {
        $flatten = function ($items) use (&$flatten): array {
            $result = [];
            foreach ((array) $items as $item) {
                if (is_array($item)) {
                    $result = array_merge($result, $flatten($item));
                    continue;
                }

                if (is_string($item)) {
                    $decoded = json_decode($item, true);
                    if (is_array($decoded)) {
                        $result = array_merge($result, $flatten($decoded));
                        continue;
                    }
                }

                if (is_scalar($item)) {
                    $value = trim((string) $item);
                    if ($value !== '') $result[] = preg_replace('/^[-•]\s*/u', '', $value);
                }
            }
            return $result;
        };

        $features = $flatten($this->features);
        if (count($features) === 1 && str_contains($features[0], ',')) {
            $features = preg_split('/\s*,\s*/', $features[0]);
        }

        return collect($features)
            ->map(fn ($feature) => trim((string) $feature))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Exact capability labels rendered on public plan cards.
     */
    public function getFrontendCapabilitiesAttribute(): array
    {
        return collect($this->feature_list)
            ->merge($this->included_resources ?? [])
            ->map(fn ($capability) => trim((string) $capability))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
