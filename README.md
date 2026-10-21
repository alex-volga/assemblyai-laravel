# AssemblyAI Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alex-volga/assemblyai-laravel.svg?style=flat-square)](https://packagist.org/packages/alex-volga/assemblyai-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alex-volga/assemblyai-laravel/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/alex-volga/assemblyai-laravel/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/alex-volga/assemblyai-laravel.svg?style=flat-square)](https://packagist.org/packages/alex-volga/assemblyai-laravel)
[![License](https://img.shields.io/packagist/l/alex-volga/assemblyai-laravel.svg?style=flat-square)](LICENSE)

A clean, modern Laravel integration for the [AssemblyAI PHP SDK](https://github.com/alex-volga/assemblyai-php). Provides seamless support for speech-to-text, audio intelligence (summarization, sentiment analysis, entity detection, PII redaction), LeMUR LLM queries, real-time streaming tokens, webhook handling, and comprehensive testing fakes.

Designed for **Laravel 11, 12, and 13** on **PHP 8.3+**.

---

## Features

- **Laravel Package Discovery**: Automatically registers the Service Provider and Facade.
- **Convenient Facade**: `AssemblyAI::transcribe(...)`, `AssemblyAI::upload(...)`, `AssemblyAI::lemur()`, etc.
- **Dependency Injection**: Type-hint `AssemblyAI\AssemblyAIClient` in controllers, jobs, and services.
- **Multiple Connections / Profiles**: Support for default, EU data residency endpoint, or custom on-the-fly clients.
- **Built-in Webhook Handling**: Dedicated route macro and events (`TranscriptCompleted`, `TranscriptError`) with secret header validation.
- **Testing Fake**: Mock transcriptions and uploads with expressive assertions (`AssemblyAI::fake()`, `assertTranscribed()`, `assertUploaded()`).
- **Strictly Typed**: Fully typed with strict types enabled and PHPStan Level 8 compliant.

---

## Requirements

- **PHP**: `^8.3 || ^8.4 || ^8.5`
- **Laravel**: `^11.0 || ^12.0 || ^13.0`

---

## Installation

Install the package via Composer:

```bash
composer require alex-volga/assemblyai-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=assemblyai-config
```

This will create `config/assemblyai.php` in your application.

---

## Configuration

Set your AssemblyAI API key in your `.env` file:

```env
ASSEMBLYAI_API_KEY=your_assemblyai_api_key_here

# Optional configuration
ASSEMBLYAI_BASE_URL=https://api.assemblyai.com # Use https://api.eu.assemblyai.com for EU region
ASSEMBLYAI_TIMEOUT=60.0
ASSEMBLYAI_POLL_INTERVAL=3
ASSEMBLYAI_POLL_TIMEOUT=300
ASSEMBLYAI_WEBHOOK_HEADER=X-Webhook-Secret
ASSEMBLYAI_WEBHOOK_SECRET=your_custom_webhook_secret
```

### Configuration Options (`config/assemblyai.php`)

```php
return [
    'api_key' => env('ASSEMBLYAI_API_KEY'),
    'base_url' => env('ASSEMBLYAI_BASE_URL', \AssemblyAI\Config::DEFAULT_BASE_URL),
    'timeout' => (float) env('ASSEMBLYAI_TIMEOUT', 60.0),
    'poll_interval' => (int) env('ASSEMBLYAI_POLL_INTERVAL', 3),
    'poll_timeout' => (int) env('ASSEMBLYAI_POLL_TIMEOUT', 300),
    'headers' => [],

    'webhook' => [
        'header' => env('ASSEMBLYAI_WEBHOOK_HEADER', 'X-Webhook-Secret'),
        'secret' => env('ASSEMBLYAI_WEBHOOK_SECRET'),
    ],
];
```

---

## Basic Usage

### 1. Transcribe Audio (Synchronous / Polling)

You can pass a remote URL, a local file path, or a file stream. `AssemblyAI::transcribe()` will upload the file if needed, poll until processing completes, and return a strongly-typed `Transcript` object:

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;

// Transcribe from a public URL
$transcript = AssemblyAI::transcribe('https://storage.googleapis.com/aai-web-samples/gettysburg.wav');

echo $transcript->id;
echo $transcript->text;
```

With custom transcription options (using array syntax or fluent `TranscriptParams` builder):

#### Option A: Fluent `TranscriptParams` Builder

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Parameters\TranscriptParams;
use AssemblyAI\Enums\SpeechModel;
use AssemblyAI\Enums\SummaryType;
use AssemblyAI\Enums\SummaryModel;
use AssemblyAI\Enums\PiiPolicy;
use AssemblyAI\Enums\PiiSubstitution;

$params = TranscriptParams::create('')
    // Speech Model & Language
    ->withSpeechModel(SpeechModel::Best) // Best, Nano, Universal2, Universal3Pro, or string
    ->withLanguageCode('en_us')          // or ->withLanguageDetection(true)
    // Speaker Diarization
    ->withSpeakerLabels(enabled: true, speakersExpected: 2)
    // Formatting & Punctuation
    ->withPunctuate(true)
    ->withFormatText(true)
    // Audio Intelligence
    ->withAutoChapters(true)
    ->withSentimentAnalysis(true)
    ->withEntityDetection(true)
    ->withSummarization(SummaryType::Bullets, SummaryModel::Informative)
    // PII Redaction
    ->withPiiRedaction(
        policies: [PiiPolicy::EmailAddress, PiiPolicy::PhoneNumber, PiiPolicy::CreditCardNumber],
        sub: PiiSubstitution::EntityType
    )
    // Contextual Prompting & Word Boosting
    ->withPrompt('Technical podcast discussing machine learning and software architecture')
    ->withWordBoost(['AssemblyAI', 'PHP', 'SDK'], 'high');

$transcript = AssemblyAI::transcribe(
    audio: storage_path('app/podcasts/episode-1.mp3'),
    params: $params,
    onPoll: function ($pollingTranscript) {
        logger()->info("Transcription status: {$pollingTranscript->status->value}");
    }
);
```

#### Option B: Associative Array Syntax

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Enums\SpeechModel;
use AssemblyAI\Enums\SummaryType;

$transcript = AssemblyAI::transcribe(
    audio: storage_path('app/podcasts/episode-1.mp3'),
    params: [
        'speech_model' => SpeechModel::Best, // or 'best', 'nano', 'universal-2'
        'speaker_labels' => true,
        'speakers_expected' => 2,
        'auto_chapters' => true,
        'summary_type' => SummaryType::Bullets,
    ],
    onPoll: function ($pollingTranscript) {
        logger()->info("Transcription status: {$pollingTranscript->status->value}");
    }
);

// Access utterances and chapters
foreach ($transcript->utterances as $utterance) {
    echo "Speaker {$utterance->speaker}: {$utterance->text}\n";
}
```

---

### 2. Dependency Injection

You can inject `AssemblyAI\AssemblyAIClient` directly into controllers, queued jobs, or services:

```php
namespace App\Http\Controllers;

use AssemblyAI\AssemblyAIClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PodcastController extends Controller
{
    public function transcribe(Request $request, AssemblyAIClient $client): JsonResponse
    {
        $transcript = $client->transcribe($request->input('audio_url'));

        return response()->json([
            'id' => $transcript->id,
            'text' => $transcript->text,
        ]);
    }
}
```

---

### 3. File Uploads

Upload local files to AssemblyAI's secure CDN before creating transcriptions:

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;

$uploadUrl = AssemblyAI::upload(storage_path('app/meeting.mp3'));
// Returns: https://cdn.assemblyai.com/upload/...
```

---

### 4. Asynchronous Transcriptions & Webhooks

When transcribing long files, submit a background transcription job with a webhook callback:

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Parameters\TranscriptParams;

// Submit via TranscriptParams builder
$params = TranscriptParams::create('https://example.com/recording.mp3')
    ->withWebhook(
        webhookUrl: route('assemblyai.webhook'),
        authHeaderName: config('assemblyai.webhook.header'),
        authHeaderValue: config('assemblyai.webhook.secret'),
    );

$transcript = AssemblyAI::transcripts()->create($params);

// Or submit via associative array:
$transcript = AssemblyAI::transcripts()->create([
    'audio_url' => 'https://example.com/recording.mp3',
    'webhook_url' => route('assemblyai.webhook'),
    'webhook_auth_header_name' => config('assemblyai.webhook.header'),
    'webhook_auth_header_value' => config('assemblyai.webhook.secret'),
]);
```

#### Register the Webhook Route

In `routes/web.php` or `routes/api.php`:

```php
use Illuminate\Support\Facades\Route;

// Registers POST /assemblyai/webhook (named 'assemblyai.webhook')
Route::assemblyAiWebhooks();

// Or define a custom path:
Route::assemblyAiWebhooks('api/v1/assemblyai/callback');
```

> **Note:** If you register the route in `routes/web.php`, remember to exclude `/assemblyai/webhook` from CSRF protection in `bootstrap/app.php`:
> ```php
> ->withMiddleware(function (Middleware $middleware) {
>     $middleware->validateCsrfTokens(except: [
>         'assemblyai/webhook',
>     ]);
> })
> ```

#### Listen for Webhook Events

When AssemblyAI notifies your endpoint, the package validates the secret header and automatically dispatches one of the following events:

- `AssemblyAI\Laravel\Events\TranscriptCompleted`
- `AssemblyAI\Laravel\Events\TranscriptError`

Example listener:

```php
namespace App\Listeners;

use AssemblyAI\Laravel\Events\TranscriptCompleted;
use AssemblyAI\Laravel\Facades\AssemblyAI;

class HandleCompletedTranscript
{
    public function handle(TranscriptCompleted $event): void
    {
        $transcriptId = $event->transcriptId;

        // Fetch full transcript details
        $transcript = AssemblyAI::transcripts()->get($transcriptId);

        // Process transcript (save to database, notify users, etc.)
    }
}
```

Register the listener in `app/Providers/AppServiceProvider.php` (or `EventServiceProvider`):

```php
use App\Listeners\HandleCompletedTranscript;
use AssemblyAI\Laravel\Events\TranscriptCompleted;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(TranscriptCompleted::class, HandleCompletedTranscript::class);
}
```

---

### 5. LeMUR (LLM Intelligence over Audio)

Apply Large Language Models directly to your transcripts:

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\Enums\LemurModel;

// Ask questions about the transcript
$qna = AssemblyAI::lemur()->questionAnswer([
    'transcript_ids' => [$transcript->id],
    'questions' => [
        ['question' => 'What was the main outcome of the meeting?'],
        ['question' => 'Who was assigned the follow-up tasks?'],
    ],
    'final_model' => LemurModel::Claude3_5_Sonnet,
]);

// Extract action items
$actionItems = AssemblyAI::lemur()->actionItems([
    'transcript_ids' => [$transcript->id],
]);
```

---

### 6. Multiple Connections (e.g. EU Region)

Define additional connections in `config/assemblyai.php`:

```php
'connections' => [
    'eu' => [
        'api_key' => env('ASSEMBLYAI_EU_API_KEY'),
        'base_url' => \AssemblyAI\Config::EU_BASE_URL,
    ],
],
```

Switch connections at runtime:

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;

$euClient = AssemblyAI::connection('eu');
$transcript = $euClient->transcribe('https://example.com/audio.mp3');
```

Or build an on-the-fly client with custom configuration:

```php
$client = AssemblyAI::build([
    'api_key' => 'temporary-api-key',
    'timeout' => 120.0,
]);
```

---

## Testing & Mocking (`AssemblyAI::fake()`)

This package includes a first-class test fake so you can test your features without making real HTTP requests or spending API credits:

```php
use AssemblyAI\Laravel\Facades\AssemblyAI;
use AssemblyAI\ValueObjects\Transcript;

public function test_it_transcribes_audio(): void
{
    // Fake AssemblyAI
    AssemblyAI::fake();

    // Perform application logic (e.g. call a controller, dispatch a job)
    $response = $this->postJson('/api/transcribe', [
        'url' => 'https://example.com/audio.mp3',
    ]);

    $response->assertOk();

    // Assert that transcription was requested
    AssemblyAI::assertTranscribed('https://example.com/audio.mp3');

    // Or assert with a custom callback
    AssemblyAI::assertTranscribed(function ($audio, $params) {
        return $audio === 'https://example.com/audio.mp3'
            && ($params['speaker_labels'] ?? false) === true;
    });

    // Assert other files were not transcribed
    AssemblyAI::assertNotTranscribed('https://example.com/other.mp3');
}
```

### Pre-queuing Mock Responses

```php
$customTranscript = Transcript::fromArray([
    'id' => 'custom-id-123',
    'status' => 'completed',
    'audio_url' => 'https://example.com/audio.mp3',
    'text' => 'Expected mock transcription text.',
]);

AssemblyAI::fake([$customTranscript]);
```

---

## Testing

Run unit and feature tests:

```bash
composer test
```

Run static analysis with PHPStan:

```bash
composer analyse
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

---

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## Security Vulnerabilities

Please review [SECURITY.md](SECURITY.md) on how to report security vulnerabilities.

---

## Credits

- [Oleksandr Volha](https://github.com/alex-volga)
- [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
