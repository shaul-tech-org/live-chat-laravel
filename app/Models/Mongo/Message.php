<?php

namespace App\Models\Mongo;

use Illuminate\Database\Eloquent\Attributes\Connection;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

#[Connection('mongodb')]
class Message extends Model
{
    use SoftDeletes;

    protected $collection = 'messages';

    protected $fillable = [
        'room_id', 'tenant_id', 'sender_type', 'sender_name',
        'content', 'content_type', 'file_url', 'is_read',
        'reply_to', 'created_at',
    ];
}
