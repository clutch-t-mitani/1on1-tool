<?php

use App\Http\Controllers\Api\Admin\CompanyController;
use App\Http\Controllers\Api\Admin\SystemDateController;
use App\Http\Controllers\Api\Admin\AdminDailyLogController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\DailyLogController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ViewerAnalysisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => response()->json([
        'user' => [
            'id'         => $request->user()->id,
            'name'       => $request->user()->name,
            'email'      => $request->user()->email,
            'is_admin'   => $request->user()->is_admin,
            'company_id' => $request->user()->company_id,
        ],
    ]));

    Route::post('/logout', [LoginController::class, 'destroy']);

    // 日次ログ
    Route::post('/daily-logs/text', [DailyLogController::class, 'storeText']);
    Route::post('/daily-logs/voice', [DailyLogController::class, 'storeVoice']);
    Route::get('/daily-logs/status', [DailyLogController::class, 'status']);

    // AI要約
    Route::post('/analyses', [AnalysisController::class, 'store']);
    Route::get('/analyses/{id}', [AnalysisController::class, 'show']);
    Route::patch('/analyses/{id}/annotation', [AnalysisController::class, 'saveAnnotation']);
    Route::post('/analyses/{id}/publish', [AnalysisController::class, 'publish']);

    // 上司閲覧
    Route::get('/viewer/analyses', [ViewerAnalysisController::class, 'index']);
    Route::get('/viewer/analyses/{id}', [ViewerAnalysisController::class, 'show']);

    Route::middleware('admin')->prefix('admin')->group(function (): void {
        // ユーザー・会社管理
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);

        // 生ログ・要約管理（タスク11で実装）
        Route::get('/daily-logs', [AdminDailyLogController::class, 'index']);
        Route::delete('/daily-logs/{id}', [AdminDailyLogController::class, 'destroy']);

        // システム日付設定（タスク12で実装）
        Route::get('/system-date', [SystemDateController::class, 'show']);
        Route::put('/system-date', [SystemDateController::class, 'update']);
        Route::delete('/system-date', [SystemDateController::class, 'destroy']);
    });
});
