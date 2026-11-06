<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Feature;

use AssemblyAI\Enums\SpeechModel;
use AssemblyAI\Enums\SummaryEffort;
use AssemblyAI\Enums\SummaryType;
use AssemblyAI\Enums\TranscriptStatus;
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Laravel\Testing\AssemblyAIFake;
use AssemblyAI\Laravel\Tests\TestCase;
use AssemblyAI\Parameters\TranscriptParams;
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

    public function test_fake_transcribes_with_transcript_params_builder_and_speech_understanding(): void
    {
        AssemblyAI::fake();

        $params = TranscriptParams::create('https://example.com/audio.mp3')
            ->withSpeechModels([SpeechModel::Universal35Pro, SpeechModel::Universal2])
            ->withAutoChapters(true)
            ->withSummarization(SummaryType::Bullets, effort: SummaryEffort::Low);

        $transcript = AssemblyAI::transcribe('https://example.com/audio.mp3', $params);

        $this->assertInstanceOf(Transcript::class, $transcript);
        AssemblyAI::assertTranscribed('https://example.com/audio.mp3');
        AssemblyAI::assertTranscribed(function ($audio, $recordedParams) {
            if (!$recordedParams instanceof TranscriptParams) {
                return false;
            }

            $array = $recordedParams->toArray();

            return $audio === 'https://example.com/audio.mp3'
                && $array['speech_models'] === ['universal-3-5-pro', 'universal-2']
                && isset($array['speech_understanding']['request']['summarization'])
                && $array['speech_understanding']['request']['summarization']['summary_type'] === 'bullets'
                && $array['speech_understanding']['request']['summarization']['effort'] === 'low';
        });
    }

    public function test_fake_supports_queued_transcript_with_speech_understanding_and_model_properties(): void
    {
        $customTranscript = Transcript::fromArray([
            'id' => 'custom-id-modern',
            'status' => 'completed',
            'audio_url' => 'https://example.com/modern.mp3',
            'text' => 'Modern transcript text.',
            'speech_model_used' => 'universal-3-5-pro',
            'speech_models' => ['universal-3-5-pro', 'universal-2'],
            'speech_understanding' => [
                'request' => [
                    'summarization' => [
                        'summary_type' => 'bullets',
                        'effort' => 'low',
                    ],
                ],
                'response' => [
                    'summarization' => [
                        'summary' => [
                            [
                                'headline' => 'Introduction',
                                'summary' => 'Intro summary text',
                                'gist' => 'Intro gist',
                                'start' => 0,
                                'end' => 1500,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        AssemblyAI::fake([$customTranscript]);

        $result = AssemblyAI::transcribe('https://example.com/modern.mp3');

        $this->assertSame('custom-id-modern', $result->id);
        $this->assertSame('universal-3-5-pro', $result->speechModelUsed);
        $this->assertSame(['universal-3-5-pro', 'universal-2'], $result->speechModels);
        $this->assertNotNull($result->speechUnderstanding);
        $this->assertCount(1, $result->chapters);
        $this->assertSame('Introduction', $result->chapters[0]->headline);
        $this->assertSame('Intro summary text', $result->chapters[0]->summary);
    }
}
