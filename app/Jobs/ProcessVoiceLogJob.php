<?php

namespace App\Jobs;

use App\Exceptions\DuplicateLogException;
use App\Models\DailyLog;
use App\Services\EncryptionService;
use App\Services\WhisperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

final class ProcessVoiceLogJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries   = 3;

    public function __construct(
        public readonly int $userId,
        public readonly int $questionId,
        public readonly string $s3Path,
        public readonly string $targetDate,
    ) {}

    public function handle(
        WhisperService $whisper,
        EncryptionService $encryption,
    ): void {
        $transcribed = $whisper->transcribe($this->s3Path);

        try {
            DailyLog::create([
                'user_id'     => $this->userId,
                'question_id' => $this->questionId,
                'answer_text' => $encryption->encrypt($transcribed),
                'target_date' => $this->targetDate,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new DuplicateLogException();
            }
            throw $e;
        }

        Storage::disk('s3')->delete($this->s3Path);
    }

    /**
     * Job の最終失敗時にも S3 音声ファイルを削除する。
     */
    public function failed(\Throwable $exception): void
    {
        Storage::disk('s3')->delete($this->s3Path);
    }
}
