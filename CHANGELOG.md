# Changelog

All notable changes to `assemblyai-laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-11-06

### Added
- Upgraded `alex-volga/assemblyai-php` SDK dependency to `^1.2`.
- Support for `speech_models` with fallback list configuration via `TranscriptParams::withSpeechModels()`.
- Added `SpeechModel::Universal35Pro` and `SpeechModel::Universal36ProPreview` cases.
- Added `SummaryEffort` enum support (`SummaryEffort::Low`, `SummaryEffort::Medium`) in `TranscriptParams::withSummarization()`.
- Added `TranscriptParams::withSpeechUnderstanding()` for direct Speech Understanding configuration.
- Support for `$transcript->speechModelUsed`, `$transcript->speechModels`, and `$transcript->speechUnderstanding` properties on `Transcript`.
- Added link and reference callout to [alex-volga/assemblyai-php](https://github.com/alex-volga/assemblyai-php) in README.

### Changed
- Migrated documentation and examples from deprecated `speech_model` to `speech_models` array.
- Updated Auto Chapters and Summarization examples to use the new Speech Understanding format.
- Deprecated `SummaryModel` in documentation examples in favor of `SummaryEffort` and Speech Understanding.

### Removed
- Removed deprecated Conformer models (`SpeechModel::Best`, `SpeechModel::Nano`) from documentation and examples.

## [1.0.1] - 2026-10-21

### Fixed
- Fixed typo in documentation for asynchronous transcript creation (`transcripts()->create` instead of `transcripts()->submit`).

### Added
- Comprehensive documentation for `TranscriptParams` builder and parameter configuration (speech models, language selection, diarization, audio intelligence, PII redaction, prompts, word boosting).

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
