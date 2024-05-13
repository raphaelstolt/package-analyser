<?php

declare(strict_types=1);

namespace App\Commands;

use App\Domain\PackageAnalyser;
use App\Domain\ReportWriter;
use App\Exceptions\NonExistentPackageDirectory;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class Analyse extends Command
{
    public const VERSION = '1.0.7';

    private PackageAnalyser $packageAnalyser;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'analyse {package-directory}
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
        $violationsThresholdOption = $this->option('violations-threshold');
        $violationText = '';

        try {
            $this->packageAnalyser = new PackageAnalyser($packageDirectory);
            $this->getOutput()->writeln('Analysing 📦 in directory <info>'.realpath($packageDirectory).'</info>');
            $amountOfAnalysisSteps = $this->packageAnalyser->analyse();

            if (count($this->packageAnalyser->getViolations()) > 0) {
                $violationText = 'Found <info>'.count($this->packageAnalyser->getViolations()).'</info> optimiseable aspect.';
                if (count($this->packageAnalyser->getViolations()) > 1) {
                    $violationText = 'Found <info>'.count($this->packageAnalyser->getViolations()).'</info> optimiseable aspects.';
                }
            }

            $table = new Table($this->getOutput());
            $table->setHeaders(['#', 'Analyse step', 'Status']);
            $table->setRows([
                $this->packageAnalyser->getStepsForTable()[0],
                $this->packageAnalyser->getStepsForTable()[1],
                $this->packageAnalyser->getStepsForTable()[2],
                $this->packageAnalyser->getStepsForTable()[3],
                $this->packageAnalyser->getStepsForTable()[4],
                $this->packageAnalyser->getStepsForTable()[5],
                $this->packageAnalyser->getStepsForTable()[6],
                $this->packageAnalyser->getStepsForTable()[7],
                $this->packageAnalyser->getStepsForTable()[8],
                $this->packageAnalyser->getStepsForTable()[9],
                $this->packageAnalyser->getStepsForTable()[10],
                $this->packageAnalyser->getStepsForTable()[11],
                $this->packageAnalyser->getStepsForTable()[12],
                $this->packageAnalyser->getStepsForTable()[13],
                $this->packageAnalyser->getStepsForTable()[14],
                $this->packageAnalyser->getStepsForTable()[15],
                $this->packageAnalyser->getStepsForTable()[16],
                new TableSeparator(),
                [new TableCell('Ran <info>'.$amountOfAnalysisSteps.'</info> analysis steps. '.$violationText, ['colspan' => 3])],
            ]);

            $table->render();
        } catch (NonExistentPackageDirectory $e) {
            $this->getOutput()->writeln($e->getMessage());

            return self::FAILURE;
        }

        $writeReportOption = $this->option('write-report');

        if ($writeReportOption) {
            $reportWriter = new ReportWriter($this->packageAnalyser, $workingDirectory);
            $reportWriter->write();
            $this->getOutput()->writeln('Writing package analysis report to <info>'.$workingDirectory.'</info>');
        }

        if (count($this->packageAnalyser->getViolations()) > $violationsThresholdOption) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
