<?php

namespace App\Repositories\Contracts;

use App\Models\Mongo\Message;
use Illuminate\Support\Collection;

interface MessageRepositoryInterface
{
    public function create(array $data): Message;

    public function getHistory(string $roomId, int $limit = 50, ?string $before = null): Collection;

    public function markAsRead(string $roomId, string $excludeSenderType): array;
}
