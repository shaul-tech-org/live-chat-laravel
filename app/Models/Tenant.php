<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
#[Fillable([
    'name', 'domain', 'api_key', 'widget_config',
    'telegram_chat_id', 'auto_reply_message', 'owner_id', 'is_active',
])]
class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $casts = [
        'widget_config' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['api_key'];
}
