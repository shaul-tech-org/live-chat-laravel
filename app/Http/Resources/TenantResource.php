<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public bool $showApiKey = false;

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'api_key_masked' => $this->api_key ? substr($this->api_key, 0, 8) . '...' : null,
            'widget_config' => $this->widget_config,
            'auto_reply_message' => $this->auto_reply_message,
            'telegram_chat_id' => $this->telegram_chat_id,
            'owner_id' => $this->owner_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->showApiKey) {
            $data['api_key'] = $this->api_key;
        }

        return $data;
    }
}
