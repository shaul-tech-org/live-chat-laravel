<?php

namespace App\Http\Resources;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WidgetConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'widget_config' => $this->widget_config ?? (object) [],
            'auto_reply_message' => $this->auto_reply_message,
            'tenant_name' => $this->name,
            'agents_online' => Agent::where('tenant_id', $this->id)
                ->where('is_online', true)
                ->where('is_active', true)
                ->count(),
        ];
    }
}
