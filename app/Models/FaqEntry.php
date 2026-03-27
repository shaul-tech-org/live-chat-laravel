<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
#[Fillable([
    'tenant_id', 'keyword', 'answer', 'is_active',
])]
class FaqEntry extends Model
{
    use HasUuids, SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
