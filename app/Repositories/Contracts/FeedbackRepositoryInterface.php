<?php

namespace App\Repositories\Contracts;

use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FeedbackRepositoryInterface
{
    public function create(array $data): Feedback;

    public function listAll(int $perPage = 20): LengthAwarePaginator;

    public function averageRating(): float;

    public function averageRatingSince(Carbon $since): float;

    public function averageRatingForDate(string $date): float;
}
