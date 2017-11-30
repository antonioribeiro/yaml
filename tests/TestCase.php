<?php

namespace PragmaRX\Yaml\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Yaml\Package\ServiceProvider as YamlServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            YamlServiceProvider::class,
        ];
    }
}
