<?php

use App\Domain\PackageAnalyser;
use App\Domain\ReportWriter;

test('uses expected emoji for irrelevant violations', function () {
    $reportWriter = new ReportWriter(new PackageAnalyser('/tmp'), '/tmp');
    $reportWriter->write();

    expect(file_get_contents('/tmp/pa-report.html'))->toContain('🔕');
    \unlink('pa-report.html');
});
