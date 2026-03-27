<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Events\SystemMessage;
use App\Models\Agent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OfflineAutoReplyListener implements ShouldQueue
{
    public function handle(MessageSent $event): void
    {
        try {
            if ($event->sender_type !== 'visitor') {
                return;
            }

            $hasOnlineAgent = Agent::where('tenant_id', $event->tenant_id)
                ->where('is_online', true)
                ->where('is_active', true)
                ->exists();

            if ($hasOnlineAgent) {
                return;
            }

            broadcast(new SystemMessage(
                room_id: $event->room_id,
                tenant_id: $event->tenant_id,
                content: '현재 상담사가 부재중입니다. 빠른 시간 내 답변드리겠습니다.',
                type: 'info',
            ));
        } catch (\Exception $e) {
            Log::debug('OfflineAutoReplyListener: skipped — ' . $e->getMessage());
        }
    }

    public function failed(MessageSent $event, \Throwable $exception): void
    {
        Log::debug('OfflineAutoReplyListener: failed — ' . $exception->getMessage());
    }
}
