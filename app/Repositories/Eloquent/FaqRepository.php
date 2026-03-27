<?php

namespace App\Repositories\Eloquent;

use App\Models\FaqEntry;
use App\Repositories\Contracts\FaqRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaqRepository implements FaqRepositoryInterface
{
    public function findById(string $id): ?FaqEntry
    {
        return FaqEntry::find($id);
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return FaqEntry::orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): FaqEntry
    {
        return FaqEntry::create($data);
    }

    public function delete(FaqEntry $faq): void
    {
        $faq->delete();
    }

    public function findMatchingKeyword(string $tenantId, string $content): ?FaqEntry
    {
        $entries = FaqEntry::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($entries as $entry) {
            if (str_contains($content, $entry->keyword)) {
                return $entry;
            }
        }

        return null;
    }
}
