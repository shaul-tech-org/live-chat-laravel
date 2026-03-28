<?php

namespace App\Models;

use App\Enums\AssignmentMethod;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
#[Fillable([
    'tenant_id', 'visitor_id', 'visitor_name', 'visitor_email', 'visitor_phone',
    'status', 'assigned_agent_id', 'assignment_method', 'closed_at',
])]
class ChatRoom extends Model
{
    use HasUuids, SoftDeletes;

    protected $casts = [
        'status' => RoomStatus::class,
        'assignment_method' => AssignmentMethod::class,
        'closed_at' => 'datetime',
    ];

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }
}
