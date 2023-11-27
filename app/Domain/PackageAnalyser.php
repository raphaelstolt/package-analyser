<?php

declare(strict_types=1);

namespace App\Domain;

use App\Exceptions\NonExistentPackageDirectory;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Finder\Finder;

class PackageAnalyser
{
    private string $directoryToAnalyse;
    private array $steps;
    private array $violations;
    private bool $isACliOrTui = false;
    private OutputStyle $output;

    /**
     * @throws NonExistentPackageDirectory
     */
    public function __construct(string $directoryToAnalyse, OutputStyle $output)
    {
        $this->directoryToAnalyse = $directoryToAnalyse;

        if (!file_exists($directoryToAnalyse)) {
            $exceptionMessage = sprintf("Provided package directory '%s' does not exist", $directoryToAnalyse);
            throw new NonExistentPackageDirectory($exceptionMessage);
        }

        $this->output = $output;

        $this->steps = [
            [ 'id' => 'changelog', 'summary' => 'Keep a CHANGELOG.md file in the base directory of the package.' ],
            [ 'id' => 'tests', 'summary' => 'Write tests or specs for the package.' ],
            [ 'id' => 'ci', 'summary' => 'Use continuous integration.' ],
            [ 'id' => 'readme', 'summary' => 'Provide a README.md in the base directory of the package.' ],
            [ 'id' => 'coding-style', 'summary' => 'Follow a coding style.' ],
            [ 'id' => 'semantic-versioning', 'summary' => 'Use Semantic Versioning to manage version numbers.' ],
            [ 'id' => 'license', 'summary' => 'Include a license file in the base directory of the package.' ],
            [ 'id' => 'gitattributes', 'summary' => 'Keep a .gitattributes file in the base directory of the package to keep dist releases lean.' ],
            [ 'id' => 'autoloader', 'summary' => 'Place domain code in a /src directory in the base directory of the package.' ],
            [ 'id' => 'vcs', 'summary' => 'Utilise a source code management system like Git.' ],
            [ 'id' => 'cli-binary', 'summary' => 'Put CLI/TUI binaries in a /bin directory.' ],
            [ 'id' => 'cli-phar', 'summary' => 'Distribute CLI/TUI binaries via PHAR.' ],
            [ 'id' => 'composer-scripts', 'summary' => 'Utilise Composer scripts.' ],
        ];
        $this->violations = [];
    }

    private function writeStatus(bool $status): void
    {
        if ($status === true) {
            $this->output->writeln(' ✅');
        } else {
            $this->output->writeln(' ⛔');
        }
    }

    public function analyse(): int
    {
        foreach ($this->steps as $step) {
            switch ($step['id']) {
                case 'changelog':
                    $this->output->write('Checking CHANGELOG.md existence');
                    $status = $this->checkChangelogExistence();
                    $this->writeStatus($status);
                    break;
                case 'tests':
                    $status = $this->checkTestsDirectoryExistence() && $this->checkTestingToolExistence();
                    $this->output->write('Checking for tests and testing tools');
                    $this->writeStatus($status);
                    break;
                case 'ci':
                    $status = $this->checkCiUsage();
                    $this->output->write('Checking for CI usage');
                    $this->writeStatus($status);
                    break;
                case 'coding-style':
                    $status = $this->checkCodingStyleToolExistence();
                    $this->output->write('Checking for a coding style fixer and linter');
                    $this->writeStatus($status);
                    break;
                case 'readme':
                    $status = $this->checkReadmeExistence();
                    $this->output->write('Checking README.md existence');
                    $this->writeStatus($status);
                    break;
                case 'license':
                    $status = $this->checkLicenseExistence();
                    $this->output->write('Checking LICENSE.md existence');
                    $this->writeStatus($status);
                    break;
                case 'gitattributes':
                    $status = $this->checkGitattributesExistence();
                    $this->output->write('Checking .gitattributes existence');
                    $this->writeStatus($status);
                    break;
                case 'autoloader':
                    $status = $this->checkSrcExistence();
                    $this->output->write('Checking /src or /app existence');
                    $this->writeStatus($status);
                    break;
                case 'vcs':
                    $status = $this->checkVcsExistence();
                    $this->output->write('Checking the utilisation of a source code management system like Git');
                    $this->writeStatus($status);
                    break;
                case 'cli-binary':
                    $this->output->write('Checking if package is written in 🐘');
                    if ($this->isAPhpPackage()) {
                        $this->writeStatus(true);
                        $status = $this->checkCliBinaryDirectoryExistence();
                        printf('Checking if package is a CLI/TUI %s and placed in a /bin directory', $this->isACliOrTui == true ? '✅' : '❌');
                        if ($this->isACliOrTui) {
                            $this->writeStatus($status);
                        } else {
                            echo PHP_EOL;
                        }
                    } else {
                        echo ' ❌' . PHP_EOL;
                    }
                    break;
                case 'cli-phar':
                    if ($this->isAPhpPackage()) {
                        $status = $this->checkPharConfigurationExistence();
                        printf('Checking if package is a CLI/TUI %s and distributed via PHAR', $this->isACliOrTui == true ? '✅' : '❌');
                        if ($this->isACliOrTui) {
                            $this->writeStatus($status);
                        } else {
                            echo PHP_EOL;
                        }
                    }
                    break;
                case 'composer-scripts':
                    if ($this->isAPhpPackage()) {
                        $status = $this->checkComposerScriptsExistence();
                        echo 'Checking if Composer scripts are utilised';
                        $this->writeStatus($status);
                    }
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
        $this->violations[] = 'tests';

        return false;
    }

    private function checkTestingToolExistence(): bool
    {
        $testingToolBinaries = [
            './vendor/bin/phpspec',
            './vendor/bin/phpunit',
            './vendor/bin/pest'
        ];

        foreach ($testingToolBinaries as $testingToolBinary) {
            if (file_exists($testingToolBinary)) {
                return true;
            }
        }

        $this->violations[] = 'tests';

        return false;
    }

    private function checkChangelogExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->files()->name('CHANGELOG*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        $this->violations[] = 'changelog';

        return false;
    }

    private function checkReadmeExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->files()->name('README*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }
        $this->violations[] = 'readme';

        return false;
    }

    private function checkLicenseExistence(): bool
    {
        $finder = new Finder();
        if ($finder->depth(0)->files()->name('LICENSE*')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }
        $this->violations[] = 'license';

        return false;
    }

    private function checkCodingStyleToolExistence(): bool
    {
        $codingStyleToolBinaries = [
            './vendor/bin/phpcs',
            './vendor/bin/phpcbf',
            './vendor/bin/pint',
            './vendor/bin/php-cs-fixer'
        ];

        foreach ($codingStyleToolBinaries as $codingStyleToolBinary) {
            if (file_exists($codingStyleToolBinary)) {
                return true;
            }
        }

        $this->violations[] = 'coding-style';

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

        $this->violations[] = 'ci';

        return false;
    }

    private function checkGitattributesExistence(): bool
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);

        if ($finder->depth(0)->files()->name('.gitattributes')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        $this->violations[] = 'gitattributes';

        return false;
    }

    private function checkSrcExistence(): bool
    {
        $finder = new Finder();

        if ($finder->depth(0)->path(['app', 'src'])->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        $this->violations[] = 'autoloader';

        return false;
    }

    private function checkVcsExistence(): bool
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);
        $finder->ignoreVCS(false);

        if ($finder->depth(0)->path('.git')->in($this->directoryToAnalyse)->hasResults()) {
            return true;
        }

        $this->violations[] = 'vcs';

        return false;
    }

    private function isAPhpPackage(): bool
    {
        return file_exists('composer.json');
    }

    private function checkCliBinaryDirectoryExistence(): bool
    {
        if ($this->isAPhpPackage()) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse . DIRECTORY_SEPARATOR . 'composer.json'), true);

            if (isset($composerJson['keywords'])) {
                $matchingKeywords = array_filter($composerJson['keywords'], fn ($keyword) => in_array($keyword, ['cli', 'tui', 'console']));

                if (count($matchingKeywords) > 0) {
                    $this->isACliOrTui = true;
                }

                if ($this->isACliOrTui) {
                    $finder = new Finder();

                    if ($finder->depth(1)->path('bin')->in($this->directoryToAnalyse)->hasResults()) {
                        return true;
                    }

                    $this->violations[] = 'cli-binary';

                    return false;
                }
            }

            return false;
        }
    }

    private function checkPharConfigurationExistence(): bool
    {
        if ($this->isAPhpPackage()) {
            $composerJson = json_decode(file_get_contents($this->directoryToAnalyse . DIRECTORY_SEPARATOR . 'composer.json'), true);

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

                    $this->violations[] = 'cli-phar';

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

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }
}
