<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant;

    public function findByApiKey(string $apiKey): ?Tenant;

    public function listAll(int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): Tenant;

    public function update(Tenant $tenant, array $data): Tenant;

    public function generateApiKey(): string;
}
