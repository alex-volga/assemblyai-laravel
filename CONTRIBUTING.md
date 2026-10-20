# Contributing

Contributions are welcome and will be fully credited.

We accept contributions via Pull Requests on [GitHub](https://github.com/alex-volga/assemblyai-laravel).

## Pull Requests

- **PSR-12 Coding Standard**: The easiest way to apply the conventions is to follow existing files.
- **Add Tests**: Your patch must include unit or feature tests that cover the new feature or fix.
- **Document Changes**: Ensure `README.md` and `CHANGELOG.md` are updated if public APIs change.
- **PHPStan Level 8**: Code must pass `composer analyse` without errors.

## Running Tests

```bash
# Run unit and feature tests
composer test

# Run static analysis
composer analyse

# Run both
composer check
```
