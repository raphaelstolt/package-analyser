<?php

use App\Domain\PackageAnalyser;
use App\Enum\ViolationStatus;
use App\Exceptions\NonExistentPackageDirectory;
use App\Exceptions\NonExistentStepId;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

afterEach(function () {
    (new Filesystem)->deleteDirectory('/tmp/pa-unit-tagged');
});

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

test('changelog latest version step is irrelevant without tags', function () {
    $packageDirectory = setUpTaggedPackage([], '');

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::Irrelevant);
});

test('changelog latest version step is irrelevant without a vcs', function () {
    $packageDirectory = '/tmp/pa-unit-vcs-less';
    if (! file_exists($packageDirectory)) {
        mkdir($packageDirectory, 0777, true);
    }

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::Irrelevant);

    (new Filesystem)->deleteDirectory($packageDirectory);
});

test('changelog latest version step fails without a changelog', function () {
    $packageDirectory = setUpTaggedPackage(['v1.0.0'], null);

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::False);
});

test('changelog latest version step detects the highest version tag', function () {
    $packageDirectory = setUpTaggedPackage(['v1.0.0', 'v1.9.0', 'v1.10.0'], "## [v1.9.0] - 2024-11-21\n");

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::False);

    $packageDirectory = setUpTaggedPackage(['v1.0.0', 'v1.9.0', 'v1.10.0'], "## [v1.10.0] - 2024-11-21\n");

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::True);
});

test('changelog latest version step matches release candidate tags', function () {
    $packageDirectory = setUpTaggedPackage(['RC-1.0.0'], "## [1.0.0] - 2024-11-21\n");

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::True);
});

test('changelog latest version step does not match a version prefix', function () {
    $packageDirectory = setUpTaggedPackage(['v1.5.1'], "## [v1.5.10] - 2024-11-21\n");

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::False);
});

test('changelog latest version step ignores non heading version mentions', function () {
    $packageDirectory = setUpTaggedPackage(['v1.0.0'], "# Change Log\n\nUpgrade from v1.0.0 via Composer.\n");

    expect(checkChangelogLatestVersionEntry($packageDirectory))->toBe(ViolationStatus::False);
});

function checkChangelogLatestVersionEntry(string $packageDirectory): ViolationStatus
{
    $packageAnalyser = new PackageAnalyser($packageDirectory);

    $reflection = new ReflectionClass($packageAnalyser);
    $method = $reflection->getMethod('checkChangelogLatestVersionEntry');

    return $method->invoke($packageAnalyser);
}

function setUpTaggedPackage(array $tags, ?string $changelogContent): string
{
    $packageDirectory = '/tmp/pa-unit-tagged';
    $filesystem = new Filesystem;
    $filesystem->deleteDirectory($packageDirectory);
    mkdir($packageDirectory, 0777, true);

    exec('cd '.$packageDirectory.' && git init 2>&1');
    exec('cd '.$packageDirectory.' && git config user.email "raphael.stolt@gmail.com" && git config user.name "Raphael Stolt" 2>&1');
    exec('cd '.$packageDirectory.' && touch foo.txt && git add foo.txt && git commit -m "Foo" 2>&1');

    foreach ($tags as $tag) {
        exec('cd '.$packageDirectory.' && git tag '.$tag.' 2>&1');
    }

    if ($changelogContent !== null) {
        file_put_contents($packageDirectory.DIRECTORY_SEPARATOR.'CHANGELOG.md', $changelogContent);
    }

    return $packageDirectory;
}

test('has emojis for console table output', function () {
    $packageAnalyser = new PackageAnalyser('/tmp');
    $violations = $packageAnalyser->getViolations();
    foreach ($violations as $violation) {
        expect($violation['status'])->toBe(ViolationStatus::False);
    }
    $emojisForConsoleTable = array_unique(Arr::pluck($packageAnalyser->getStepsForTable(), 'status'));
    expect($emojisForConsoleTable)->toContain(' ⛔', ' 🔕');
});
