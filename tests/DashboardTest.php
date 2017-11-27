<?php

namespace PragmaRX\YamlConf\Tests;

use PragmaRX\YamlConf\Package\Facade as YamlConf;
use PragmaRX\YamlConf\Package\Service as YamlConfService;

class YamlConfTest extends TestCase
{
    /**
     * @var YamlConfService
     */
    private $yamlConf;

    const currentYamlConf = '1.0.0';

    public function setUp()
    {
        parent::setup();

        $this->yamlConf = YamlConf::instance();
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(YamlConfService::class, $this->yamlConf);
    }
}
