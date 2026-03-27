<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\FaqEntry;
use App\Repositories\Contracts\FaqRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaqService
{
    public function __construct(
        private readonly FaqRepositoryInterface $faqRepo,
    ) {}

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->faqRepo->listAll($perPage);
    }

    public function create(array $data): FaqEntry
    {
        return $this->faqRepo->create($data);
    }

    public function delete(string $id): void
    {
        $faq = $this->faqRepo->findById($id);

        if (!$faq) {
            throw new NotFoundException('FAQ를 찾을 수 없습니다.');
        }

        $this->faqRepo->delete($faq);
    }
}
