<?php

namespace App\Models\Mongo;

use Illuminate\Database\Eloquent\Attributes\Connection;
use MongoDB\Laravel\Eloquent\Model;

#[Connection('mongodb')]
class Reaction extends Model
{
    protected $collection = 'reactions';

    protected $fillable = [
        'message_id', 'emoji', 'user_id',
    ];
}
