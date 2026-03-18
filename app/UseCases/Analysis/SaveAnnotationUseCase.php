<?php

namespace App\UseCases\Analysis;

use App\Exceptions\AnalysisAlreadyPublishedException;
use App\Models\Analysis;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class SaveAnnotationUseCase
{
    /**
     * @throws ModelNotFoundException
     * @throws AnalysisAlreadyPublishedException
     */
    public function execute(int $analysisId, int $userId, ?string $annotationText): void
    {
        $analysis = Analysis::query()
            ->where('id', $analysisId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($analysis->published_at !== null) {
            throw new AnalysisAlreadyPublishedException();
        }

        $analysis->update(['annotation_text' => $annotationText]);
    }
}
