<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TranscriptCompleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $transcriptId,
        public readonly array $payload = [],
    ) {
    }
}
