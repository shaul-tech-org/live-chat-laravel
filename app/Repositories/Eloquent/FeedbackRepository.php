<?php

namespace App\Repositories\Eloquent;

use App\Models\Feedback;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeedbackRepository implements FeedbackRepositoryInterface
{
    public function create(array $data): Feedback
    {
        return Feedback::create($data);
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return Feedback::orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function averageRating(): float
    {
        return (float) Feedback::avg('rating') ?: 0.0;
    }

    public function averageRatingSince(Carbon $since): float
    {
        return (float) Feedback::where('created_at', '>=', $since)
            ->avg('rating') ?: 0.0;
    }

    public function averageRatingForDate(string $date): float
    {
        return (float) Feedback::whereDate('created_at', $date)
            ->avg('rating') ?: 0.0;
    }
}
