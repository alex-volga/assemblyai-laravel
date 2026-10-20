<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Testing;

use AssemblyAI\Enums\TranscriptStatus;
use AssemblyAI\Parameters\TranscriptParams;
use AssemblyAI\ValueObjects\Transcript;
use Closure;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Test double for AssemblyAI client in Laravel tests.
 */
class AssemblyAIFake
{
    /**
     * @var list<array{
     *     audio: mixed,
     *     params: TranscriptParams|array<string, mixed>|null,
     *     pollInterval: ?int,
     *     timeout: ?int,
     *     result: Transcript
     * }>
     */
    protected array $transcriptions = [];

    /**
     * @var list<array{file: mixed, result: string}>
     */
    protected array $uploads = [];

    /**
     * Pre-configured queue of transcripts or callbacks to return.
     *
     * @var array<int, Transcript|Closure>
     */
    protected array $transcriptQueue = [];

    /**
     * Pre-configured queue of upload URLs or callbacks to return.
     *
     * @var array<int, string|Closure>
     */
    protected array $uploadQueue = [];

    /**
     * @param array<int, Transcript|Closure|string> $queue
     */
    public function __construct(array $queue = [])
    {
        foreach ($queue as $item) {
            if ($item instanceof Transcript || ($item instanceof Closure)) {
                $this->transcriptQueue[] = $item;
            } elseif (is_string($item)) {
                $this->uploadQueue[] = $item;
            }
        }
    }

    /**
     * Queue a mock Transcript to be returned by transcribe().
     */
    public function queueTranscript(Transcript|Closure $transcript): self
    {
        $this->transcriptQueue[] = $transcript;

        return $this;
    }

    /**
     * Queue a mock upload URL to be returned by upload().
     */
    public function queueUpload(string|Closure $url): self
    {
        $this->uploadQueue[] = $url;

        return $this;
    }

    /**
     * Transcribe an audio source (mocked).
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
        $result = $this->resolveNextTranscript($audio, $params);

        if ($onPoll !== null) {
            $onPoll($result);
        }

        $this->transcriptions[] = [
            'audio' => $audio,
            'params' => $params,
            'pollInterval' => $pollInterval,
            'timeout' => $timeout,
            'result' => $result,
        ];

        return $result;
    }

    /**
     * Upload an audio file or stream (mocked).
     *
     * @param mixed $file
     */
    public function upload(mixed $file): string
    {
        $result = $this->resolveNextUpload($file);

        $this->uploads[] = [
            'file' => $file,
            'result' => $result,
        ];

        return $result;
    }

    /**
     * Resolve the next Transcript from the queue or create a default completed one.
     *
     * @param mixed $audio
     * @param TranscriptParams|array<string, mixed>|null $params
     */
    protected function resolveNextTranscript(mixed $audio, TranscriptParams|array|null $params): Transcript
    {
        if (!empty($this->transcriptQueue)) {
            $next = array_shift($this->transcriptQueue);

            if ($next instanceof Closure) {
                return $next($audio, $params);
            }

            return $next;
        }

        $audioUrl = is_string($audio) ? $audio : 'https://cdn.assemblyai.com/mock-audio.mp3';

        return Transcript::fromArray([
            'id' => 'mock_transcript_' . bin2hex(random_bytes(6)),
            'status' => TranscriptStatus::Completed->value,
            'audio_url' => $audioUrl,
            'text' => 'This is a mocked AssemblyAI transcription result.',
            'confidence' => 0.98,
            'audio_duration' => 12.5,
        ]);
    }

    /**
     * Resolve the next upload URL from the queue or return a generated mock URL.
     *
     * @param mixed $file
     */
    protected function resolveNextUpload(mixed $file): string
    {
        if (!empty($this->uploadQueue)) {
            $next = array_shift($this->uploadQueue);

            if ($next instanceof Closure) {
                return $next($file);
            }

            return $next;
        }

        return 'https://cdn.assemblyai.com/upload/mock_' . bin2hex(random_bytes(8));
    }

    /**
     * Assert that a transcription was requested.
     *
     * @param (callable(mixed, TranscriptParams|array<string, mixed>|null): bool)|string|null $callback
     */
    public function assertTranscribed(callable|string|null $callback = null, ?int $expectedCount = null): void
    {
        if (is_string($callback)) {
            $url = $callback;
            $callback = fn ($audio) => $audio === $url;
        }

        $matching = $this->filterTranscriptions($callback);

        if ($expectedCount !== null) {
            PHPUnit::assertCount(
                $expectedCount,
                $matching,
                sprintf('Expected %d transcriptions matching the filter, found %d.', $expectedCount, count($matching))
            );

            return;
        }

        PHPUnit::assertTrue(
            count($matching) > 0,
            'The expected transcription was not requested.'
        );
    }

    /**
     * Assert that a transcription was not requested.
     *
     * @param (callable(mixed, TranscriptParams|array<string, mixed>|null): bool)|string|null $callback
     */
    public function assertNotTranscribed(callable|string|null $callback = null): void
    {
        if (is_string($callback)) {
            $url = $callback;
            $callback = fn ($audio) => $audio === $url;
        }

        $matching = $this->filterTranscriptions($callback);

        PHPUnit::assertCount(
            0,
            $matching,
            sprintf('Expected 0 matching transcriptions, but found %d.', count($matching))
        );
    }

    /**
     * Assert that no transcriptions were requested.
     */
    public function assertNothingTranscribed(): void
    {
        PHPUnit::assertEmpty(
            $this->transcriptions,
            sprintf('Expected no transcriptions, but %d were recorded.', count($this->transcriptions))
        );
    }

    /**
     * Assert that a file was uploaded.
     *
     * @param (callable(mixed): bool)|mixed|null $callback
     */
    public function assertUploaded(mixed $callback = null, ?int $expectedCount = null): void
    {
        if ($callback !== null && !($callback instanceof Closure)) {
            $expectedFile = $callback;
            $callback = fn ($file) => $file === $expectedFile;
        }

        $matching = $this->filterUploads($callback);

        if ($expectedCount !== null) {
            PHPUnit::assertCount(
                $expectedCount,
                $matching,
                sprintf('Expected %d uploads matching the filter, found %d.', $expectedCount, count($matching))
            );

            return;
        }

        PHPUnit::assertTrue(
            count($matching) > 0,
            'The expected file upload was not performed.'
        );
    }

    /**
     * Assert that no files were uploaded.
     */
    public function assertNothingUploaded(): void
    {
        PHPUnit::assertEmpty(
            $this->uploads,
            sprintf('Expected no file uploads, but %d were recorded.', count($this->uploads))
        );
    }

    /**
     * Total number of recorded transcriptions.
     */
    public function recordedTranscriptionsCount(): int
    {
        return count($this->transcriptions);
    }

    /**
     * Total number of recorded uploads.
     */
    public function recordedUploadsCount(): int
    {
        return count($this->uploads);
    }

    /**
     * Filter recorded transcriptions by a callback.
     *
     * @param (callable(mixed, TranscriptParams|array<string, mixed>|null): bool)|null $callback
     * @return list<array{audio: mixed, params: mixed, pollInterval: ?int, timeout: ?int, result: Transcript}>
     */
    protected function filterTranscriptions(?callable $callback = null): array
    {
        if ($callback === null) {
            return $this->transcriptions;
        }

        return array_values(array_filter(
            $this->transcriptions,
            fn (array $record) => $callback($record['audio'], $record['params'])
        ));
    }

    /**
     * Filter recorded uploads by a callback.
     *
     * @param (callable(mixed): bool)|null $callback
     * @return list<array{file: mixed, result: string}>
     */
    protected function filterUploads(?callable $callback = null): array
    {
        if ($callback === null) {
            return $this->uploads;
        }

        return array_values(array_filter(
            $this->uploads,
            fn (array $record) => $callback($record['file'])
        ));
    }
}
