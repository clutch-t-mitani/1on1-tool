<?php

namespace App\DTOs\DailyLog;

final readonly class StoreTextLogInput
{
    public function __construct(
        public int $userId,
        public int $questionId,
        public string $answerText,
        public string $targetDate,
    ) {}
}
