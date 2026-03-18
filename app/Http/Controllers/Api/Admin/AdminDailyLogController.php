<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Services\AuditLogger;
use App\Services\EncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminDailyLogController extends Controller
{
    /**
     * 全ユーザーの生ログ一覧を返す（復号して返す）。
     * 閲覧した事実を audit_logs に記録する。
     */
    public function index(
        Request $request,
        EncryptionService $encryption,
        AuditLogger $audit,
    ): JsonResponse {
        $logs = DailyLog::withTrashed()
            ->with([
                'user'     => fn ($query) => $query->withTrashed()->select('id', 'name'),
                'question' => fn ($query) => $query->withTrashed()->select('id', 'content'),
            ])
            ->get()
            ->map(fn (DailyLog $log): array => [
                'id'          => $log->id,
                'user'        => $log->user
                    ? ['id' => $log->user->id, 'name' => $log->user->name]
                    : null,
                'question'    => $log->question
                    ? ['id' => $log->question->id, 'content' => $log->question->content]
                    : null,
                'answer_text' => $encryption->decrypt($log->answer_text),
                'target_date' => $log->target_date->toDateString(),
                'deleted_at'  => $log->deleted_at?->toIso8601String(),
            ]);

        $audit->log(
            adminId: $request->user()->id,
            action:  'view_daily_logs',
        );

        return response()->json(['data' => $logs]);
    }

    /**
     * 生ログを論理削除する。
     * 削除した事実を audit_logs に記録する。
     */
    public function destroy(Request $request, int $id, AuditLogger $audit): JsonResponse
    {
        $log = DailyLog::findOrFail($id);
        $log->delete();

        $audit->log(
            adminId:    $request->user()->id,
            action:     'delete_daily_log',
            targetType: 'DailyLog',
            targetId:   $log->id,
            metadata:   ['user_id' => $log->user_id],
        );

        return response()->json(null, 204);
    }
}
