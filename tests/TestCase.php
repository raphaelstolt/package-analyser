<?php

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected string $temporaryDirectory;

    /**
     * Set up temporary directory.
     *
     * @return void
     */
    protected function setUpTemporaryDirectory()
    {
        if ((new OsHelper())->isWindows() === false) {
            \ini_set('sys_temp_dir', '/tmp/lpv');
            $this->temporaryDirectory = '/tmp/lpv';
        } else {
            $this->temporaryDirectory = \sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'lpv';
        }

        if (!\file_exists($this->temporaryDirectory)) {
            \mkdir($this->temporaryDirectory);
        }
    }
}
