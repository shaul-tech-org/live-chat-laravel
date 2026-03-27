<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\ChatRoom;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomService
{
    public function __construct(
        private readonly RoomRepositoryInterface $roomRepo,
    ) {}

    public function findForTenant(string $id, string $tenantId): ChatRoom
    {
        $room = $this->roomRepo->findByIdAndTenant($id, $tenantId);

        if (!$room) {
            throw new NotFoundException('채팅방을 찾을 수 없습니다.');
        }

        return $room;
    }

    public function findOrFail(string $id): ChatRoom
    {
        $room = $this->roomRepo->findById($id);

        if (!$room) {
            throw new NotFoundException('채팅방을 찾을 수 없습니다.');
        }

        return $room;
    }

    public function create(string $tenantId, array $data): ChatRoom
    {
        return $this->roomRepo->createForVisitor($tenantId, $data);
    }

    public function listByVisitor(string $tenantId, string $visitorId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->roomRepo->listByVisitor($tenantId, $visitorId, $perPage);
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->roomRepo->listAll($perPage);
    }

    public function updateStatus(string $id, string $status): ChatRoom
    {
        $room = $this->findOrFail($id);

        return $this->roomRepo->updateStatus($room, $status);
    }
}
