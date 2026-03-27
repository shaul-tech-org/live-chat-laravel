<?php

namespace App\Repositories\Eloquent;

use App\Models\ChatRoom;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomRepository implements RoomRepositoryInterface
{
    public function findByIdAndTenant(string $id, string $tenantId): ?ChatRoom
    {
        return ChatRoom::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findById(string $id): ?ChatRoom
    {
        return ChatRoom::find($id);
    }

    public function createForVisitor(string $tenantId, array $data): ChatRoom
    {
        return ChatRoom::create(array_merge($data, [
            'tenant_id' => $tenantId,
            'visitor_id' => 'v_' . substr(bin2hex(random_bytes(4)), 0, 8),
            'status' => 'open',
        ]));
    }

    public function listByVisitor(string $tenantId, string $visitorId, int $perPage = 20): LengthAwarePaginator
    {
        return ChatRoom::where('tenant_id', $tenantId)
            ->where('visitor_id', $visitorId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function listAll(int $perPage = 20, string $sort = 'newest'): LengthAwarePaginator
    {
        $query = ChatRoom::query();

        $query = match ($sort) {
            'activity' => $query->orderByDesc('updated_at'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage);
    }

    public function updateStatus(ChatRoom $room, string $status): ChatRoom
    {
        $room->status = $status;

        if ($status === 'closed' && $room->closed_at === null) {
            $room->closed_at = now();
        }

        $room->save();

        return $room;
    }

    public function countSince(Carbon $since): int
    {
        return ChatRoom::where('created_at', '>=', $since)->count();
    }

    public function dailyCountSince(Carbon $since): \Illuminate\Support\Collection
    {
        return ChatRoom::select(
            \Illuminate\Support\Facades\DB::raw("DATE(created_at) as date"),
            \Illuminate\Support\Facades\DB::raw("COUNT(*) as chats"),
        )
            ->where('created_at', '>=', $since)
            ->groupBy(\Illuminate\Support\Facades\DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();
    }
}
