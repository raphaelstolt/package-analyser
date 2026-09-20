# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`stolt/package-analyser` is a Laravel Zero TUI that analyses a PHP project/micro-package for structural best practices and reports violations. PHP 8.2+, PSR-4 with `App\` → `app/` and `Tests\` → `tests/`.

## Commands

```bash
composer test                        # Pest test suite
composer test -- --parallel          # as CI runs it
./vendor/bin/pest --filter="prints number of analysis steps"   # single test by name
./vendor/bin/pest tests/Unit/PackageAnalyserTest.php           # single file
composer test-coverage-html          # coverage into coverage-report/ (needs Xdebug)

composer cs-lint                     # Pint, check only
composer cs-fix                      # Pint, apply fixes
composer spell-check                 # Peck
composer validate-gitattributes      # lean-package-validator
composer pre-commit-check            # test + cs-lint + spell-check
```

Run the tool itself: `php bin/package-analyser analyse <path-to-package-directory>`. Note `bin/package-analyser` requires `./bootstrap/app.php` relative to the **current working directory**, so it only runs from the repo root during development.

CI (`.github/workflows/`) runs `test` (`composer test -- --parallel`) and `lint` (`cs-lint` + `validate-gitattributes`) on PHP 8.3.

## Architecture

Single command, three domain classes:

- `app/Commands/Analyse.php` — the only command. Parses `{package-directory}`, `--configuration`, `--write-report`, `--violations-threshold`; wires the domain objects; renders the Symfony Console table; returns `FAILURE` when violation count exceeds the threshold.
- `app/Domain/PackageAnalyser.php` — the whole rule engine. The `$steps` array in the constructor is the single source of truth for what gets analysed; `analyse()` switches over step ids and delegates to `check*()` predicates, most of which are Symfony Finder existence probes against `$directoryToAnalyse` (files, `vendor/bin/*` tool binaries, `composer.json` keys). Two steps shell out: `semantic-versioning` runs `git tag --list`, `composer-outdated` runs `composer outdated --format=json --direct`.
- `app/Domain/Configuration.php` — loads `.pa.yml` (or `--configuration`), validates every entry under `stepsToOmit` against the analyser's known step ids, throws `InvalidConfiguration` otherwise.
- `app/Domain/ReportWriter.php` — renders `app/Templates/report.html` to `pa-report.html` in the working directory via `str_replace` placeholders (`{{ directory }}`, `{{ tbody }}`, `{{ pa_version }}`).

Step outcomes are the `App\Enum\ViolationStatus` enum: `True`, `False`, `Irrelevant` (step doesn't apply — e.g. CLI steps on a non-CLI package), `Omitted` (suppressed via config). Only `False` counts as a violation. The `cli`/`cli-binary`/`cli-phar` steps start as `Irrelevant` and are activated when `composer.json` `keywords` contains `cli`, `tui`, or `console`.

### Adding or removing an analyse step

The step count is hardcoded in several places — all of these must change together:

1. The `$steps` array and the matching `case` in `analyse()` in `PackageAnalyser.php`.
2. The explicit `getStepsForTable()[0..21]` index list in `Analyse::handle()` — it is not a loop, so a new step is silently dropped from the table (or throws on a removal) if not added here.
3. The `Ran 22 analysis steps` assertion in `tests/Feature/AnalyseCommandTest.php`, plus any threshold-dependent assertions there. Note that `setUpCompletePackage()` in that file is expected to produce a violation-free package, so a new step usually needs a matching fixture there too.

`Analyse::VERSION` is a manual constant used only for the HTML report; the TUI's displayed version comes from `app('git.version')` in `config/app.php`.

## Conventions

- Code style is Laravel Pint with the default preset; `declare(strict_types=1)` in `app/` classes (except `Configuration.php`).
- Peck spell-checks the codebase and is part of `pre-commit-check`. New domain vocabulary that trips it belongs in the `ignore.words` list in `peck.json`.
- The package is kept "lean" for dist releases — new dev-only root files need an `export-ignore` entry in `.gitattributes` or `validate-gitattributes` fails CI.
- Feature tests are bound (via `tests/Pest.php`) to a per-test temporary directory at `/tmp/pa` that gets `git init`'d and a copy of the repo's own `composer.json`; that directory is the package under analysis. Unit tests reach private methods through `ReflectionClass`.
- Notable UK/`-ise` spelling throughout (`analyse`, `optimiseable`, `utilise`) — match it.
- Contributions use feature/topic branches; `CHANGELOG.md` follows Keep a Changelog and the project follows SemVer.
