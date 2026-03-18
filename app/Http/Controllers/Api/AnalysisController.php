<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Analysis\GenerateAnalysisInput;
use App\DTOs\Analysis\PublishAnalysisInput;
use App\Exceptions\AnalysisAlreadyInProgressException;
use App\Exceptions\AnalysisAlreadyPublishedException;
use App\Exceptions\DifferentCompanyException;
use App\Exceptions\InsufficientLogsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analysis\PublishAnalysisRequest;
use App\Http\Requests\Analysis\SaveAnnotationRequest;
use App\Models\Analysis;
use App\UseCases\Analysis\GenerateAnalysisUseCase;
use App\UseCases\Analysis\PublishAnalysisUseCase;
use App\UseCases\Analysis\SaveAnnotationUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AnalysisController extends Controller
{
    /**
     * AI要約生成を開始する。
     *
     * @throws ValidationException
     */
    public function store(
        Request $request,
        GenerateAnalysisUseCase $useCase,
    ): JsonResponse {
        try {
            $analysis = $useCase->execute(new GenerateAnalysisInput(
                userId: $request->user()->id,
            ));
        } catch (InsufficientLogsException $e) {
            throw ValidationException::withMessages(['base' => [$e->getMessage()]]);
        } catch (AnalysisAlreadyInProgressException $e) {
            throw ValidationException::withMessages(['base' => [$e->getMessage()]]);
        }

        return response()->json(['analysis_id' => $analysis->id], 202);
    }

    /**
     * 要約の詳細を返す（本人のみ）。
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $analysis = Analysis::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'id'              => $analysis->id,
            'status'          => $analysis->status,
            'summary_content' => $analysis->summary_content,
            'annotation_text' => $analysis->annotation_text,
            'error_message'   => $analysis->error_message,
            'published_at'    => $analysis->published_at?->toIso8601String(),
            'viewer_id'       => $analysis->viewer_id,
        ]);
    }

    /**
     * 注釈を保存する（公開前のみ）。
     *
     * @throws ValidationException
     */
    public function saveAnnotation(
        SaveAnnotationRequest $request,
        int $id,
        SaveAnnotationUseCase $useCase,
    ): JsonResponse {
        try {
            $useCase->execute(
                analysisId:     $id,
                userId:         $request->user()->id,
                annotationText: $request->validated('annotation_text'),
            );
        } catch (AnalysisAlreadyPublishedException $e) {
            throw ValidationException::withMessages(['base' => [$e->getMessage()]]);
        }

        return response()->json(null, 200);
    }

    /**
     * 要約を上司に公開する。
     *
     * @throws ValidationException
     */
    public function publish(
        PublishAnalysisRequest $request,
        int $id,
        PublishAnalysisUseCase $useCase,
    ): JsonResponse {
        try {
            $useCase->execute(new PublishAnalysisInput(
                analysisId: $id,
                userId:     $request->user()->id,
                viewerId:   (int) $request->validated('viewer_id'),
            ));
        } catch (AnalysisAlreadyPublishedException $e) {
            throw ValidationException::withMessages(['base' => [$e->getMessage()]]);
        } catch (DifferentCompanyException $e) {
            throw ValidationException::withMessages(['viewer_id' => [$e->getMessage()]]);
        }

        return response()->json(null, 200);
    }
}
