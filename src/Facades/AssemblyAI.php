<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Facades;

use AssemblyAI\AssemblyAIClient;
use AssemblyAI\Config;
use AssemblyAI\Laravel\Testing\AssemblyAIFake;
use AssemblyAI\Parameters\TranscriptParams;
use AssemblyAI\Resources\FileResource;
use AssemblyAI\Resources\LemurResource;
use AssemblyAI\Resources\RealtimeResource;
use AssemblyAI\Resources\TranscriptResource;
use AssemblyAI\ValueObjects\Transcript;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AssemblyAIClient connection(?string $name = null)
 * @method static AssemblyAIClient client()
 * @method static AssemblyAIClient build(array<string, mixed> $overrideConfig = [])
 * @method static TranscriptResource transcripts()
 * @method static FileResource files()
 * @method static LemurResource lemur()
 * @method static RealtimeResource realtime()
 * @method static string upload(mixed $file)
 * @method static Transcript transcribe(mixed $audio, TranscriptParams|array<string, mixed>|null $params = null, ?int $pollInterval = null, ?int $timeout = null, ?callable $onPoll = null)
 * @method static Config getConfig()
 * @method static void assertTranscribed(callable|string|null $callback = null, ?int $expectedCount = null)
 * @method static void assertNotTranscribed(callable|string|null $callback = null)
 * @method static void assertNothingTranscribed()
 * @method static void assertUploaded(mixed $callback = null, ?int $expectedCount = null)
 * @method static void assertNothingUploaded()
 *
 * @see \AssemblyAI\Laravel\AssemblyAIManager
 * @see \AssemblyAI\AssemblyAIClient
 */
class AssemblyAI extends Facade
{
    /**
     * Replace the bound instance with a fake for testing.
     *
     * @param array<int, Transcript|Closure|string> $queue
     */
    public static function fake(array $queue = []): AssemblyAIFake
    {
        $fake = new AssemblyAIFake($queue);

        static::swap($fake);

        return $fake;
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'assemblyai';
    }
}
