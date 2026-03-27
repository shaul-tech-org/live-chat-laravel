<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenantRepository implements TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function findByApiKey(string $apiKey): ?Tenant
    {
        return Tenant::withoutTrashed()
            ->where('api_key', $apiKey)
            ->first();
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return Tenant::orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant;
    }

    public function generateApiKey(): string
    {
        return 'ck_live_' . bin2hex(random_bytes(16));
    }
}
