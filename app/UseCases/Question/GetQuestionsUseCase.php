<?php

namespace App\UseCases\Question;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;

final class GetQuestionsUseCase
{
    /**
     * @return Collection<int, Question>
     */
    public function execute(int $companyId): Collection
    {
        return Question::query()
            ->where('company_id', $companyId)
            ->active()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
