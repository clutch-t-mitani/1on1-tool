<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DailyLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'question_id',
        'answer_text',
        'summarized_at',
    ];

    protected $casts = [
        'summarized_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeWithinDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeNotSummarized(Builder $query): void
    {
        $query->whereNull('summarized_at');
    }
}
