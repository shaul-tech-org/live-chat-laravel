<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->_id,
            'room_id' => $this->room_id,
            'message_id' => $this->message_id,
            'emoji' => $this->emoji,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at instanceof Carbon
                ? $this->created_at->toISOString()
                : (string) $this->created_at,
        ];
    }
}
