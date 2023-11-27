<?php

it('requires a package-directory argument', function () {
    try {
        $this->artisan('analyse')->assertExitCode(1);
    } catch (RuntimeException $e) {
        $this->assertEquals($e->getMessage(), 'Not enough arguments (missing: "package-directory").');
    }
});

it('requires an existing package-directory argument', function () {
    $this->artisan('analyse /tmp')->assertExitCode(0);
});
