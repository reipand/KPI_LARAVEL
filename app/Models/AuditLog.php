<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'module_name',
        'action_name',
        'entity_name',
        'entity_id',
        'old_value_json',
        'new_value_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_value_json' => 'array',
        'new_value_json' => 'array',
        'created_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
