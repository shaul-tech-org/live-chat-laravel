<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'messages';

    protected $fillable = [
        'room_id', 'tenant_id', 'sender_type', 'sender_name',
        'content', 'content_type', 'file_url', 'is_read',
        'reply_to', 'created_at',
    ];
}
