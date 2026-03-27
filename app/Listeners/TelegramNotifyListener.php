<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TelegramNotifyListener implements ShouldQueue
{
    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    public function handle(MessageSent $event): void
    {
        try {
            if ($event->sender_type !== 'visitor') {
                return;
            }

            $content = mb_strlen($event->content) > 100
                ? mb_substr($event->content, 0, 100) . '...'
                : $event->content;

            $message = "💬 새 메시지\n방: {$event->room_id}\n보낸이: {$event->sender_name}\n내용: {$content}";

            $this->telegram->sendNotification($message);
        } catch (\Exception $e) {
            Log::debug('TelegramNotifyListener: skipped — ' . $e->getMessage());
        }
    }
}
