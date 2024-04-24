<?php

use App\Domain\PackageAnalyser;
use App\Exceptions\NonExistentPackageDirectory;

test('throws exception for non existent package directory', function () {
    $nonExistentPackageDirectory = '/tmp/non-existent-package-directory';
    new PackageAnalyser($nonExistentPackageDirectory);
})->throws(NonExistentPackageDirectory::class, sprintf("Provided package directory '%s' does not exist", '/tmp/non-existent-package-directory'));
