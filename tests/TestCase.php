<?php

namespace PragmaRX\YamlConf\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\YamlConf\Package\ServiceProvider as YamlConfServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            YamlConfServiceProvider::class,
        ];
    }
}
