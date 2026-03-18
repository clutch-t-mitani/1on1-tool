<?php

namespace App\Http\Controllers\Api;

use App\DTOs\DailyLog\StoreTextLogInput;
use App\Exceptions\DuplicateLogException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailyLog\StoreTextLogRequest;
use App\Http\Requests\DailyLog\StoreVoiceLogRequest;
use App\Jobs\ProcessVoiceLogJob;
use App\Models\SystemSetting;
use App\UseCases\DailyLog\GetDailyLogStatusUseCase;
use App\UseCases\DailyLog\StoreTextLogUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DailyLogController extends Controller
{
    /**
     * テキストで日次ログを保存する。
     *
     * @throws ValidationException
     */
    public function storeText(
        StoreTextLogRequest $request,
        StoreTextLogUseCase $useCase,
    ): JsonResponse {
        $masterNow  = SystemSetting::getMasterDate();
        $targetDate = $masterNow->hour < 12
            ? $masterNow->startOfDay()->toDateString()
            : $masterNow->copy()->addDay()->startOfDay()->toDateString();

        try {
            $useCase->execute(new StoreTextLogInput(
                userId:     $request->user()->id,
                questionId: (int) $request->validated('question_id'),
                answerText: (string) $request->validated('answer_text'),
                targetDate: $targetDate,
            ));
        } catch (DuplicateLogException $e) {
            throw ValidationException::withMessages([
                'question_id' => [$e->getMessage()],
            ]);
        }

        return response()->json(null, 201);
    }

    /**
     * 音声ファイルをS3に一時保存し、文字起こしJobをキューに投入する。
     */
    public function storeVoice(
        StoreVoiceLogRequest $request,
    ): JsonResponse {
        $masterNow  = SystemSetting::getMasterDate();
        $targetDate = $masterNow->hour < 12
            ? $masterNow->toDateString()
            : $masterNow->copy()->addDay()->toDateString();

        $file      = $request->file('audio');
        $extension = $file->getClientOriginalExtension();
        $s3Path    = 'temp/audio/' . Str::uuid() . '.' . $extension;

        Storage::disk('s3')->put($s3Path, $file->get());

        ProcessVoiceLogJob::dispatch(
            userId:     $request->user()->id,
            questionId: (int) $request->validated('question_id'),
            s3Path:     $s3Path,
            targetDate: $targetDate,
        );

        return response()->json(null, 202);
    }

    /**
     * 当日の入力状況を返す。
     */
    public function status(
        Request $request,
        GetDailyLogStatusUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute($request->user()->id);

        return response()->json($result);
    }
}
