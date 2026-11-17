<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowGuardModel extends Model
{
    protected $table = 'flow_guard_models';

    protected $fillable = ['name', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('enabled', true);
    }
}
