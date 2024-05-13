<?php

use App\Domain\PackageAnalyser;
use App\Enum\ViolationStatus;
use App\Exceptions\NonExistentPackageDirectory;
use App\Exceptions\NonExistentStepId;
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

test('throws exception for non existent violation step id', function () {
    $packageAnalyser = new PackageAnalyser('/tmp');

    $reflection = new ReflectionClass($packageAnalyser);
    $method = $reflection->getMethod('alternateStepStatus');

    $method->invokeArgs($packageAnalyser, ['non-existent-step-id', ViolationStatus::Irrelevant]);
})->throws(NonExistentStepId::class, sprintf("Step id '%s' does not exist", 'non-existent-step-id'));

test('has emojis for console table output', function () {
    $packageAnalyser = new PackageAnalyser('/tmp');
    $violations = $packageAnalyser->getViolations();
    foreach ($violations as $violation) {
        expect($violation['status'])->toBe(ViolationStatus::False);
    }
    $emojisForConsoleTable = array_unique(Arr::pluck($packageAnalyser->getStepsForTable(), 'status'));
    expect($emojisForConsoleTable)->toContain(' ⛔', ' 🔕');
});
