<?php

use Symfony\Component\Console\Command\Command;

it('requires a package-directory argument', function () {
    try {
        $this->artisan('analyse')->assertExitCode(Command::FAILURE);
    } catch (RuntimeException $e) {
        $this->assertEquals($e->getMessage(), 'Not enough arguments (missing: "package-directory").');
    }
});

it('requires an existing package-directory argument', function () {
    $this->artisan('analyse /tmpo')->assertExitCode(Command::FAILURE);
});
