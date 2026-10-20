<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Feature;

use AssemblyAI\Laravel\Events\TranscriptCompleted;
use AssemblyAI\Laravel\Events\TranscriptError;
use AssemblyAI\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class WebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::assemblyAiWebhooks();
    }

    public function test_webhook_dispatches_transcript_completed_event(): void
    {
        Event::fake([TranscriptCompleted::class, TranscriptError::class]);

        $response = $this->postJson('/assemblyai/webhook', [
            'transcript_id' => 'tx_12345abc',
            'status' => 'completed',
        ], [
            'X-Webhook-Secret' => 'test-webhook-secret-token',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Event::assertDispatched(TranscriptCompleted::class, function (TranscriptCompleted $event) {
            return $event->transcriptId === 'tx_12345abc'
                && ($event->payload['status'] ?? null) === 'completed';
        });

        Event::assertNotDispatched(TranscriptError::class);
    }

    public function test_webhook_dispatches_transcript_error_event(): void
    {
        Event::fake([TranscriptCompleted::class, TranscriptError::class]);

        $response = $this->postJson('/assemblyai/webhook', [
            'transcript_id' => 'tx_error_678',
            'status' => 'error',
        ], [
            'X-Webhook-Secret' => 'test-webhook-secret-token',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Event::assertDispatched(TranscriptError::class, function (TranscriptError $event) {
            return $event->transcriptId === 'tx_error_678'
                && ($event->payload['status'] ?? null) === 'error';
        });

        Event::assertNotDispatched(TranscriptCompleted::class);
    }

    public function test_webhook_aborts_with_invalid_secret(): void
    {
        Event::fake([TranscriptCompleted::class, TranscriptError::class]);

        $response = $this->postJson('/assemblyai/webhook', [
            'transcript_id' => 'tx_123',
            'status' => 'completed',
        ], [
            'X-Webhook-Secret' => 'wrong-secret',
        ]);

        $response->assertUnauthorized();
        Event::assertNotDispatched(TranscriptCompleted::class);
        Event::assertNotDispatched(TranscriptError::class);
    }

    public function test_webhook_returns_bad_request_on_invalid_payload(): void
    {
        $response = $this->postJson('/assemblyai/webhook', [
            'foo' => 'bar',
        ], [
            'X-Webhook-Secret' => 'test-webhook-secret-token',
        ]);

        $response->assertBadRequest();
    }
}
