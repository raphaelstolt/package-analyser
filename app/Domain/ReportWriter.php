<?php

declare(strict_types=1);

namespace App\Domain;

class ReportWriter
{
    private array $analyseSteps;

    private $directoryAnalysed;

    public function __construct(PackageAnalyser $analyser)
    {
        $this->analyseSteps = $analyser->getSteps();
        $this->directoryAnalysed = $analyser->getDirectoryToAnalyse();
    }

    public function write(string $outputDirectory): bool
    {
        $reportTemplateContent = file_get_contents(realpath('app/Templates/report.html'));

        $tbodyContent = '';
        foreach ($this->analyseSteps as $index => $analyseStep) {
            $trClass = 'table-success';
            $statusEmoji = '✅';
            if ($analyseStep['status'] === false) {
                $trClass = 'table-danger';
                $statusEmoji = '⛔';
            }

            $tbodyContent .= '<tr class="' . $trClass . '">
        <th scope="row">' . $index + 1 . '</th>
        <td>' . $analyseStep['summary'] . '</td>
        <td>' . $statusEmoji . '</td>
      </tr>' . PHP_EOL;
        }

        file_put_contents(
            $outputDirectory . DIRECTORY_SEPARATOR . 'pa-report.html',
            str_replace(['{{ directory }}', '{{ tbody }}'], [realpath($this->directoryAnalysed), $tbodyContent], $reportTemplateContent)
        );
        return true;
    }
}
