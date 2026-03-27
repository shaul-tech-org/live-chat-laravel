<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepo,
    ) {}

    public function findByApiKeyOrFail(string $apiKey): Tenant
    {
        if (!$apiKey) {
            throw new UnauthorizedException('API 키가 필요합니다.');
        }

        $tenant = $this->tenantRepo->findByApiKey($apiKey);

        if (!$tenant) {
            throw new UnauthorizedException('유효하지 않은 API 키입니다.');
        }

        if (!$tenant->is_active) {
            throw new ForbiddenException('비활성화된 테넌트입니다.');
        }

        return $tenant;
    }

    public function findOrFail(string $id): Tenant
    {
        $tenant = $this->tenantRepo->findById($id);

        if (!$tenant) {
            throw new NotFoundException('테넌트를 찾을 수 없습니다.');
        }

        return $tenant;
    }

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->tenantRepo->listAll($perPage);
    }

    public function create(array $data): Tenant
    {
        $apiKey = $this->tenantRepo->generateApiKey();

        return $this->tenantRepo->create(array_merge($data, [
            'api_key' => $apiKey,
        ]));
    }

    public function update(string $id, array $data): Tenant
    {
        $tenant = $this->findOrFail($id);

        return $this->tenantRepo->update($tenant, $data);
    }

    public function rotateApiKey(string $id): array
    {
        $tenant = $this->findOrFail($id);
        $newKey = $this->tenantRepo->generateApiKey();
        $this->tenantRepo->update($tenant, ['api_key' => $newKey]);

        return [
            'id' => $tenant->id,
            'api_key' => $newKey,
            'message' => 'API 키가 재발급되었습니다.',
        ];
    }
}
