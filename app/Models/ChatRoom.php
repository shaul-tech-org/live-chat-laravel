<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'visitor_id', 'visitor_name', 'visitor_email',
        'status', 'assigned_agent_id', 'closed_at',
    ];

    protected $casts = [
        'status' => RoomStatus::class,
        'closed_at' => 'datetime',
    ];
}
