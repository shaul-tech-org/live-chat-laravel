<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('feedbacks', key: 'id', keyType: 'string', incrementing: false)]
#[Fillable([
    'tenant_id', 'room_id', 'visitor_email', 'rating', 'comment', 'page_url',
])]
class Feedback extends Model
{
    use HasUuids, SoftDeletes;
}
