<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ViewerAnalysisController extends Controller
{
    /**
     * 自分宛てに公開された要約一覧を返す（7日以内のみ）。
     */
    public function index(Request $request): JsonResponse
    {
        $analyses = Analysis::query()
            ->published()
            ->visibleToViewer($request->user()->id)
            ->notExpired()
            ->with('user:id,name')
            ->get()
            ->map(fn (Analysis $analysis): array => [
                'id'              => $analysis->id,
                'user'            => ['id' => $analysis->user->id, 'name' => $analysis->user->name],
                'summary_content' => $analysis->summary_content,
                'annotation_text' => $analysis->annotation_text,
                'published_at'    => $analysis->published_at->toIso8601String(),
            ]);

        return response()->json(['data' => $analyses]);
    }

    /**
     * 要約の詳細を返す（自分宛て・7日以内のみ）。
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $analysis = Analysis::query()
            ->published()
            ->visibleToViewer($request->user()->id)
            ->notExpired()
            ->with('user:id,name')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'id'              => $analysis->id,
            'user'            => ['id' => $analysis->user->id, 'name' => $analysis->user->name],
            'summary_content' => $analysis->summary_content,
            'annotation_text' => $analysis->annotation_text,
            'published_at'    => $analysis->published_at->toIso8601String(),
        ]);
    }
}
