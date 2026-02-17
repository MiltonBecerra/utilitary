# AGENTS.md

## Scope
This repository is a Laravel 8 application with Node-based asset builds and
standalone Node scraping scripts. This document is for agentic coding agents
working in this repo.

## Rules files
- No `.cursor/rules/`, `.cursorrules`, or `.github/copilot-instructions.md`
  were found in this repository at the time of writing.
- Follow this file as the primary guidance for agents.

## Quick commands

### PHP dependencies
- Install: `composer install`
- Autoload refresh: `composer dump-autoload`

### Node dependencies
- Install: `npm install`

### Asset build (Laravel Mix)
- Dev build: `npm run dev`
- Production build: `npm run prod`
- Watch: `npm run watch`
- Watch (polling): `npm run watch-poll`
- Hot reload: `npm run hot`

### Tests (PHPUnit)
Tests are defined in `phpunit.xml` with two suites:
- Unit tests: `tests/Unit`
- Feature tests: `tests/Feature`

Run all tests:
- `vendor/bin/phpunit`

Run a suite:
- `vendor/bin/phpunit --testsuite Unit`
- `vendor/bin/phpunit --testsuite Feature`

Run a single test file:
- `vendor/bin/phpunit tests/Feature/SomeTest.php`

Run a single test by method name:
- `vendor/bin/phpunit --filter testMethodName`
- `vendor/bin/phpunit --filter SomeTest::testMethodName`

Run a single test class from a file:
- `vendor/bin/phpunit --filter SomeTest tests/Feature/SomeTest.php`

Laravel test runner (wrapper around PHPUnit, optional):
- `php artisan test`
- `php artisan test --filter SomeTest`
- `php artisan test --testsuite=Feature --filter testMethodName`

### Lint / format
- No explicit linters or formatters are configured in this repo
  (no Pint, PHPCS, ESLint, or Prettier configs found).
- Follow the local style and `.editorconfig` settings.

## Environment notes
- PHP: `^7.3` or `^8.0` (see `composer.json`).
- `.env` is created from `.env.example` by composer post-install.
- Do not commit secrets or local `.env` changes.

## Code style guidelines

### General formatting
- Indentation: 4 spaces.
- Line endings: LF.
- Charset: UTF-8.
- Trim trailing whitespace (except Markdown per `.editorconfig`).
- Ensure final newline at EOF.

### PHP (Laravel)
- Use PSR-12-ish formatting; mirror existing files in `app/`.
- Namespaces follow PSR-4; files live under `app/` and `tests/`.
- Class names: StudlyCase.
- Method names: camelCase.
- Variables: camelCase, descriptive.
- Constants: UPPER_SNAKE_CASE where used.
- Prefer typed properties and return types when practical.
- For complex arrays, use docblock array shapes (see services classes).
- Avoid unused imports; keep `use` statements tidy and grouped.
- Keep controller methods thin; push business logic to services.

### Imports and dependencies
- Use fully qualified class imports at top of files (`use ...`).
- Keep Laravel facades imported instead of leading backslashes in logic.
- Prefer existing helpers: `Cache`, `Str`, `response()->json`, `collect`.

### Error handling and validation
- Use `$request->validate([...])` in controllers for input validation.
- For user-visible errors, prefer `abort(422, ...)` or validation errors.
- Wrap external I/O in `try/catch (\Throwable)` and log best-effort errors.
- Log with context (`\Log::info/warning`, include array context).
- Do not leak sensitive data in logs or responses.

### Null safety and optional data
- Use null-safe operators (`?->`) where applicable.
- Prefer `isset()` or `is_array()` checks before indexing arrays.
- Normalize strings with `trim()` and `(string)` casting for safety.

### Data and arrays
- When building structured results, keep array keys consistent.
- Prefer explicit key names over positional arrays.
- Keep numeric values as floats where prices/quantities are used.

### Services and utilities
- Follow existing patterns in `app/Modules/Utilities/...` services.
- Keep pure parsing/normalization logic in helper classes.
- Prefer small, testable methods with clear responsibilities.

### Requests, caching, and rate limiting
- Use cache keys consistently (string prefix + hashed query).
- Honor `RequestGuard` and circuit breaker logic.
- Avoid repeated requests on failures; backoff as implemented.
- Cache search results for 10 minutes when possible.

### Tests
- Place unit tests in `tests/Unit` and feature tests in `tests/Feature`.
- Test method names should describe behavior (e.g., `test_it_does_x`).
- Use Laravel testing helpers and database traits where appropriate.

## JavaScript / Node scripts
- This repo uses CommonJS (`type: commonjs`).
- Prefer `const` and `let`, avoid `var`.
- Use semicolons as shown in existing scripts.
- Keep scripts robust around timeouts and external failures.
- Puppeteer scripts should clean up resources (close browser, delete temp dirs).

## Frontend assets
- Assets are built with Laravel Mix (`webpack.mix.js`).
- Source JS lives under `resources/js`.
- Avoid introducing new build tools unless necessary.

## Docs and Markdown
- Markdown allows trailing whitespace (see `.editorconfig`).
- Keep docs concise; link to files rather than pasting large code blocks.

## File locations to know
- `app/` - application code
- `routes/` - route definitions
- `resources/views/` - Blade templates
- `tests/` - PHPUnit tests
- `scrape_*.js` - standalone scraping scripts
- `puppeteer-server/` - local puppeteer API server

## Notes for agents
- Prefer reading existing patterns before introducing new ones.
- Keep changes minimal and scoped to the task.
- Avoid touching unrelated files, especially generated assets or vendor code.
