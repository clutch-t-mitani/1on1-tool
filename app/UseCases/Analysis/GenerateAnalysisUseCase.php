<?php

namespace App\UseCases\Analysis;

use App\DTOs\Analysis\GenerateAnalysisInput;
use App\Exceptions\AnalysisAlreadyInProgressException;
use App\Exceptions\InsufficientLogsException;
use App\Jobs\GenerateAnalysisJob;
use App\Models\Analysis;
use App\Models\DailyLog;

final class GenerateAnalysisUseCase
{
    private const MINIMUM_LOG_DAYS = 5;

    /**
     * @throws InsufficientLogsException
     * @throws AnalysisAlreadyInProgressException
     */
    public function execute(GenerateAnalysisInput $input): Analysis
    {
        // 未使用ログが5日分以上あるかチェック
        $logDays = DailyLog::query()
            ->forUser($input->userId)
            ->notSummarized()
            ->distinct('target_date')
            ->count('target_date');

        if ($logDays < self::MINIMUM_LOG_DAYS) {
            throw new InsufficientLogsException(self::MINIMUM_LOG_DAYS - $logDays);
        }

        // 処理中チェック
        $inProgress = Analysis::query()
            ->where('user_id', $input->userId)
            ->where('status', 'pending')
            ->exists();

        if ($inProgress) {
            throw new AnalysisAlreadyInProgressException();
        }

        $analysis = Analysis::create([
            'user_id'         => $input->userId,
            'viewer_id'       => null,
            'status'          => 'pending',
            'summary_content' => null,
        ]);

        GenerateAnalysisJob::dispatch($analysis->id, $input->userId);

        return $analysis;
    }
}
