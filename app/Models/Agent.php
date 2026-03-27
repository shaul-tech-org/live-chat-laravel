<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'email', 'role',
        'is_online', 'is_active', 'last_seen_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];
}
