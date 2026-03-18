<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Analysis extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'viewer_id',
        'status',
        'summary_content',
        'annotation_text',
        'error_message',
        'published_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'failed_at'    => 'datetime',
        ];
    }

    /** 部下（作成者） */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 上司（閲覧者） */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    public function scopeVisibleToViewer(Builder $query, int $viewerId): void
    {
        $query->where('viewer_id', $viewerId);
    }

    /** 公開から7日以内のみ */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where('published_at', '>=', now()->subDays(7));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->published_at !== null
            && $this->published_at->lt(now()->subDays(7));
    }
}
