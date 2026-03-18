<?php

namespace App\DTOs\Analysis;

final readonly class GenerateAnalysisInput
{
    public function __construct(
        public int $userId,
    ) {}
}
