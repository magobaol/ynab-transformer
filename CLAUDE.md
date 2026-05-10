# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Symfony 7.3 / PHP 8.4 application that converts Italian bank statements (Fineco, Revolut, Nexi, Popso, Poste, Telepass, Isybank, BPER, Intesa) into YNAB-compatible CSV. Two entry points share the same transformation pipeline:

- **CLI** — `bin/console app:transform <input-file> [--format=<name>]`
- **Web** — Symfony controller serving a Twig UI at `/` with a POST `/transform` AJAX upload endpoint

## Common Commands

```sh
# Run the full test suite
composer test
# or
vendor/bin/phpunit

# Run a single test file / test method
vendor/bin/phpunit tests/Transformer/FinecoTest.php
vendor/bin/phpunit --filter testSomeMethod tests/Transformer/FinecoTest.php

# Note: bin/phpunit is a broken Symfony bridge stub targeting PHPUnit 9 —
# use vendor/bin/phpunit (PHPUnit 12) directly.

# CLI transformation (auto-detects bank format; --format overrides)
bin/console app:transform path/to/statement.xlsx
bin/console app:transform path/to/statement.xlsx --format=fineco

# Local web server
symfony serve          # if the symfony CLI is installed
php -S localhost:8000 -t public
```

Bank statement fixtures for tests live in `tests/Fixtures/`.

## Architecture

### Transformer pipeline

The core abstraction is `Transformer\Transformer` ([src/Transformer/Transformer.php](src/Transformer/Transformer.php)), a two-method interface:

- `canHandle(string $filename): bool` — static sniff used for format auto-detection (header row inspection, extension check, etc.)
- `transformToYNAB(): YNABTransactions` — produces the normalized transaction collection

Each bank has its own implementation in `src/Transformer/` (Fineco, Revolut, Nexi, Popso, Poste, Telepass, Isybank, BPER, Intesa). Excel files are read via `phpoffice/phpspreadsheet`; CSVs are parsed directly.

`Transformer\TransformerFactory` ([src/Transformer/TransformerFactory.php](src/Transformer/TransformerFactory.php)) owns the `format => class` registry and exposes `detectFormat()` / `create()` / `getSupportedFormats()`. **When adding a new bank, register it here** — both the CLI command and the web service go through this factory.

Detection rules: `detectFormat()` throws when zero transformers match (unsupported file) **or** when more than one matches (ambiguous) — in the latter case the user is asked to pass `--format` explicitly.

### Output model

`Model\Transaction\YNABTransaction` + `YNABTransactions` ([src/Model/Transaction/](src/Model/Transaction/)) encapsulate the YNAB CSV shape: `Date;Payee;Memo;Outflow;Inflow` (semicolon-separated). `toCSVFile()` writes the final file; filenames are built by `Common\FileNameGenerator` which appends `-to-ynab`, swaps extension to `csv`, and disambiguates collisions with a timestamp suffix.

### Web layer (Symfony)

Request flow for `POST /transform`:

1. `TransformController::transform` ([src/Controller/TransformController.php](src/Controller/TransformController.php)) — validates CSRF via `CsrfTokenService`, rate-limits by IP via `RateLimitingService` (5 requests / 10 min, whitelist via `RATE_LIMIT_WHITELIST` env), then validates the upload (xlsx/xls/csv, 1MB max).
2. `FileProcessingService::processUploadedFile` ([src/Service/FileProcessingService.php](src/Service/FileProcessingService.php)) — stores to `var/tmp/`, delegates transformation, returns a `BinaryFileResponse` with `deleteFileAfterSend(true)`. The uploaded temp file is always cleaned up in `finally`.
3. `TransformationService` ([src/Service/TransformationService.php](src/Service/TransformationService.php)) — thin wrapper calling the factory.

### Error handling (ADR-003)

Per [docs/ADR-003-error-handling-production.md](docs/ADR-003-error-handling-production.md), the controller **returns `JsonResponse` instead of throwing HTTP exceptions** to avoid the Symfony debug page redirecting users away from the SPA on prod. `App\EventListener\ExceptionListener` catches any remaining exceptions for API-shaped requests (AJAX / POST to `/transform` / JSON Accept) and returns JSON. `TransformController::getUserFriendlyErrorMessage()` maps known exception substrings (`'No supported format detected'`, `'Multiple formats detected'`, etc.) to end-user copy — **when adding new error categories, extend this map rather than exposing raw messages**.

### Namespacing quirk

`composer.json` declares **two PSR-4 roots for `src/`**: the empty prefix `""` and `App\\`. Domain code lives under root namespaces (`Transformer\`, `Model\`, `Common\`) while Symfony-specific code lives under `App\` (`App\Controller`, `App\Service`, `App\Command`, `App\EventListener`). Preserve this split when adding files.

## Architectural decisions

Prior ADRs under [docs/](docs/):
- 001 — Web interface architecture
- 002 — Web interface implementation plan
- 003 — Production error handling (see above)
