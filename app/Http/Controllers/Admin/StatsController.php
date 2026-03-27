<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', '7d');
        $days = $this->parsePeriodDays($period);

        $since = now()->subDays($days)->startOfDay();

        $totalChats = ChatRoom::where('created_at', '>=', $since)->count();
        $avgRating = Feedback::where('created_at', '>=', $since)->avg('rating');

        $daily = ChatRoom::select(
            DB::raw("DATE(created_at) as date"),
            DB::raw("COUNT(*) as chats"),
        )
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get()
            ->map(function ($row) use ($since) {
                $dayRating = Feedback::whereDate('created_at', $row->date)->avg('rating');
                return [
                    'date' => $row->date,
                    'chats' => $row->chats,
                    'avg_rating' => $dayRating ? round((float) $dayRating, 1) : 0,
                ];
            });

        return response()->json([
            'total_chats' => $totalChats,
            'avg_rating' => $avgRating ? round((float) $avgRating, 1) : 0,
            'daily' => $daily,
        ]);
    }

    private function parsePeriodDays(string $period): int
    {
        if (preg_match('/^(\d+)d$/', $period, $matches)) {
            return (int) $matches[1];
        }

        return 7;
    }
}
