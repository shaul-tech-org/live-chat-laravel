<?php

namespace App\Services;

use App\Models\Feedback;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeedbackService
{
    public function __construct(
        private readonly FeedbackRepositoryInterface $feedbackRepo,
    ) {}

    public function create(string $tenantId, array $data): Feedback
    {
        return $this->feedbackRepo->create(array_merge($data, [
            'tenant_id' => $tenantId,
        ]));
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->feedbackRepo->listAll($perPage);
    }

    public function averageRating(): float
    {
        return $this->feedbackRepo->averageRating();
    }
}
