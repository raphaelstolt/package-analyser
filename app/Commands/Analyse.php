<?php

declare(strict_types=1);

namespace App\Commands;

use App\Domain\PackageAnalyser;
use App\Domain\ReportWriter;
use App\Exceptions\NonExistentPackageDirectory;
use LaravelZero\Framework\Commands\Command;

class Analyse extends Command
{
    public const VERSION = '1.0.0';

    private PackageAnalyser $packageAnalyser;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'analyse {package-directory}
    {--open-php-package-checklist-link : Open the report in a browser}
    {--write-report : Write a HTML report to the filesystem}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Analyse the given 📦 and provide tips on best practices when required';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workingDirectory = getcwd();
        $packageDirectory = $this->argument('package-directory');

        try {
            $this->packageAnalyser = new PackageAnalyser($packageDirectory, $this->getOutput());
            $this->getOutput()->writeln('Analysing 📦 in directory <info>'.realpath($packageDirectory).'</info>');
            $amountOfAnalysisSteps = $this->packageAnalyser->analyse();
        } catch (NonExistentPackageDirectory $e) {
            $this->getOutput()->writeln($e->getMessage());

            return self::FAILURE;
        }

        $this->getOutput()->writeln('Ran <info>'.$amountOfAnalysisSteps.'</info> analysis steps');

        if (count($this->packageAnalyser->getViolations()) > 0) {
            if (count($this->packageAnalyser->getViolations()) === 1) {
                $this->getOutput()->writeln(
                    'Found <info>'.count($this->packageAnalyser->getViolations()).'</info> optimiseable aspect'
                );
            } else {
                $this->getOutput()->writeln(
                    'Found <info>'.count($this->packageAnalyser->getViolations()).'</info> optimiseable aspects'
                );
            }
        }

        $writeReportOption = $this->option('write-report');

        if ($writeReportOption) {
            $reportWriter = new ReportWriter($this->packageAnalyser);
            $reportWriter->write($workingDirectory);
            $this->getOutput()->writeln('Writing package analysis report to <info>'.$workingDirectory.'</info>');
        }

        return self::SUCCESS;
    }
}
