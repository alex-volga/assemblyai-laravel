# Changelog

All notable changes to `assemblyai-laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-20

### Added
- Initial release of the official AssemblyAI Laravel integration package.
- Support for Laravel 11, 12, and 13 on PHP 8.3+.
- Package auto-discovery for `AssemblyAIServiceProvider` and `AssemblyAI` Facade.
- `AssemblyAIManager` with multi-connection management and dynamic client building.
- Webhook handling via `Route::assemblyAiWebhooks()` macro and `WebhookController`.
- Event dispatching for webhooks (`TranscriptCompleted`, `TranscriptError`) with secret header validation.
- Comprehensive testing double `AssemblyAIFake` via `AssemblyAI::fake()`, with `assertTranscribed()`, `assertUploaded()`, and response queueing.
- Static analysis with PHPStan at Level 8.
