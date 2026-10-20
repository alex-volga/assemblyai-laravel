<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel;

use AssemblyAI\AssemblyAIClient;
use AssemblyAI\Config;
use AssemblyAI\Laravel\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AssemblyAIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/assemblyai.php', 'assemblyai');

        $this->app->singleton(AssemblyAIManager::class, function ($app) {
            return new AssemblyAIManager($app);
        });

        $this->app->singleton('assemblyai', function ($app) {
            return $app->make(AssemblyAIManager::class);
        });

        $this->app->bind(AssemblyAIClient::class, function ($app) {
            return $app->make('assemblyai')->client();
        });

        $this->app->bind(Config::class, function ($app) {
            return $app->make(AssemblyAIClient::class)->getConfig();
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/assemblyai.php' => $this->app->configPath('assemblyai.php'),
            ], 'assemblyai-config');
        }

        $this->registerRouteMacro();
    }

    /**
     * Register the Route::assemblyAiWebhooks() macro.
     */
    protected function registerRouteMacro(): void
    {
        Route::macro('assemblyAiWebhooks', function (string $url = 'assemblyai/webhook') {
            return Route::post($url, WebhookController::class)->name('assemblyai.webhook');
        });
    }
}
