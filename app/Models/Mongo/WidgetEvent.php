<?php

namespace App\Models\Mongo;

use Illuminate\Database\Eloquent\Attributes\Connection;
use MongoDB\Laravel\Eloquent\Model;

#[Connection('mongodb')]
class WidgetEvent extends Model
{
    protected $collection = 'widget_events';

    protected $fillable = [
        'tenant_id', 'visitor_id', 'event_type', 'page_url',
        'user_agent', 'ip_hash', 'metadata',
    ];
}
