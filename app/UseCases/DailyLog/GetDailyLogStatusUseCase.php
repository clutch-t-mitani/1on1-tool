<?php

namespace App\UseCases\DailyLog;

use App\Models\DailyLog;
use App\Models\SystemSetting;

final class GetDailyLogStatusUseCase
{
    /**
     * @return array{answered_question_ids: list<int>, total_log_days: int, target_date: string}
     */
    public function execute(int $userId): array
    {
        $masterNow = SystemSetting::getMasterDate();
        $targetDate = $masterNow->hour < 12
            ? $masterNow->startOfDay()->toDateString()
            : $masterNow->copy()->addDay()->startOfDay()->toDateString();

        $answeredQuestionIds = DailyLog::query()
            ->forUser($userId)
            ->where('target_date', $targetDate)
            ->pluck('question_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        $totalLogDays = DailyLog::query()
            ->forUser($userId)
            ->distinct('target_date')
            ->count('target_date');

        return [
            'answered_question_ids' => $answeredQuestionIds,
            'total_log_days'        => $totalLogDays,
            'target_date'           => $targetDate,
        ];
    }
}
