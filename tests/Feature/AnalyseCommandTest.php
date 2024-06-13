<?php

use Symfony\Component\Console\Command\Command;

it('requires a package-directory argument', function () {
    try {
        $this->artisan('analyse')->assertExitCode(Command::FAILURE);
    } catch (RuntimeException $e) {
        $this->assertEquals($e->getMessage(), 'Not enough arguments (missing: "package-directory").');
    }
});

it('fails on non existent package directory', function () {
    $this->artisan('analyse /tmpo')->assertExitCode(Command::FAILURE);
});

it('prints number of analysis steps', function () {
    $this->artisan('analyse '.$this->temporaryDirectory)->expectsOutputToContain('Ran 17 analysis steps');
});

it('has success emoji for successful analyse step', function () {
    $this->artisan('analyse '.$this->temporaryDirectory)->expectsOutputToContain(' ✅');
});

it('has violations in output and fails', function () {
    $this->artisan('analyse '.$this->temporaryDirectory)->expectsOutputToContain('optimiseable aspects')->assertExitCode(
        Command::FAILURE
    );
});

it('can alternate command failure state', function () {
    $this->artisan('analyse '.$this->temporaryDirectory.' --violations-threshold=16')->assertExitCode(Command::SUCCESS);
});

it('writes a HTML report if desired', function () {
    $this->artisan('analyse '.$this->temporaryDirectory.' --write-report')->expectsOutputToContain('Writing package analysis report to');
});

it('has its expected options', function () {
    $this->artisan('analyse --help')->expectsOutputToContain('--write-report', '--violations-threshold[=VIOLATIONS-THRESHOLD]');
});

it('succeeds for failureless analysis', function () {
    setUpCompletePackage($this->temporaryDirectory);

    $this->artisan('analyse '.$this->temporaryDirectory)->assertExitCode(Command::SUCCESS);
});

it('succeeds for release candidates', function () {
    setUpCompletePackage($this->temporaryDirectory);
    \exec('cd '.$this->temporaryDirectory.' && touch foo.txt && git add foo.txt && git commit -m "Foo" && git tag RC-1.0.0 2>&1');

    $this->artisan('analyse '.$this->temporaryDirectory)->assertExitCode(Command::SUCCESS);
});

it('succeeds for GitHub CI', function () {
    setUpCompletePackage($this->temporaryDirectory);
    \unlink($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.gitlab-ci.yml');

    if (! \file_exists($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.github')) {
        \mkdir($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.github');
        \mkdir($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.github'.DIRECTORY_SEPARATOR.'workflows');
    }

    $this->artisan('analyse '.$this->temporaryDirectory)->assertExitCode(Command::SUCCESS);
});

function setUpCompletePackage(string $temporaryDirectory): void
{
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'.gitignore');
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'.gitattributes');
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'.gitlab-ci.yml');

    \exec('cd '.$temporaryDirectory.' && git config user.email "raphael.stolt@gmail.com" && git config user.name "Raphael Stolt" 2>&1');
    \exec('cd '.$temporaryDirectory.' && touch foo.txt && git add foo.txt && git commit -m "Foo" && git tag v1.0.0 2>&1');

    if (! \file_exists($temporaryDirectory.DIRECTORY_SEPARATOR.'src')) {
        \mkdir($temporaryDirectory.DIRECTORY_SEPARATOR.'src');
    }

    if (! \file_exists($temporaryDirectory.DIRECTORY_SEPARATOR.'bin')) {
        \mkdir($temporaryDirectory.DIRECTORY_SEPARATOR.'bin');
    }

    if (! \file_exists($temporaryDirectory.DIRECTORY_SEPARATOR.'tests')) {
        \mkdir($temporaryDirectory.DIRECTORY_SEPARATOR.'tests');
    }

    if (! \file_exists($temporaryDirectory.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin')) {
        \mkdir($temporaryDirectory.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin', 0777, true);
        \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'phpstan');
        \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pint');
        \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pest');
    }
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'box.json');
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'CHANGELOG.md');
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'README.md');
    \touch($temporaryDirectory.DIRECTORY_SEPARATOR.'LICENSE.md');
}
