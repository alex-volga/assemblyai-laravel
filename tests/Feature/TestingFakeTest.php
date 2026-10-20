<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Feature;

use AssemblyAI\Enums\TranscriptStatus;
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Laravel\Testing\AssemblyAIFake;
use AssemblyAI\Laravel\Tests\TestCase;
use AssemblyAI\ValueObjects\Transcript;
use PHPUnit\Framework\AssertionFailedError;

class TestingFakeTest extends TestCase
{
    public function test_fake_swaps_facade(): void
    {
        $fake = AssemblyAI::fake();

        $this->assertInstanceOf(AssemblyAIFake::class, $fake);
    }

    public function test_fake_transcribes_and_records(): void
    {
        AssemblyAI::fake();

        AssemblyAI::assertNothingTranscribed();

        $transcript = AssemblyAI::transcribe('https://example.com/audio.mp3', [
            'speaker_labels' => true,
        ]);

        $this->assertInstanceOf(Transcript::class, $transcript);
        $this->assertSame(TranscriptStatus::Completed, $transcript->status);
        $this->assertSame('https://example.com/audio.mp3', $transcript->audioUrl);

        AssemblyAI::assertTranscribed('https://example.com/audio.mp3');
        AssemblyAI::assertTranscribed(function ($audio, $params) {
            return $audio === 'https://example.com/audio.mp3'
                && is_array($params)
                && ($params['speaker_labels'] ?? false) === true;
        });

        AssemblyAI::assertNotTranscribed('https://example.com/other.mp3');
    }

    public function test_fake_uploads_and_records(): void
    {
        AssemblyAI::fake();

        AssemblyAI::assertNothingUploaded();

        $uploadedUrl = AssemblyAI::upload('fake-file-content');

        $this->assertStringStartsWith('https://cdn.assemblyai.com/upload/', $uploadedUrl);
        AssemblyAI::assertUploaded('fake-file-content');
        AssemblyAI::assertUploaded(fn ($file) => $file === 'fake-file-content', 1);
    }

    public function test_fake_uses_queued_responses(): void
    {
        $customTranscript = Transcript::fromArray([
            'id' => 'custom-id-999',
            'status' => 'completed',
            'audio_url' => 'https://example.com/custom.mp3',
            'text' => 'Custom queued text',
        ]);

        AssemblyAI::fake([$customTranscript]);

        $result = AssemblyAI::transcribe('https://example.com/custom.mp3');

        $this->assertSame('custom-id-999', $result->id);
        $this->assertSame('Custom queued text', $result->text);
    }

    public function test_assert_transcribed_fails_when_not_requested(): void
    {
        AssemblyAI::fake();

        $this->expectException(AssertionFailedError::class);
        AssemblyAI::assertTranscribed('https://example.com/uncalled.mp3');
    }
}
