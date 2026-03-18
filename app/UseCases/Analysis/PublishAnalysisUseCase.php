<?php

namespace App\UseCases\Analysis;

use App\DTOs\Analysis\PublishAnalysisInput;
use App\Exceptions\AnalysisAlreadyPublishedException;
use App\Exceptions\DifferentCompanyException;
use App\Models\Analysis;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PublishAnalysisUseCase
{
    /**
     * @throws ModelNotFoundException
     * @throws AnalysisAlreadyPublishedException
     * @throws DifferentCompanyException
     */
    public function execute(PublishAnalysisInput $input): void
    {
        $analysis = Analysis::query()
            ->where('id', $input->analysisId)
            ->where('user_id', $input->userId)
            ->firstOrFail();

        if ($analysis->published_at !== null) {
            throw new AnalysisAlreadyPublishedException();
        }

        $viewer = User::findOrFail($input->viewerId);
        $owner  = User::findOrFail($input->userId);

        if ($viewer->company_id !== $owner->company_id) {
            throw new DifferentCompanyException();
        }

        $analysis->update([
            'viewer_id'    => $input->viewerId,
            'published_at' => now(),
        ]);
    }
}
