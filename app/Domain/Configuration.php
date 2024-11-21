<?php

namespace App\Domain;

use App\Exceptions\InvalidConfiguration;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    const CONFIGURATION_FILE = '.pa.yml';

    private string $config;

    private mixed $yamlConfig = null;

    public function __construct(readonly PackageAnalyser $analyser, $directoryWithConfigFile = null)
    {
        $this->config = realpath($directoryWithConfigFile.DIRECTORY_SEPARATOR.self::CONFIGURATION_FILE);

        if ($directoryWithConfigFile === null) {
            $this->config = realpath(getcwd().DIRECTORY_SEPARATOR.self::CONFIGURATION_FILE);
        }
    }

    /**
     * @throws InvalidConfiguration
     */
    public function hasConfiguration(): bool
    {
        if (file_exists($this->config)) {
            if ($this->yamlConfig === null) {
                $this->yamlConfig = Yaml::parseFile($this->config);
            }
            if (is_array($this->yamlConfig)) {
                if (array_key_exists('violationThreshold', $this->yamlConfig) && is_int($this->yamlConfig['violationThreshold']) === false) {
                    throw new InvalidConfiguration('No numeric violation threshold provided.');
                }
                if (array_key_exists('stepsToOmit', $this->yamlConfig)) {
                    if (is_array($this->yamlConfig['stepsToOmit']) === false) {
                        throw new InvalidConfiguration('No array of steps to omit provided.');
                    }
                    $availableStepsToOmit = array_map(function ($step) {
                        return $step['id'];
                    }, $this->analyser->getSteps());

                    foreach ($this->yamlConfig['stepsToOmit'] as $stepToOmit) {
                        if (! in_array($stepToOmit, $availableStepsToOmit)) {
                            throw new InvalidConfiguration(sprintf("Unknown step '%s' provided.", $stepToOmit));
                        }
                    }
                }

                return true;
            }

            return false;
        }

        return false;
    }

    public function getStepsToOmit(): array
    {
        if ($this->yamlConfig === null) {
            $this->yamlConfig = Yaml::parseFile($this->config);
        }

        return $this->yamlConfig['stepsToOmit'];
    }

    public function getViolationThreshold(): int
    {
        if ($this->yamlConfig === null) {
            $this->yamlConfig = Yaml::parseFile($this->config);
        }

        return $this->yamlConfig['violationThreshold'];
    }
}
