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
}
