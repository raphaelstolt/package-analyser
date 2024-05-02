# Package analyser

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

The `--write-report` option will write a HTML report in the current directory based on the provided package.

The `--violations-threshold` option defines the threshold on which the package analysis is considered invalid 
and produces an exit status > 0. Defaults to `0`.

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
