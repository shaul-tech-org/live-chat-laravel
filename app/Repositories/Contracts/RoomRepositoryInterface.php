<?php

namespace App\Repositories\Contracts;

use App\Models\ChatRoom;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoomRepositoryInterface
{
    public function findByIdAndTenant(string $id, string $tenantId): ?ChatRoom;

    public function findById(string $id): ?ChatRoom;

    public function createForVisitor(string $tenantId, array $data): ChatRoom;

    public function listByVisitor(string $tenantId, string $visitorId, int $perPage = 20): LengthAwarePaginator;

    public function listAll(int $perPage = 20, string $sort = 'newest'): LengthAwarePaginator;

    public function updateStatus(ChatRoom $room, string $status): ChatRoom;

    public function countSince(Carbon $since): int;

    public function dailyCountSince(Carbon $since): \Illuminate\Support\Collection;
}
