<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Agent;
use App\Repositories\Contracts\AgentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AgentService
{
    public function __construct(
        private readonly AgentRepositoryInterface $agentRepo,
    ) {}

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->agentRepo->listAll($perPage);
    }

    public function create(array $data): Agent
    {
        return $this->agentRepo->create($data);
    }

    public function delete(string $id): void
    {
        $agent = $this->agentRepo->findById($id);

        if (!$agent) {
            throw new NotFoundException('상담원을 찾을 수 없습니다.');
        }

        $this->agentRepo->delete($agent);
    }
}
