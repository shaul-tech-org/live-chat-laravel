<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'visitor_id' => $this->visitor_id,
            'visitor_name' => $this->visitor_name,
            'visitor_email' => $this->visitor_email,
            'status' => $this->status,
            'assigned_agent_id' => $this->assigned_agent_id,
            'assigned_agent_name' => $this->whenLoaded('assignedAgent', fn () => $this->assignedAgent?->name),
            'assignment_method' => $this->assignment_method,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
