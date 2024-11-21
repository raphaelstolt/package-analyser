# Package analyser

![Test Status](https://github.com/raphaelstolt/package-analyser/workflows/test/badge.svg)
[![Version](http://img.shields.io/packagist/v/stolt/package-analyser.svg?style=flat)](https://packagist.org/packages/stolt/package-analyser)
![PHP Version](https://img.shields.io/badge/php-8.2+-ff69b4.svg)
[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat)](https://github.com/php-pds/skeleton)

The package analyser is a utility tool that analyses a 🐘 project/micro-📦
for its structure, and provides tips on best practices for such one. It's also my first __TUI__ (Terminal User Interface)
written in [Laravel Zero](https://laravel-zero.com/), so bear with me.

## Installation

The package analyser TUI should be installed globally through Composer.

``` bash
composer global require --dev stolt/package-analyser
```

Make sure that the path to your global vendor binaries directory is in your `$PATH`.
You can determine the location of your global vendor binaries directory via
`composer global config bin-dir --absolute`. This way the `package-analyser`
executable can be located.

## Usage

Run the package analyser TUI within or against a 🐘 project/micro-package directory, and it will analyse
the given package and provide tips on best practices when required.

``` bash
package-analyser analyse [<path-to-package-directory>]
```

### Available options

The `--configuration` option allows the usage of a configuration with analyse steps to ignore. If no configuration
is provided the tool will look for an existing `.pa.yml` file per default.

The `--write-report` option will write an HTML report in the current directory based on the provided package.

The `--violations-threshold` option defines the threshold on which the package analysis is considered invalid 
and produces a non-zero exit code. Defaults to `0`.

### Configuration

To omit steps from the package analysis it's possible to configure these like shown next.

```yaml
stepsToOmit:
    - static-analyse
    - eol-php
```

### Running tests

``` bash
composer test
```

### License

This library and its TUI are licensed under the MIT license. Please see [LICENSE.md](LICENSE.md) for more details.

### Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more details.

### Contributing

Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for more details.
