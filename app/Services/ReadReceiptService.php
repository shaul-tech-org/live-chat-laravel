<?php

namespace App\Services;

use App\Events\MessageRead;
use App\Repositories\Contracts\MessageRepositoryInterface;

class ReadReceiptService
{
    public function __construct(
        private readonly MessageRepositoryInterface $messageRepo,
        private readonly RoomService $roomService,
    ) {}

    public function markAsRead(string $roomId, string $tenantId, array $data): array
    {
        $room = $this->roomService->findForTenant($roomId, $tenantId);

        $result = $this->messageRepo->markAsRead($room->id, $data['reader_type']);

        broadcast(new MessageRead(
            room_id: $room->id,
            tenant_id: $room->tenant_id,
            reader_type: $data['reader_type'],
            reader_name: $data['reader_name'],
            last_read_message_id: $result['last_id'],
        ))->toOthers();

        return [
            'status' => 'ok',
            'updated_count' => $result['count'],
            'last_read_message_id' => $result['last_id'],
        ];
    }
}
