<?php

declare(strict_types=1);

namespace App\Domain;

use App\Commands\Analyse;
use App\Enum\ViolationStatus;

class ReportWriter
{
    private array $analyseSteps;

    private string $directoryAnalysed;

    public function __construct(PackageAnalyser $analyser, readonly string $outputDirectory)
    {
        $this->analyseSteps = $analyser->getSteps();
        $this->directoryAnalysed = $analyser->getDirectoryToAnalyse();
    }

    public function write(): bool
    {
        $reportTemplateContent = file_get_contents(realpath('app/Templates/report.html'));

        $tbodyContent = '';
        foreach ($this->analyseSteps as $index => $analyseStep) {
            $trClass = 'table-success';
            $statusEmoji = '✅';
            if ($analyseStep['status'] === ViolationStatus::False) {
                $trClass = 'table-danger';
                $statusEmoji = '⛔';
            }

            if ($analyseStep['status'] === ViolationStatus::Irrelevant) {
                $trClass = 'table-secondary';
                $statusEmoji = '🔕';
            }

            $tbodyContent .= '<tr class="'.$trClass.'">
        <th scope="row">'.$index + 1 .'</th>
        <td>'.$analyseStep['summary'].'</td>
        <td>'.$statusEmoji.'</td>
      </tr>'.PHP_EOL;
        }

        file_put_contents(
            $this->outputDirectory.DIRECTORY_SEPARATOR.'pa-report.html',
            str_replace(
                ['{{ directory }}', '{{ tbody }}', '{{ pa_version }}'],
                [realpath($this->directoryAnalysed), $tbodyContent, Analyse::VERSION],
                $reportTemplateContent
            )
        );

        return true;
    }
}
