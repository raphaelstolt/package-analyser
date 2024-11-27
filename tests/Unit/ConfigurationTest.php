<?php

use App\Domain\Configuration;
use App\Domain\PackageAnalyser;
use App\Exceptions\InvalidConfiguration;

test('it detects non available and available configuration', function () {
    $configuration = new Configuration(new PackageAnalyser(getcwd()));

    expect($configuration->hasConfiguration())->toBeFalse();

    $yamlContent = <<<'YAML'
stepsToOmit:
    - eol-php
YAML;

    file_put_contents('/tmp/.pa.yml', $yamlContent);
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');

    expect($configuration->hasConfiguration())->toBeTrue();

    unlink('/tmp/.pa.yml');
});

test('it detects invalid configuration', function () {
    file_put_contents('/tmp/.pa.yml', 'invalid');
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');

    expect($configuration->hasConfiguration())->toBeFalse();

    unlink('/tmp/.pa.yml');
});

test('it detects steps to omit misconfiguration', function () {
    $yamlContent = <<<'YAML'
stepsToOmit: no-array
YAML;

    file_put_contents('/tmp/.pa.yml', $yamlContent);
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');

    expect($configuration->hasConfiguration())->toBeFalse();

    unlink('/tmp/.pa.yml');
})->throws(InvalidConfiguration::class, 'No array of steps to omit provided.');

test('it detects non existent step to omit misconfiguration', function () {
    $yamlContent = <<<'YAML'
stepsToOmit:
    - eol-php
    - cli-phar
    - non-existent-step
YAML;

    file_put_contents('/tmp/.pa.yml', $yamlContent);
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');

    expect($configuration->hasConfiguration())->toBeFalse();

    unlink('/tmp/.pa.yml');
})->throws(InvalidConfiguration::class, sprintf("Unknown step '%s' provided.", 'non-existent-step'));

test('it returns steps to omit', function () {
    $yamlContent = <<<'YAML'
stepsToOmit:
    - eol-php
    - cli-phar
YAML;

    file_put_contents('/tmp/.pa.yml', $yamlContent);
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');

    expect($configuration->hasConfiguration())->toBeTrue();
    expect($configuration->getStepsToOmit())->toBe(['eol-php', 'cli-phar']);

    unlink('/tmp/.pa.yml');
});
