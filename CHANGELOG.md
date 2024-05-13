# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to
[Semantic Versioning](http://semver.org/).

## [Unreleased]

## [v1.0.7] - 2024-05-13
### Fixed

- Return correct return value.

## [v1.0.6] - 2024-05-08
### Fixed

- Catch release candidates when checking for Semantic Versioning.

## [v1.0.5] - 2024-05-08
### Fixed

- Fix several bugs discovered while writing integration tests.

### Added

- Add `phpstan` existence check.

## [v1.0.4] - 2024-05-07
### Added

- Add `ecs.php` existence check.

## [v1.0.3] - 2024-05-03
### Added

- Add `.gitignore` existence check.
- Improve violations handling and reporting.

## [v1.0.2] - 2024-05-02

### Added

- Add `--violations-threshold` option, which defaults to `0`.
- Utilise `Symfony\Component\Console\Helper\Table` for TUI output.

## [v1.0.1] - 2024-05-02

### Removed

- Remove `--open-php-package-checklist-link` option.

## v1.0.0 - 2024-04-30

- Initial release.

[Unreleased]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.6...HEAD

[v1.0.6]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.5...v1.0.6
[v1.0.5]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.4...v1.0.5
[v1.0.4]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.3...v1.0.4
[v1.0.3]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/raphaelstolt/package-analyser/compare/v1.0.0...v1.0.1
