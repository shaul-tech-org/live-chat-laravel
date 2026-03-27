<?php

namespace App\Repositories\Eloquent;

use App\Models\Mongo\Reaction;
use App\Repositories\Contracts\ReactionRepositoryInterface;

class ReactionRepository implements ReactionRepositoryInterface
{
    public function create(array $data): Reaction
    {
        return Reaction::create($data);
    }
}
