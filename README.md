# PackageAnalyser

The PackageAnalyser is a utility tool that analyses a 🐘 project/micro-package
for its structure, and provides tips on best practices for such one. It's also my first __TUI__ (Terminal User Interface)
written in [Laravel Zero](https://laravel-zero.com/), so bear with me.

## Installation

The PackageAnalyser TUI should be installed globally through Composer.

``` bash
composer global require stolt/package-analyser
```

Make sure that the path to your global vendor binaries directory is in your `$PATH`.
You can determine the location of your global vendor binaries directory via
`composer global config bin-dir --absolute`. This way the `package-analyser`
executable can be located.

## Usage

Run the PackageAnalyser TUI within or against a 🐘 project/micro-package directory, and it will analyse
the given package and provide tips on best practices when required.

``` bash
package-analyser analyse [<path-to-package-directory>]
```

### Available options

The `--open-php-package-checklist-link` option will open the PHP Package Checklist (https://phppackagechecklist.com) in
a browser with a custom report based on the provided package.

The `--write-report` option will write a custom HTML report in the current directory based on the provided package.

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
