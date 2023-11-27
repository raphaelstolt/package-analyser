<?php

declare(strict_types=1);

namespace App\Domain;

class ReportWriter
{
    private array $violations;

    private array $analyseSteps;

    public function __construct(PackageAnalyser $analyser)
    {
        $this->violations = $analyser->getViolations();
        $this->analyseSteps = $analyser->getSteps();
    }

    public function write(string $outputDirectory): bool
    {
        return true;
    }
}
