<?php

declare(strict_types=1);

namespace App\Commands;

use App\Domain\Configuration;
use App\Domain\PackageAnalyser;
use App\Domain\ReportWriter;
use App\Exceptions\NonExistentPackageDirectory;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class Analyse extends Command
{
    public const VERSION = '1.0.8';

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'analyse {package-directory}
    {--configuration=.pa.yml : Use provided configuration file}
    {--write-report : Write a HTML report to the filesystem}
    {--violations-threshold=0 : Threshold of allowed violations}';

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
        $providedConfiguration = $this->option('configuration');
        $violationsThreshold = $this->option('violations-threshold');
        $violationText = $omitText = '';

        try {
            $packageAnalyser = new PackageAnalyser($packageDirectory);
            $this->getOutput()->writeln('Analysing 📦 in directory <info>'.realpath($packageDirectory).'</info>');
            $configuration = new Configuration($packageAnalyser, $workingDirectory, $providedConfiguration);
            $stepsToOmit = [];

            if ($configuration->hasConfiguration()) {
                $this->getOutput()->writeln('Using configuration <info>'.realpath($providedConfiguration).'</info>');
                $stepsToOmit = $configuration->getStepsToOmit();
            }

            $amountOfAnalysisSteps = $packageAnalyser->analyse($stepsToOmit);

            if (count($packageAnalyser->getViolations()) > 0) {
                $violationText = 'Found <info>'.count($packageAnalyser->getViolations()).'</info> optimiseable aspect.';
                if (count($packageAnalyser->getViolations()) > 1) {
                    $violationText = 'Found <info>'.count($packageAnalyser->getViolations()).'</info> optimiseable aspects.';
                }
            }

            if (count($stepsToOmit) > 0) {
                $omitText = 'Omitted <info>'.count($stepsToOmit).'</info> analyse step.';
                if (count($stepsToOmit) > 1) {
                    $omitText = 'Omitted <info>'.count($stepsToOmit).'</info> analyse steps.';
                }
            }

            $table = new Table($this->getOutput());
            $table->setHeaders(['#', 'Analyse step', 'Status']);
            $table->setRows([
                $packageAnalyser->getStepsForTable()[0],
                $packageAnalyser->getStepsForTable()[1],
                $packageAnalyser->getStepsForTable()[2],
                $packageAnalyser->getStepsForTable()[3],
                $packageAnalyser->getStepsForTable()[4],
                $packageAnalyser->getStepsForTable()[5],
                $packageAnalyser->getStepsForTable()[6],
                $packageAnalyser->getStepsForTable()[7],
                $packageAnalyser->getStepsForTable()[8],
                $packageAnalyser->getStepsForTable()[9],
                $packageAnalyser->getStepsForTable()[10],
                $packageAnalyser->getStepsForTable()[11],
                $packageAnalyser->getStepsForTable()[12],
                $packageAnalyser->getStepsForTable()[13],
                $packageAnalyser->getStepsForTable()[14],
                $packageAnalyser->getStepsForTable()[15],
                $packageAnalyser->getStepsForTable()[16],
                $packageAnalyser->getStepsForTable()[17],
                $packageAnalyser->getStepsForTable()[18],
                $packageAnalyser->getStepsForTable()[19],
                $packageAnalyser->getStepsForTable()[20],
                new TableSeparator,
                [new TableCell('Ran <info>'.$amountOfAnalysisSteps.'</info> analysis steps. '.$violationText.$omitText, ['colspan' => 3])],
            ]);

            $table->render();
        } catch (NonExistentPackageDirectory $e) {
            $this->getOutput()->writeln($e->getMessage());

            return self::FAILURE;
        }

        $writeReportOption = $this->option('write-report');

        if ($writeReportOption) {
            $reportWriter = new ReportWriter($packageAnalyser, $workingDirectory);
            $reportWriter->write();
            $this->getOutput()->writeln('Writing package analysis report to <info>'.$workingDirectory.'</info>');
        }

        if (count($packageAnalyser->getViolations()) > $violationsThreshold) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
