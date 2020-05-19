<?php

namespace PragmaRX\Yaml\Tests;

use PragmaRX\Yaml\Package\Facade as YamlFacade;

class SymfonyYamlTest extends TestCase
{
    use CommonYamlTests;

    public function setUp(): void
    {
        parent::setup();

        $this->yaml = YamlFacade::instance();

        $this->multiple = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/multiple', 'multiple');

        $this->single = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/single/single-app.yml', 'single');
    }
}
