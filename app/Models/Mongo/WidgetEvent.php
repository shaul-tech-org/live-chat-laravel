<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class WidgetEvent extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'widget_events';

    protected $fillable = [
        'tenant_id', 'visitor_id', 'event_type', 'page_url',
        'user_agent', 'ip_hash', 'metadata',
    ];
}
