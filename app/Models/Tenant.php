<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'domain', 'api_key', 'widget_config',
        'telegram_chat_id', 'auto_reply_message', 'owner_id', 'is_active',
    ];

    protected $casts = [
        'widget_config' => 'array',
        'is_active' => 'boolean',
    ];
}
