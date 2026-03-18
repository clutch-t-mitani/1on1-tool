<?php

namespace App\UseCases\DailyLog;

use App\DTOs\DailyLog\StoreTextLogInput;
use App\Exceptions\DuplicateLogException;
use App\Models\DailyLog;
use App\Services\EncryptionService;
use Illuminate\Database\QueryException;

final class StoreTextLogUseCase
{
    public function __construct(
        private readonly EncryptionService $encryption,
    ) {}

    /**
     * @throws DuplicateLogException
     */
    public function execute(StoreTextLogInput $input): void
    {
        $alreadyAnswered = DailyLog::query()
            ->forUser($input->userId)
            ->where('question_id', $input->questionId)
            ->where('target_date', $input->targetDate)
            ->exists();

        if ($alreadyAnswered) {
            throw new DuplicateLogException();
        }

        try {
            DailyLog::create([
                'user_id'     => $input->userId,
                'question_id' => $input->questionId,
                'answer_text' => $this->encryption->encrypt($input->answerText),
                'target_date' => $input->targetDate,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new DuplicateLogException();
            }
            throw $e;
        }
    }
}
