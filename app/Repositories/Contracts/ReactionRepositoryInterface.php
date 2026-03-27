<?php

namespace App\Repositories\Contracts;

use App\Models\Mongo\Reaction;

interface ReactionRepositoryInterface
{
    public function create(array $data): Reaction;
}
