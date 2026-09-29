<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtensionHistory extends Model
{
    protected $table = 'extension_histories';

    protected $fillable = [
        'version',
        'filename',
        'file_path',
        'file_size',
        'type',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];
}
