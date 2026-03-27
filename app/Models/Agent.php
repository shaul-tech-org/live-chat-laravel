<?php

namespace App\Models;

use App\Enums\AgentRole;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Agent extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'email', 'role',
        'is_online', 'is_active', 'last_seen_at',
    ];

    protected $casts = [
        'role' => AgentRole::class,
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];
}
