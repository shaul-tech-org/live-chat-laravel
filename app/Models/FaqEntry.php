<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqEntry extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'keyword', 'answer', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
