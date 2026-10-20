<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Unit;

use AssemblyAI\Laravel\Testing\AssemblyAIFake;
use PHPUnit\Framework\TestCase;

class AssemblyAIFakeUnitTest extends TestCase
{
    public function test_fake_initializes_with_empty_counters(): void
    {
        $fake = new AssemblyAIFake();

        $this->assertSame(0, $fake->recordedTranscriptionsCount());
        $this->assertSame(0, $fake->recordedUploadsCount());
    }
}
