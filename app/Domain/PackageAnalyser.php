<?php

declare(strict_types=1);

namespace App\Domain;

use App\Exceptions\NonExistentPackageDirectory;
use App\Exceptions\NonExistentStepId;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Finder\Finder;

class PackageAnalyser
{
    private string $directoryToAnalyse;

    private array $steps;

    private array $stepIds;

    private bool $isACliOrTui = false;

    private OutputStyle $output;

    /**
     * @throws NonExistentPackageDirectory
     */
    public function __construct(string $directoryToAnalyse)
    {
        $this->directoryToAnalyse = $directoryToAnalyse;

        if (! file_exists($directoryToAnalyse)) {
            $exceptionMessage = sprintf("Provided package directory '%s' does not exist.", $directoryToAnalyse);
            throw new NonExistentPackageDirectory($exceptionMessage);
        }

        $this->steps = [
            ['id' => 'php-package', 'summary' => 'The given package is written in 🐘.', 'status' => false],
            ['id' => 'changelog', 'summary' => 'Keep a CHANGELOG.md file in the base directory of the package.', 'status' => false],
            ['id' => 'tests', 'summary' => 'Write tests or specs for the package.', 'status' => false],
            ['id' => 'ci', 'summary' => 'Use continuous integration.', 'status' => false],
            ['id' => 'readme', 'summary' => 'Provide a README.md in the base directory of the package.', 'status' => false],
            ['id' => 'coding-style', 'summary' => 'Enforce a coding style.', 'status' => false],
            ['id' => 'semantic-versioning', 'summary' => 'Use Semantic Versioning to manage version numbers.', 'status' => false],
            ['id' => 'license', 'summary' => 'Include a license file in the base directory of the package.', 'status' => false],
            ['id' => 'gitattributes', 'summary' => 'Keep a .gitattributes file in the base directory of the package to keep dist releases lean.', 'status' => false],
            ['id' => 'autoloader', 'summary' => 'Place domain code in a /src or app/ directory in the base directory of the package.', 'status' => false],
            ['id' => 'vcs', 'summary' => 'Utilise a source code management system like Git.', 'status' => false],
            ['id' => 'cli', 'summary' => 'The given package is a CLI/TUI.', 'status' => false],
            ['id' => 'cli-binary', 'summary' => 'Put CLI/TUI binaries in a /bin directory in the base directory of the package.', 'status' => false],
            ['id' => 'cli-phar', 'summary' => 'Distribute CLI/TUI binaries via PHAR.', 'status' => false],
            ['id' => 'composer-scripts', 'summary' => 'Utilise Composer scripts.', 'status' => false],
        ];
        $this->stepIds = [];

        foreach ($this->steps as $step) {
            $this->stepIds[] = $step['id'];
        }
    }

    /**
     * @throws NonExistentStepId
     */
    private function alternateStepStatus(string $stepId, bool $status): void
    {
        if (! in_array($stepId, $this->stepIds)) {
            throw new NonExistentStepId('Step id '.$stepId.'does not exist.');
        }

        foreach ($this->steps as $index => $step) {
            if ($step['id'] === $stepId) {
                $this->steps[$index]['status'] = $status;
            }
        }
    }

    /**
     * @throws NonExistentStepId
     */
    public function analyse(): int
    {
        foreach ($this->steps as $step) {
            switch ($step['id']) {
                case 'changelog':
                    $this->alternateStepStatus('changelog', $this->checkChangelogExistence());
                    break;
                case 'tests':
                    $status = $this->checkTestsDirectoryExistence() && $this->checkTestingToolExistence();
                    $this->alternateStepStatus('tests', $status);
                    break;
                case 'ci':
                    $this->alternateStepStatus('ci', $this->checkCiUsage());
                    break;
                case 'coding-style':
                    $this->alternateStepStatus('coding-style', $this->checkCodingStyleToolExistence());
                    break;
                case 'readme':
                    $this->alternateStepStatus('readme', $this->checkReadmeExistence());
                    break;
                case 'license':
                    $this->alternateStepStatus('license', $this->checkLicenseExistence());
                    break;
                case 'gitattributes':
                    $this->alternateStepStatus('gitattributes', $this->checkGitattributesExistence());
                    break;
                case 'autoloader':
                    $this->alternateStepStatus('autoloader', $this->checkSrcOrAppExistence());
                    break;
                case 'semantic-versioning':
                    $this->alternateStepStatus('semantic-versioning', $this->checkSemanticVersioningUsage());
                    break;
                case 'vcs':
                    $this->alternateStepStatus('vcs', $this->checkVcsExistence());
                    break;
                case 'cli-binary':
                    $this->alternateStepStatus('php-package', $this->isAPhpPackage());
                    $this->alternateStepStatus('cli-binary', $this->checkCliBinaryDirectoryExistence());
                    break;
                case 'cli-phar':
                    $this->alternateStepStatus('cli-phar', $this->checkPharConfigurationExistence());
                    break;
                case 'composer-scripts':
                    $this->alternateStepStatus('composer-scripts', $this->checkComposerScriptsExistence());
                    break;
            }
        }

        return count($this->steps);
    }

    private function checkTestsDirectoryExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->directories()->name(['test*', 'spec*'])->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkTestingToolExistence(): bool
    {
        $testingToolBinaries = [
            './vendor/bin/phpspec',
            './vendor/bin/phpunit',
            './vendor/bin/pest',
        ];

        foreach ($testingToolBinaries as $testingToolBinary) {
            if (file_exists($testingToolBinary)) {
                return true;
            }
        }

        return false;
    }

    private function checkChangelogExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->files()->name('CHANGELOG*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkReadmeExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->files()->name('README*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkLicenseExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->files()->name('LICENSE*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkCodingStyleToolExistence(): bool
    {
        $codingStyleToolBinaries = [
            './vendor/bin/phpcs',
            './vendor/bin/phpcbf',
            './vendor/bin/pint',
            './vendor/bin/php-cs-fixer',
        ];

        foreach ($codingStyleToolBinaries as $codingStyleToolBinary) {
            if (file_exists($codingStyleToolBinary)) {
                return true;
            }
        }

        return false;
    }

    private function checkCiUsage(): bool
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);

        if ($finder->depth(1)->path('.github/workflows')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        $finder = new Finder();
        $finder->ignoreDotFiles(false);

        if ($finder->depth(0)->files()->name('.gitlab-ci*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkGitattributesExistence(): bool
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);

        if ($finder->depth(0)->files()->name('.gitattributes')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkSrcOrAppExistence(): bool
    {
        $finder = new Finder();

        if ($finder->depth(0)->path(['app', 'src'])->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    private function checkSemanticVersioningUsage(): bool
    {
        if ($this->checkVcsExistence() === false) {
            return false;
        }

        exec('cd '.realpath($this->getDirectoryToAnalyse()).' && git tag --list', $tags);

        if (count($tags) === 0) {
            return false;
        }

        $usesSemanticVersioning = false;
        foreach ($tags as $tag) {
            $tag = str_replace('v', '', $tag);
            if (preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $tag)) {
                $usesSemanticVersioning = true;
            }
        }

        return $usesSemanticVersioning;
    }

    private function checkVcsExistence(): bool
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);
        $finder->ignoreVCS(false);

        if ($finder->depth(0)->path('.git')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        return false;
    }

    public function getDirectoryToAnalyse(): string
    {
        return $this->directoryToAnalyse;
    }

    private function isAPhpPackage(): bool
    {
        return file_exists('composer.json');
    }

    private function checkCliBinaryDirectoryExistence(): bool
    {
        if ($this->isAPhpPackage()) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json'), true);

            if (isset($composerJson['keywords'])) {
                $matchingKeywords = array_filter($composerJson['keywords'], fn ($keyword) => in_array($keyword, ['cli', 'tui', 'console']));

                if (count($matchingKeywords) > 0) {
                    $this->isACliOrTui = true;
                    $this->alternateStepStatus('cli', true);
                }

                if ($this->isACliOrTui) {
                    $finder = new Finder();

                    if ($finder->depth(1)->path('bin')->in($this->directoryToAnalyse)->hasResults()) {
                        $this->alternateStepStatus('cli-binary', true);

                        return true;
                    }

                    $this->alternateStepStatus('cli-binary', false);

                    return false;
                }
            }

            return false;
        }
    }

    private function checkPharConfigurationExistence(): bool
    {
        if ($this->isAPhpPackage()) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse.DIRECTORY_SEPARATOR.'composer.json'), true);

            if (isset($composerJson['keywords'])) {
                $matchingKeywords = array_filter($composerJson['keywords'], fn ($keyword) => in_array($keyword, ['cli', 'tui', 'console']));

                if (count($matchingKeywords) > 0) {
                    $this->isACliOrTui = true;
                }

                if ($this->isACliOrTui) {
                    $finder = new Finder();

                    if ($finder->depth(0)->files()->name('box.json*')->in($this->directoryToAnalyse)->hasResults()) {
                        return true;
                    }

                    return false;
                }
            }

            return false;
        }
    }

    private function checkComposerScriptsExistence(): bool
    {
        if ($this->isAPhpPackage()) {
            $composerJson = json_decode(file_get_contents('composer.json'), true);

            if (isset($composerJson['scripts'])) {
                return count($composerJson['scripts']) > 0;
            }

            return false;
        }
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getViolations(): array
    {
        return array_filter($this->steps, fn ($step) => $step['status'] === false);
    }

    public function getStepsForTable(): array
    {
        $index = 1;
        $steps = [];

        foreach ($this->steps as $step) {
            $status = ' ⛔';
            if ($step['status'] === true) {
                $status = ' ✅';
            }
            $steps[] = [$step['id'] => $index, $step['summary'] => $step['summary'], $step['status'] => $status];
            $index++;
        }

        return $steps;
    }
}
