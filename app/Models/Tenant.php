<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
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

    protected $hidden = [
        'api_key', // API 키는 기본적으로 숨김 (마스킹 필요 시 별도 처리)
    ];
}
