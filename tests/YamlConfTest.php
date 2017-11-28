<?php

namespace PragmaRX\YamlConf\Tests;

use PragmaRX\YamlConf\Package\Exceptions\InvalidYamlFile;
use PragmaRX\YamlConf\Package\Facade as YamlConfFacade;
use PragmaRX\YamlConf\Package\YamlConf as YamlConfService;

class YamlConfTest extends TestCase
{
    /**
     * @var YamlConfService
     */
    private $yamlConf;
    private $single;
    private $multiple;

    public function setUp()
    {
        parent::setup();

        $this->yamlConf = YamlConfFacade::instance();

        $this->multiple = $this->yamlConf->loadToConfig(__DIR__.'/stubs/conf/multiple', 'multiple');

        $this->single = $this->yamlConf->loadToConfig(__DIR__.'/stubs/conf/single/single-app.yml', 'single');

        dd($this->multiple);
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(YamlConfService::class, $this->yamlConf);
    }

    public function test_loaded_results()
    {
        $this->assertEquals('Antonio Carlos', config('multiple.app.person.name'));

        $this->assertEquals('Laravel', config('multiple.app.environment.app.name'));

        $this->assertEquals('Benoit', config('multiple.alter.person.name'));

        $this->assertEquals('Antonio Carlos Brazil', config('multiple.app.recursive.name'));
    }

    public function test_can_list_files()
    {
        $this->assertEquals(2, $this->yamlConf->listFiles(__DIR__.'/stubs/conf/multiple')->count());
        $this->assertEquals(1, $this->yamlConf->listFiles(__DIR__.'/stubs/conf/single')->count());
        $this->assertEquals(0, $this->yamlConf->listFiles(__DIR__.'/stubs/conf/non-existent')->count());
    }

    public function test_can_detect_invalid_yaml_files()
    {
        $this->expectException(InvalidYamlFile::class);

        $wrong = $this->yamlConf->loadToConfig(__DIR__.'/stubs/conf/wrong/invalid.yml', 'wrong');
    }
}
