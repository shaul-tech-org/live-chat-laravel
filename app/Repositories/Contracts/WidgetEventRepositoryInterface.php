<?php

namespace App\Repositories\Contracts;

use App\Models\Mongo\WidgetEvent;

interface WidgetEventRepositoryInterface
{
    public function create(array $data): WidgetEvent;
}
