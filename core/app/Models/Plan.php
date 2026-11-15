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
        'tts_access' => 'boolean',
        'tts_credit_limit' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
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
        return $this->accounts()
            ->where('status', \App\Constants\Status::LISTING_ACTIVE)
            ->whereHas('socialMedia', function($q){ 
                $q->active(); 
            })
            ->with('socialMedia')
            ->get()
            ->pluck('socialMedia.name')
            ->unique();
    }
}
