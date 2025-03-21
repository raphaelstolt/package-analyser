<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enum\ViolationStatus;
use App\Exceptions\NonExistentPackageDirectory;
use App\Exceptions\NonExistentStepId;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

class PackageAnalyser
{
    private array $steps;

    private array $stepIds;

    private bool $isACliOrTui = false;

    /**
     * @throws NonExistentPackageDirectory
     */
    public function __construct(readonly string $directoryToAnalyse)
    {
        if (! file_exists($directoryToAnalyse)) {
            $exceptionMessage = sprintf("Provided package directory '%s' does not exist.", $directoryToAnalyse);
            throw new NonExistentPackageDirectory($exceptionMessage);
        }

        $this->steps = [
            ['id' => 'php-package', 'summary' => 'The given package is written in 🐘.', 'status' => ViolationStatus::False],
            ['id' => 'changelog', 'summary' => 'Keep a CHANGELOG.md file in the base directory of the package.', 'status' => ViolationStatus::False],
            ['id' => 'tests', 'summary' => 'Write tests or specs for the package.', 'status' => ViolationStatus::False],
            ['id' => 'ci', 'summary' => 'Use continuous integration.', 'status' => ViolationStatus::False],
            ['id' => 'readme', 'summary' => 'Provide a README.md in the base directory of the package.', 'status' => ViolationStatus::False],
            ['id' => 'coding-style', 'summary' => 'Enforce a coding style.', 'status' => ViolationStatus::False],
            ['id' => 'static-analyse', 'summary' => 'Utilise static analysis tools like PHPStan.', 'status' => ViolationStatus::False],
            ['id' => 'semantic-versioning', 'summary' => 'Use Semantic Versioning to manage version numbers.', 'status' => ViolationStatus::False],
            ['id' => 'license', 'summary' => 'Include a license file in the base directory of the package.', 'status' => ViolationStatus::False],
            ['id' => 'gitignore', 'summary' => 'Keep a .gitignore file in the base directory of the package to keep unwanted files unversioned.', 'status' => ViolationStatus::False],
            ['id' => 'gitattributes', 'summary' => 'Keep a .gitattributes file in the base directory of the package to keep dist releases lean.', 'status' => ViolationStatus::False],
            ['id' => 'autoloader', 'summary' => 'Place domain code in a /src or app/ directory in the base directory of the package.', 'status' => ViolationStatus::False],
            ['id' => 'vcs', 'summary' => 'Utilise a source code management system like Git.', 'status' => ViolationStatus::False],
            ['id' => 'cli', 'summary' => 'The given package is a CLI/TUI.', 'status' => ViolationStatus::Irrelevant],
            ['id' => 'cli-binary', 'summary' => 'Put CLI/TUI binaries in a /bin directory in the base directory of the package.', 'status' => ViolationStatus::Irrelevant],
            ['id' => 'cli-phar', 'summary' => 'Distribute CLI/TUI binaries via PHAR.', 'status' => ViolationStatus::Irrelevant],
            ['id' => 'composer-scripts', 'summary' => 'Utilise Composer scripts.', 'status' => ViolationStatus::False],
            ['id' => 'eol-php', 'summary' => 'Use a supported PHP version.', 'status' => ViolationStatus::False],
            ['id' => 'peck', 'summary' => 'Utilise Peck for detecting spelling mistakes.', 'status' => ViolationStatus::False],
            ['id' => 'rector', 'summary' => 'Utilise Rector to continuously refactor your code.', 'status' => ViolationStatus::False],
            ['id' => 'composer-outdated', 'summary' => 'Update your direct Composer dependencies.', 'status' => ViolationStatus::False],
        ];

        $this->stepIds = Arr::pluck($this->steps, 'id');
    }

    /**
     * @throws NonExistentStepId
     */
    private function alternateStepStatus(string $stepId, ViolationStatus $status, array $stepsToOmit = []): void
    {
        if (! in_array($stepId, $this->stepIds)) {
            throw new NonExistentStepId("Step id '".$stepId."' does not exist.");
        }

        array_walk($this->steps, function ($array, $index) use ($stepId, $status, $stepsToOmit) {
            if (in_array($array['id'], $stepsToOmit)) {
                $this->steps[$index]['status'] = ViolationStatus::Omitted;

                return;
            }

            if ($array['id'] === $stepId) {
                $this->steps[$index]['status'] = $status;
            }
        });
    }

    /**
     * @throws NonExistentStepId
     */
    public function analyse(array $stepsToOmit): int
    {
        foreach ($this->steps as $step) {
            switch ($step['id']) {
                case 'php-package':
                    $this->alternateStepStatus('php-package', $this->isAPhpPackage(), $stepsToOmit);
                    break;
                case 'changelog':
                    $this->alternateStepStatus('changelog', $this->checkChangelogExistence(), $stepsToOmit);
                    break;
                case 'tests':
                    $this->alternateStepStatus('tests', ViolationStatus::True, $stepsToOmit);
                    if ($this->checkTestsDirectoryExistence() === ViolationStatus::False && $this->checkTestingToolExistence() === ViolationStatus::False) {
                        $this->alternateStepStatus('tests', ViolationStatus::False, $stepsToOmit);
                    }
                    break;
                case 'ci':
                    $this->alternateStepStatus('ci', $this->checkCiUsage(), $stepsToOmit);
                    break;
                case 'coding-style':
                    $this->alternateStepStatus('coding-style', $this->checkCodingStyleToolExistence(), $stepsToOmit);
                    break;
                case 'static-analyse':
                    $this->alternateStepStatus('static-analyse', $this->checkStaticAnalysisToolExistence(), $stepsToOmit);
                    break;
                case 'readme':
                    $this->alternateStepStatus('readme', $this->checkReadmeExistence(), $stepsToOmit);
                    break;
                case 'license':
                    $this->alternateStepStatus('license', $this->checkLicenseExistence(), $stepsToOmit);
                    break;
                case 'gitattributes':
                    $this->alternateStepStatus('gitattributes', $this->checkGitattributesExistence(), $stepsToOmit);
                    break;
                case 'gitignore':
                    $this->alternateStepStatus('gitignore', $this->checkGitignoreExistence(), $stepsToOmit);
                    break;
                case 'autoloader':
                    $this->alternateStepStatus('autoloader', $this->checkSrcOrAppExistence(), $stepsToOmit);
                    break;
                case 'semantic-versioning':
                    $this->alternateStepStatus('semantic-versioning', $this->checkSemanticVersioningUsage(), $stepsToOmit);
                    break;
                case 'vcs':
                    $this->alternateStepStatus('vcs', $this->checkVcsExistence(), $stepsToOmit);
                    break;
                case 'peck':
                    $this->alternateStepStatus('peck', $this->checkPeckExistence(), $stepsToOmit);
                    break;
                case 'rector':
                    $this->alternateStepStatus('rector', $this->checkRectorExistence(), $stepsToOmit);
                    break;
                case 'cli-binary':
                    $this->alternateStepStatus('cli-binary', $this->checkCliBinaryDirectoryExistence(), $stepsToOmit);
                    break;
                case 'cli-phar':
                    $this->alternateStepStatus('cli-phar', $this->checkPharConfigurationExistence(), $stepsToOmit);
                    break;
                case 'composer-scripts':
                    $this->alternateStepStatus('composer-scripts', $this->checkComposerScriptsExistence(), $stepsToOmit);
                    break;
                case 'composer-outdated':
                    $this->alternateStepStatus('composer-outdated', $this->checkComposerOutdatedDirectDependencies(), $stepsToOmit);
                    break;
                case 'eol-php':
                    $this->alternateStepStatus('eol-php', $this->checkComposerPHPVersion(), $stepsToOmit);
                    break;
            }
        }

        return count($this->steps);
    }

    private function checkTestsDirectoryExistence(): ViolationStatus
    {
        $finder = new Finder;
        if ($finder->depth(0)->directories()->name(['test*', 'spec*'])->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkTestingToolExistence(): ViolationStatus
    {
        $testingToolBinaries = [
            $this->directoryToAnalyse.'/vendor/bin/phpspec',
            $this->directoryToAnalyse.'/vendor/bin/phpunit',
            $this->directoryToAnalyse.'/vendor/bin/pest',
        ];

        foreach ($testingToolBinaries as $testingToolBinary) {
            if (file_exists($testingToolBinary)) {
                return ViolationStatus::True;
            }
        }

        return ViolationStatus::False;
    }

    private function checkChangelogExistence(): ViolationStatus
    {
        $finder = new Finder;
        if ($finder->depth(0)->files()->name('CHANGELOG*')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkReadmeExistence(): ViolationStatus
    {
        $finder = new Finder;
        if ($finder->depth(0)->files()->name('README*')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkLicenseExistence(): ViolationStatus
    {
        $finder = new Finder;
        if ($finder->depth(0)->files()->name('LICENSE*')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkCodingStyleToolExistence(): ViolationStatus
    {
        $codingStyleToolBinaries = [
            $this->directoryToAnalyse.'/vendor/bin/phpcs',
            $this->directoryToAnalyse.'/vendor/bin/phpcbf',
            $this->directoryToAnalyse.'/vendor/bin/pint',
            $this->directoryToAnalyse.'/vendor/bin/php-cs-fixer',
            $this->directoryToAnalyse.'/vendor/bin/ecs',
        ];

        foreach ($codingStyleToolBinaries as $codingStyleToolBinary) {
            if (file_exists($codingStyleToolBinary)) {
                return ViolationStatus::True;
            }
        }

        if (file_exists($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'mago.toml')) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkStaticAnalysisToolExistence(): ViolationStatus
    {
        $staticAnalysisToolBinaries = [
            $this->directoryToAnalyse.'/vendor/bin/phpstan',
        ];

        foreach ($staticAnalysisToolBinaries as $staticAnalysisToolBinary) {
            if (file_exists($staticAnalysisToolBinary)) {
                return ViolationStatus::True;
            }
        }

        return ViolationStatus::False;
    }

    private function checkCiUsage(): ViolationStatus
    {
        $finder = new Finder;
        $finder->ignoreDotFiles(false);

        if ($finder->depth(1)->path('.github/workflows')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        $finder = new Finder;
        $finder->ignoreDotFiles(false);

        if ($finder->depth(0)->files()->name('.gitlab-ci*')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkGitattributesExistence(): ViolationStatus
    {
        $finder = new Finder;
        $finder->ignoreDotFiles(false);

        if ($finder->depth(0)->files()->name('.gitattributes')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkGitignoreExistence(): ViolationStatus
    {
        $finder = new Finder;
        $finder->ignoreDotFiles(false);

        if ($finder->depth(0)->files()->name('.gitignore')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkSrcOrAppExistence(): ViolationStatus
    {
        $finder = new Finder;

        if ($finder->depth(0)->path(['app', 'src'])->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkSemanticVersioningUsage(): ViolationStatus
    {
        if ($this->checkVcsExistence() === ViolationStatus::False) {
            return ViolationStatus::False;
        }

        exec('cd '.realpath($this->getDirectoryToAnalyse()).' && git tag --list 2>&1', $tags);

        if (count($tags) === 0) {
            return ViolationStatus::False;
        }

        $usesSemanticVersioning = false;
        foreach ($tags as $tag) {
            $tag = str_replace(['v', 'rc-', 'rc', 'V', 'RC-', 'RC'], '', $tag);
            if (preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $tag)) {
                $usesSemanticVersioning = true;
            }
        }

        if ($usesSemanticVersioning === true) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkVcsExistence(): ViolationStatus
    {
        $finder = new Finder;
        $finder->ignoreDotFiles(false);
        $finder->ignoreVCS(false);

        if ($finder->depth(0)->path('.git')->in($this->directoryToAnalyse)->hasResults()) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkRectorExistence(): ViolationStatus
    {
        $finder = new Finder;
        $finder->ignoreDotFiles(false);
        $finder->ignoreVCS(false);

        if ($finder->depth(0)->path('vendor/bin')->in($this->directoryToAnalyse)->contains('rector')) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    private function checkPeckExistence(): ViolationStatus
    {
        $finder = new Finder;
        $finder->ignoreDotFiles(false);
        $finder->ignoreVCS(false);

        if ($finder->depth(0)->path('vendor/bin')->in($this->directoryToAnalyse)->contains('peck')) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    public function getDirectoryToAnalyse(): string
    {
        return $this->directoryToAnalyse;
    }

    private function isAPhpPackage(): ViolationStatus
    {
        if (file_exists($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json')) {
            return ViolationStatus::True;
        }

        return ViolationStatus::False;
    }

    /**
     * @throws NonExistentStepId
     */
    private function checkCliBinaryDirectoryExistence(): ViolationStatus
    {
        if ($this->isAPhpPackage() === ViolationStatus::True) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json'), true);

            if (isset($composerJson['keywords'])) {
                $matchingKeywords = array_filter($composerJson['keywords'], fn ($keyword) => in_array($keyword, ['cli', 'tui', 'console']));

                if (count($matchingKeywords) > 0) {
                    $this->isACliOrTui = true;
                    $this->alternateStepStatus('cli', ViolationStatus::True);
                }

                if ($this->isACliOrTui) {
                    $finder = new Finder;

                    if ($finder->depth(0)->path('bin')->in($this->directoryToAnalyse)->hasResults()) {
                        $this->alternateStepStatus('cli-binary', ViolationStatus::True);

                        return ViolationStatus::True;
                    }

                    $this->alternateStepStatus('cli-binary', ViolationStatus::False);

                    return ViolationStatus::False;
                }
            }

            return ViolationStatus::Irrelevant;
        }

        return ViolationStatus::Irrelevant;
    }

    private function checkPharConfigurationExistence(): ViolationStatus
    {
        if ($this->isAPhpPackage() === ViolationStatus::True) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json'), true);

            if (isset($composerJson['keywords'])) {
                $matchingKeywords = array_filter($composerJson['keywords'], fn ($keyword) => in_array($keyword, ['cli', 'tui', 'console']));

                if (count($matchingKeywords) > 0) {
                    $this->isACliOrTui = true;
                }

                if ($this->isACliOrTui) {
                    $finder = new Finder;

                    if ($finder->depth(0)->files()->name('box.json*')->in($this->directoryToAnalyse)->hasResults()) {
                        return ViolationStatus::True;
                    }

                    return ViolationStatus::True;
                }
            }

            return ViolationStatus::Irrelevant;
        }

        return ViolationStatus::Irrelevant;
    }

    private function checkComposerScriptsExistence(): ViolationStatus
    {
        if ($this->isAPhpPackage() === ViolationStatus::True) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json'), true);

            if (isset($composerJson['scripts'])) {
                if (count($composerJson['scripts']) > 0) {
                    return ViolationStatus::True;
                }
            }

            return ViolationStatus::False;
        }

        return ViolationStatus::False;
    }

    private function checkComposerOutdatedDirectDependencies(): ViolationStatus
    {
        if ($this->isAPhpPackage() === ViolationStatus::True) {
            $composerOutdatedCommand = 'composer outdated --format=json --direct';

            exec($composerOutdatedCommand, $output, $returnCode);
            $output = implode(PHP_EOL, $output);
            $outdatedJson = json_decode($output, true);

            if ($outdatedJson === '' || ! isset($outdatedJson['installed'])) {
                return ViolationStatus::False;
            }

            if (count($outdatedJson['installed']) > 0) {
                return ViolationStatus::True;
            }

            return ViolationStatus::False;
        }

        return ViolationStatus::False;
    }

    private function checkComposerPHPVersion(): ViolationStatus
    {
        $latestSupportedPHPVersion = 8.1;

        if ($this->isAPhpPackage() === ViolationStatus::True) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json'), true);

            if (isset($composerJson['require']['php'])) {
                if (floatval(str_replace(['^', '~', '>='], '', $composerJson['require']['php'])) >= floatval($latestSupportedPHPVersion)) {
                    return ViolationStatus::True;
                }
            }

            return ViolationStatus::False;
        }

        return ViolationStatus::False;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getViolations(): array
    {
        return array_filter($this->steps, fn ($step) => $step['status'] === ViolationStatus::False);
    }

    public function getStepsForTable(): array
    {
        $index = 1;
        $steps = [];

        foreach ($this->steps as $step) {
            $status = ' ⛔';
            if ($step['status'] === ViolationStatus::True) {
                $status = ' ✅';
            }

            if ($step['status'] === ViolationStatus::Irrelevant || $step['status'] === ViolationStatus::Omitted) {
                $status = ' 🔕';
            }
            $steps[] = ['id' => $index, 'summary' => $step['summary'], 'status' => $status];
            $index++;
        }

        return $steps;
    }
}
