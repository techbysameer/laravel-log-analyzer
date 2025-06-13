<?php

namespace App\Providers;

use App\Services\OpenAIService;
use App\Services\LogParserService;
use Illuminate\Support\ServiceProvider;
use App\Services\PatternRecognitionService;

class LogAnalyzerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PatternRecognitionService::class);
        $this->app->singleton(OpenAIService::class);
        $this->app->singleton(LogParserService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
