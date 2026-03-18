<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
