<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Http\Controllers;

use AssemblyAI\Laravel\Events\TranscriptCompleted;
use AssemblyAI\Laravel\Events\TranscriptError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles incoming AssemblyAI webhook callbacks.
 */
class WebhookController
{
    /**
     * Handle the incoming AssemblyAI webhook request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->validateSecretHeader($request);

        /** @var string|null $transcriptId */
        $transcriptId = $request->input('transcript_id');

        /** @var string|null $status */
        $status = $request->input('status');

        if ($transcriptId === null || $status === null) {
            return new JsonResponse(['message' => 'Invalid webhook payload: transcript_id and status are required.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        if ($status === 'completed') {
            event(new TranscriptCompleted($transcriptId, $payload));
        } elseif ($status === 'error') {
            event(new TranscriptError($transcriptId, $payload));
        }

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Verify that the request provides the expected webhook secret header if one is configured.
     */
    protected function validateSecretHeader(Request $request): void
    {
        /** @var string|null $expectedSecret */
        $expectedSecret = config('assemblyai.webhook.secret');

        if ($expectedSecret === null || trim($expectedSecret) === '') {
            return;
        }

        /** @var string $headerName */
        $headerName = config('assemblyai.webhook.header', 'X-Webhook-Secret');

        $providedSecret = $request->header($headerName);

        if (!is_string($providedSecret) || !hash_equals($expectedSecret, $providedSecret)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid AssemblyAI webhook secret.');
        }
    }
}
