<?php

namespace App\Providers;

use App\Services\GptSummaryService;
use App\Services\WhisperService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhisperService::class, fn (): WhisperService => new WhisperService(
            apiKey: (string) config('services.openai.api_key'),
        ));

        $this->app->singleton(GptSummaryService::class, fn (): GptSummaryService => new GptSummaryService(
            apiKey: (string) config('services.openai.api_key'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
