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

test('it detects violation threshold misconfiguration', function () {
    $yamlContent = <<<'YAML'
stepsToOmit:
    - eol-php
    - cli-phar
    - non-existent-step

violationThreshold: zwei
YAML;

    file_put_contents('/tmp/.pa.yml', $yamlContent);
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');
    expect($configuration->hasConfiguration())->toBeFalse();

    unlink('/tmp/.pa.yml');
})->throws(InvalidConfiguration::class, 'No numeric violation threshold provided.');

test('it detects steps to omit misconfiguration', function () {
    $yamlContent = <<<'YAML'
stepsToOmit: non-sense
violationThreshold: 2
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

violationThreshold: 0
YAML;

    file_put_contents('/tmp/.pa.yml', $yamlContent);
    $configuration = new Configuration(new PackageAnalyser(getcwd()), '/tmp');
    expect($configuration->hasConfiguration())->toBeFalse();

    unlink('/tmp/.pa.yml');
})->throws(InvalidConfiguration::class, sprintf("Unknown step '%s' provided.", 'non-existent-step'));
