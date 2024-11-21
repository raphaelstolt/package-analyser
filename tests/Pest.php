<?php

use Illuminate\Filesystem\Filesystem;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->beforeEach(function () {
    $this->temporaryDirectory = setUpTemporaryDirectory();
    exec('cd '.$this->temporaryDirectory.' && git init 2>&1');
})->afterEach(function () {
    $filesystem = new Filesystem;
    $filesystem->deleteDirectory($this->temporaryDirectory);
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
function isWindows($os = PHP_OS): bool
{
    if (\strtoupper(\substr($os, 0, 3)) !== 'WIN') {
        return false;
    }

    return true;
}

function setUpTemporaryDirectory(): string
{
    if (isWindows() === false) {
        \ini_set('sys_temp_dir', '/tmp/pa');
        $temporaryDirectory = '/tmp/pa';
    } else {
        $temporaryDirectory = \sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'pa';
    }

    if (! \file_exists($temporaryDirectory)) {
        \mkdir($temporaryDirectory);
    }

    \copy(realpath('./composer.json'), $temporaryDirectory.DIRECTORY_SEPARATOR.'composer.json');

    return $temporaryDirectory;
}
