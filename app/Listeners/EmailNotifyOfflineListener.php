<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Mail\OfflineMessageNotification;
use App\Models\Agent;
use App\Models\ChatRoom;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotifyOfflineListener implements ShouldQueue
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

            $room = ChatRoom::find($event->room_id);
            $visitorEmail = $room?->visitor_email;

            $agentEmails = Agent::where('tenant_id', $event->tenant_id)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();

            if (empty($agentEmails)) {
                Log::debug('EmailNotifyOfflineListener: no agent emails found for tenant ' . $event->tenant_id);
                return;
            }

            $mailable = new OfflineMessageNotification(
                roomId: $event->room_id,
                senderName: $event->sender_name,
                messageContent: $event->content,
                visitorEmail: $visitorEmail,
            );

            Mail::to($agentEmails[0])
                ->cc(array_slice($agentEmails, 1))
                ->send($mailable);
        } catch (\Exception $e) {
            Log::debug('EmailNotifyOfflineListener: failed — ' . $e->getMessage());
        }
    }

    public function failed(MessageSent $event, \Throwable $exception): void
    {
        Log::debug('EmailNotifyOfflineListener: failed — ' . $exception->getMessage());
    }
}
