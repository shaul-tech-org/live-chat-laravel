<?php

namespace App\Services;

use App\Events\ReactionAdded;
use App\Models\Mongo\Reaction;
use App\Repositories\Contracts\ReactionRepositoryInterface;

class ReactionService
{
    public function __construct(
        private readonly ReactionRepositoryInterface $reactionRepo,
        private readonly RoomService $roomService,
    ) {}

    public function addReaction(string $roomId, string $tenantId, array $data): Reaction
    {
        $room = $this->roomService->findForTenant($roomId, $tenantId);

        $reaction = $this->reactionRepo->create([
            'room_id' => $room->id,
            'message_id' => $data['message_id'],
            'emoji' => $data['emoji'],
            'user_id' => $data['user_id'],
        ]);

        broadcast(new ReactionAdded(
            room_id: $room->id,
            message_id: $data['message_id'],
            emoji: $data['emoji'],
            user_id: $data['user_id'],
        ))->toOthers();

        return $reaction;
    }
}
