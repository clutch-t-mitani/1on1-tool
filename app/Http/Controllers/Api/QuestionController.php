<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\UseCases\Question\GetQuestionsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class QuestionController extends Controller
{
    public function index(
        Request $request,
        GetQuestionsUseCase $useCase,
    ): AnonymousResourceCollection {
        $questions = $useCase->execute($request->user()->company_id);

        return QuestionResource::collection($questions);
    }
}
