<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class Reaction extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reactions';

    protected $fillable = [
        'message_id', 'emoji', 'user_id',
    ];
}
