<?php

namespace App\Repositories\Eloquent;

use App\Models\Mongo\WidgetEvent;
use App\Repositories\Contracts\WidgetEventRepositoryInterface;

class WidgetEventRepository implements WidgetEventRepositoryInterface
{
    public function create(array $data): WidgetEvent
    {
        return WidgetEvent::create($data);
    }
}
