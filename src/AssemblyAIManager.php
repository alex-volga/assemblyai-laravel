<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel;

use AssemblyAI\AssemblyAIClient;
use AssemblyAI\Config;
use AssemblyAI\Parameters\TranscriptParams;
use AssemblyAI\Resources\FileResource;
use AssemblyAI\Resources\LemurResource;
use AssemblyAI\Resources\RealtimeResource;
use AssemblyAI\Resources\TranscriptResource;
use AssemblyAI\ValueObjects\Transcript;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Manages AssemblyAI client instances and provides connection management.
 */
class AssemblyAIManager
{
    /**
     * Cache of resolved client instances.
     *
     * @var array<string, AssemblyAIClient>
     */
    protected array $clients = [];

    public function __construct(
        protected readonly Container $container,
    ) {
    }

    /**
     * Get a client instance for a given connection/profile.
     */
    public function connection(?string $name = null): AssemblyAIClient
    {
        $name ??= 'default';

        return $this->clients[$name] ??= $this->resolve($name);
    }

    /**
     * Get the default client instance.
     */
    public function client(): AssemblyAIClient
    {
        return $this->connection();
    }

    /**
     * Create a new custom AssemblyAI client on the fly.
     *
     * @param array<string, mixed> $overrideConfig
     */
    public function build(array $overrideConfig = []): AssemblyAIClient
    {
        /** @var ConfigRepository $configRepo */
        $configRepo = $this->container->make('config');

        /** @var array<string, mixed> $baseConfig */
        $baseConfig = $configRepo->get('assemblyai', []);
        $merged = array_merge($baseConfig, $overrideConfig);

        return $this->createClient($merged);
    }

    /**
     * Resolve the given client instance.
     */
    protected function resolve(string $name): AssemblyAIClient
    {
        /** @var ConfigRepository $configRepo */
        $configRepo = $this->container->make('config');

        /** @var array<string, mixed> $config */
        $config = $configRepo->get('assemblyai', []);

        if ($name !== 'default' && isset($config['connections'][$name]) && is_array($config['connections'][$name])) {
            $config = array_merge($config, $config['connections'][$name]);
        }

        return $this->createClient($config);
    }

    /**
     * Create an AssemblyAIClient from a configuration array.
     *
     * @param array<string, mixed> $config
     */
    protected function createClient(array $config): AssemblyAIClient
    {
        $apiKey = (string) ($config['api_key'] ?? '');

        if (trim($apiKey) === '') {
            throw new InvalidArgumentException(
                'AssemblyAI API key is missing. Set the ASSEMBLYAI_API_KEY environment variable or publish the config file.'
            );
        }

        $headers = isset($config['headers']) && is_array($config['headers'])
            ? array_map(strval(...), $config['headers'])
            : [];

        $clientConfig = new Config(
            apiKey: $apiKey,
            baseUrl: (string) ($config['base_url'] ?? Config::DEFAULT_BASE_URL),
            timeout: (float) ($config['timeout'] ?? Config::DEFAULT_TIMEOUT),
            pollInterval: (int) ($config['poll_interval'] ?? Config::DEFAULT_POLL_INTERVAL),
            pollTimeout: (int) ($config['poll_timeout'] ?? Config::DEFAULT_POLL_TIMEOUT),
            additionalHeaders: $headers,
        );

        return new AssemblyAIClient($clientConfig);
    }

    /**
     * Access the Transcripts resource.
     */
    public function transcripts(): TranscriptResource
    {
        return $this->client()->transcripts();
    }

    /**
     * Access the Files resource.
     */
    public function files(): FileResource
    {
        return $this->client()->files();
    }

    /**
     * Access the LeMUR resource.
     */
    public function lemur(): LemurResource
    {
        return $this->client()->lemur();
    }

    /**
     * Access the Realtime resource.
     */
    public function realtime(): RealtimeResource
    {
        return $this->client()->realtime();
    }

    /**
     * Upload an audio file or stream.
     *
     * @param mixed $file
     */
    public function upload(mixed $file): string
    {
        return $this->client()->upload($file);
    }

    /**
     * Transcribe an audio source, poll until completion, and return the Transcript.
     *
     * @param mixed $audio
     * @param TranscriptParams|array<string, mixed>|null $params
     * @param (callable(Transcript): void)|null $onPoll
     */
    public function transcribe(
        mixed $audio,
        TranscriptParams|array|null $params = null,
        ?int $pollInterval = null,
        ?int $timeout = null,
        ?callable $onPoll = null,
    ): Transcript {
        return $this->client()->transcribe(
            audio: $audio,
            params: $params,
            pollInterval: $pollInterval,
            timeout: $timeout,
            onPoll: $onPoll,
        );
    }

    /**
     * Dynamically pass missing methods to the default client.
     *
     * @param array<int, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->client()->{$method}(...$parameters);
    }
}
