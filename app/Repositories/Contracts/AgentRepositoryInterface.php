<?php

namespace App\Repositories\Contracts;

use App\Models\Agent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AgentRepositoryInterface
{
    public function findById(string $id): ?Agent;

    public function findByTenantAndEmail(string $tenantId, string $email): ?Agent;

    public function listAll(int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): Agent;

    public function delete(Agent $agent): void;
}
