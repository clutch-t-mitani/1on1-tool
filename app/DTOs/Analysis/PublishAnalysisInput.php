<?php

namespace App\DTOs\Analysis;

final readonly class PublishAnalysisInput
{
    public function __construct(
        public int $analysisId,
        public int $userId,
        public int $viewerId,
    ) {}
}
