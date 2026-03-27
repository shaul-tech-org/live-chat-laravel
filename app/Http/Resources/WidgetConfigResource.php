<?php

namespace App\Http\Resources;

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
        ];
    }
}
