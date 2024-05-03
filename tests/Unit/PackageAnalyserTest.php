<?php

use App\Domain\PackageAnalyser;
use App\Enum\ViolationStatus;
use App\Exceptions\NonExistentPackageDirectory;

test('throws exception for non existent package directory', function () {
    $nonExistentPackageDirectory = '/tmp/non-existent-package-directory';
    new PackageAnalyser($nonExistentPackageDirectory);
})->throws(NonExistentPackageDirectory::class, sprintf("Provided package directory '%s' does not exist", '/tmp/non-existent-package-directory'));

test('violations have status ViolationStatus::False', function () {
    $packageAnalyser = new PackageAnalyser('/tmp');
    $violations = $packageAnalyser->getViolations();
    foreach ($violations as $violation) {
        expect($violation['status'])->toBe(ViolationStatus::False);
    }
});
