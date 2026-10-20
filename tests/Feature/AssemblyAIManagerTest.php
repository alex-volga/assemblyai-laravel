<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Feature;

use AssemblyAI\AssemblyAIClient;
use AssemblyAI\Laravel\AssemblyAIManager;
use AssemblyAI\Laravel\Tests\TestCase;

class AssemblyAIManagerTest extends TestCase
{
    public function test_manager_creates_custom_connection(): void
    {
        $app = $this->getApplication();
        $app['config']->set('assemblyai.connections.eu', [
            'api_key' => 'eu-secret-key',
            'base_url' => 'https://api.eu.assemblyai.com',
            'timeout' => 90.0,
        ]);

        /** @var AssemblyAIManager $manager */
        $manager = $app->make('assemblyai');

        $euClient = $manager->connection('eu');

        $this->assertInstanceOf(AssemblyAIClient::class, $euClient);
        $this->assertSame('eu-secret-key', $euClient->getConfig()->apiKey);
        $this->assertSame('https://api.eu.assemblyai.com', $euClient->getConfig()->baseUrl);
        $this->assertSame(90.0, $euClient->getConfig()->timeout);
    }

    public function test_manager_builds_client_on_the_fly(): void
    {
        $app = $this->getApplication();
        /** @var AssemblyAIManager $manager */
        $manager = $app->make('assemblyai');

        $customClient = $manager->build([
            'api_key' => 'custom-api-key',
            'timeout' => 12.0,
        ]);

        $this->assertInstanceOf(AssemblyAIClient::class, $customClient);
        $this->assertSame('custom-api-key', $customClient->getConfig()->apiKey);
        $this->assertSame(12.0, $customClient->getConfig()->timeout);
    }
}
