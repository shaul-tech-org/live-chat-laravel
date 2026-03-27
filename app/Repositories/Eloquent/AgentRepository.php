<?php

namespace App\Repositories\Eloquent;

use App\Models\Agent;
use App\Repositories\Contracts\AgentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AgentRepository implements AgentRepositoryInterface
{
    public function findById(string $id): ?Agent
    {
        return Agent::find($id);
    }

    public function findByTenantAndEmail(string $tenantId, string $email): ?Agent
    {
        return Agent::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return Agent::orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): Agent
    {
        return Agent::create($data);
    }

    public function delete(Agent $agent): void
    {
        $agent->delete();
    }
}
