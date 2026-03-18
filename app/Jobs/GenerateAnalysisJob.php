<?php

namespace App\Jobs;

use App\Events\AnalysisCompleted;
use App\Models\Analysis;
use App\Models\DailyLog;
use App\Services\EncryptionService;
use App\Services\GptSummaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateAnalysisJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;
    public int $tries   = 2;

    public function __construct(
        public readonly int $analysisId,
        public readonly int $userId,
    ) {
        $this->onQueue('ai-processing');
    }

    public function handle(
        EncryptionService $encryption,
        GptSummaryService $gpt,
    ): void {
        // 未使用ログのみ取得
        $logs = DailyLog::query()
            ->forUser($this->userId)
            ->notSummarized()
            ->with('question')
            ->get();

        // メモリ上でのみ復号
        $decryptedLogs = $logs->map(fn (DailyLog $log): array => [
            'question' => $log->question->content,
            'answer'   => $encryption->decrypt($log->answer_text),
            'date'     => $log->target_date->toDateString(),
        ])->all();

        $summary = $gpt->summarize($decryptedLogs);

        Analysis::find($this->analysisId)?->update([
            'status'          => 'completed',
            'summary_content' => $summary,
        ]);

        // 使用したログに summarized_at を記録
        DailyLog::query()
            ->whereIn('id', $logs->pluck('id'))
            ->update(['summarized_at' => now()]);

        event(new AnalysisCompleted($this->analysisId, $this->userId));
    }

    /**
     * Job の最終失敗時に analysis を失敗状態に更新する。
     */
    public function failed(\Throwable $exception): void
    {
        Analysis::find($this->analysisId)?->update([
            'status'        => 'failed',
            'error_message' => mb_substr($exception->getMessage(), 0, 500),
            'failed_at'     => now(),
        ]);
    }
}
