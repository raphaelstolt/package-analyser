<?php

use Symfony\Component\Console\Command\Command;

it('requires a package-directory argument', function () {
    try {
        $this->artisan('analyse')->assertExitCode(Command::FAILURE);
    } catch (RuntimeException $e) {
        $this->assertEquals($e->getMessage(), 'Not enough arguments (missing: "package-directory").');
    }
});

it('fails an non-existing package-directory', function () {
    $this->artisan('analyse /tmpo')->assertExitCode(Command::FAILURE);
});

it('prints number of analysis steps', function () {
    $this->artisan('analyse '.$this->temporaryDirectory)->expectsOutputToContain('Ran 16 analysis steps');
});

it('has violations in output', function () {
    $this->artisan('analyse '.$this->temporaryDirectory)->expectsOutputToContain('optimiseable aspects')->assertExitCode(
        Command::FAILURE
    );
});
