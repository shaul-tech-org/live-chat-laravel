<?php

namespace App\Services;

use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;

class StatsService
{
    public function __construct(
        private readonly RoomRepositoryInterface $roomRepo,
        private readonly FeedbackRepositoryInterface $feedbackRepo,
    ) {}

    public function getStats(string $period = '7d'): array
    {
        $days = $this->parsePeriodDays($period);
        $since = now()->subDays($days)->startOfDay();

        $totalChats = $this->roomRepo->countSince($since);
        $avgRating = $this->feedbackRepo->averageRatingSince($since);

        $daily = $this->roomRepo->dailyCountSince($since)
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'chats' => $row->chats,
                    'avg_rating' => round($this->feedbackRepo->averageRatingForDate($row->date), 1),
                ];
            });

        return [
            'total_chats' => $totalChats,
            'avg_rating' => round($avgRating, 1),
            'daily' => $daily,
        ];
    }

    private function parsePeriodDays(string $period): int
    {
        if (preg_match('/^(\d+)d$/', $period, $matches)) {
            return (int) $matches[1];
        }

        return 7;
    }
}
