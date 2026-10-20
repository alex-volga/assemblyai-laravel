<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests;

use AssemblyAI\Laravel\AssemblyAIServiceProvider;
use AssemblyAI\Laravel\Facades\AssemblyAI;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AssemblyAIServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'AssemblyAI' => AssemblyAI::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('assemblyai.api_key', 'test_api_key_secret');
        $app['config']->set('assemblyai.base_url', 'https://api.assemblyai.com');
        $app['config']->set('assemblyai.timeout', 45.0);
        $app['config']->set('assemblyai.poll_interval', 2);
        $app['config']->set('assemblyai.poll_timeout', 120);
        $app['config']->set('assemblyai.webhook.secret', 'test-webhook-secret-token');
    }

    /**
     * @return \Illuminate\Foundation\Application
     */
    public function getApplication(): \Illuminate\Foundation\Application
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        return $app;
    }
}
