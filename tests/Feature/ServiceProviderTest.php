<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Feature;

use AssemblyAI\AssemblyAIClient;
use AssemblyAI\Config;
use AssemblyAI\Laravel\AssemblyAIManager;
use AssemblyAI\Laravel\Tests\TestCase;
use InvalidArgumentException;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_manager_singleton(): void
    {
        $app = $this->getApplication();
        $manager1 = $app->make('assemblyai');
        $manager2 = $app->make(AssemblyAIManager::class);

        $this->assertInstanceOf(AssemblyAIManager::class, $manager1);
        $this->assertSame($manager1, $manager2);
    }

    public function test_it_binds_assemblyai_client(): void
    {
        $app = $this->getApplication();
        $client = $app->make(AssemblyAIClient::class);

        $this->assertInstanceOf(AssemblyAIClient::class, $client);
        $this->assertSame('test_api_key_secret', $client->getConfig()->apiKey);
        $this->assertSame(45.0, $client->getConfig()->timeout);
        $this->assertSame(2, $client->getConfig()->pollInterval);
        $this->assertSame(120, $client->getConfig()->pollTimeout);
    }

    public function test_it_binds_config(): void
    {
        $app = $this->getApplication();
        $config = $app->make(Config::class);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertSame('test_api_key_secret', $config->apiKey);
    }

    public function test_it_throws_exception_when_api_key_is_missing(): void
    {
        $app = $this->getApplication();
        $app['config']->set('assemblyai.api_key', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AssemblyAI API key is missing.');

        $app->make(AssemblyAIClient::class);
    }
}
