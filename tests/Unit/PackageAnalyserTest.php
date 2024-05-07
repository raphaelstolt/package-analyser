<?php

use App\Domain\PackageAnalyser;
use App\Enum\ViolationStatus;
use App\Exceptions\NonExistentPackageDirectory;
use Illuminate\Support\Arr;

test('throws exception for non existent package directory', function () {
    $nonExistentPackageDirectory = '/tmp/non-existent-package-directory';
    new PackageAnalyser($nonExistentPackageDirectory);
})->throws(NonExistentPackageDirectory::class, sprintf("Provided package directory '%s' does not exist", '/tmp/non-existent-package-directory'));

test('violations have default status ViolationStatus::False', function () {
    $packageAnalyser = new PackageAnalyser('/tmp');
    $violations = $packageAnalyser->getViolations();
    foreach ($violations as $violation) {
        expect($violation['status'])->toBe(ViolationStatus::False);
    }
});

test('has emojis for console table output', function () {
    $packageAnalyser = new PackageAnalyser('/tmp');
    $violations = $packageAnalyser->getViolations();
    foreach ($violations as $violation) {
        expect($violation['status'])->toBe(ViolationStatus::False);
    }
    $emojisForConsoleTable = array_unique(Arr::pluck($packageAnalyser->getStepsForTable(), 'status'));
    expect($emojisForConsoleTable)->toContain(' ⛔', ' 🔕');
});
