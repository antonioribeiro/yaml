<?php

namespace PragmaRX\Yaml\Tests;

use PragmaRX\Yaml\Package\Facade as YamlFacade;
use PragmaRX\Yaml\Package\Support\Parser;
use PragmaRX\Yaml\Package\Support\PeclParser;

class PeclYamlTest extends TestCase
{
    use CommonYamlTests;

    public function setUp(): void
    {
        if (!extension_loaded('yaml')) {
            $this->markTestSkipped('The PECL YAML extension is not available.');
        }

        parent::setup();

        $this->app->bind(Parser::class, PeclParser::class);

        $this->yaml = YamlFacade::instance();

        $this->multiple = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/multiple', 'multiple');

        $this->single = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/single/single-app.yml', 'single');
    }
}
