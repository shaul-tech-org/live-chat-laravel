<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->_id,
            'room_id' => $this->room_id,
            'tenant_id' => $this->tenant_id,
            'sender_type' => $this->sender_type,
            'sender_name' => $this->sender_name,
            'content' => $this->content,
            'content_type' => $this->content_type,
            'file_url' => $this->file_url,
            'reply_to' => $this->reply_to,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at instanceof Carbon
                ? $this->created_at->toISOString()
                : (string) $this->created_at,
        ];
    }
}
