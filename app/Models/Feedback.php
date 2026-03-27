<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'feedbacks';

    protected $fillable = [
        'tenant_id', 'room_id', 'visitor_email', 'rating', 'comment', 'page_url',
    ];
}
