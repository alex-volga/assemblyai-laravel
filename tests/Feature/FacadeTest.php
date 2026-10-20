<?php

declare(strict_types=1);

namespace AssemblyAI\Laravel\Tests\Feature;

use AssemblyAI\AssemblyAIClient;
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Laravel\Tests\TestCase;
use AssemblyAI\Resources\FileResource;
use AssemblyAI\Resources\LemurResource;
use AssemblyAI\Resources\RealtimeResource;
use AssemblyAI\Resources\TranscriptResource;

class FacadeTest extends TestCase
{
    public function test_facade_resolves_resources(): void
    {
        $this->assertInstanceOf(AssemblyAIClient::class, AssemblyAI::client());
        $this->assertInstanceOf(TranscriptResource::class, AssemblyAI::transcripts());
        $this->assertInstanceOf(FileResource::class, AssemblyAI::files());
        $this->assertInstanceOf(LemurResource::class, AssemblyAI::lemur());
        $this->assertInstanceOf(RealtimeResource::class, AssemblyAI::realtime());
    }

    public function test_facade_accesses_config(): void
    {
        $this->assertSame('test_api_key_secret', AssemblyAI::getConfig()->apiKey);
    }
}
