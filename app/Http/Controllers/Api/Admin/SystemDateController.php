<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemDateRequest;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class SystemDateController extends Controller
{
    /**
     * 現在のシステム日付を返す。
     */
    public function show(): JsonResponse
    {
        $masterDate = SystemSetting::getMasterDate();
        $isOverridden = SystemSetting::where('key', 'master_date_override')->exists();

        return response()->json([
            'date'         => $masterDate->toDateString(),
            'is_overridden' => $isOverridden,
        ]);
    }

    /**
     * システム日付を上書きする。
     */
    public function update(
        UpdateSystemDateRequest $request,
        AuditLogger $audit,
    ): JsonResponse {
        $before = SystemSetting::getMasterDate()->toDateString();
        $after  = (string) $request->validated('date');

        SystemSetting::setMasterDate(Carbon::parse($after));

        $audit->log(
            adminId:    $request->user()->id,
            action:     'update_system_date',
            targetType: 'SystemSetting',
            metadata:   ['before' => $before, 'after' => $after],
        );

        return response()->json(['date' => $after]);
    }

    /**
     * システム日付の上書きを解除してサーバー時刻に戻す。
     */
    public function destroy(Request $request, AuditLogger $audit): JsonResponse
    {
        $before = SystemSetting::getMasterDate()->toDateString();

        SystemSetting::clearMasterDate();

        $audit->log(
            adminId:    $request->user()->id,
            action:     'clear_system_date',
            targetType: 'SystemSetting',
            metadata:   ['before' => $before],
        );

        return response()->json(null, 204);
    }
}
