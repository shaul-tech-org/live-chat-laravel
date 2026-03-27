<?php

namespace App\Repositories\Contracts;

use App\Models\FaqEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FaqRepositoryInterface
{
    public function findById(string $id): ?FaqEntry;

    public function listAll(int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): FaqEntry;

    public function delete(FaqEntry $faq): void;

    public function findMatchingKeyword(string $tenantId, string $content): ?FaqEntry;
}
