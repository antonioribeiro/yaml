<?php

namespace PragmaRX\YamlConf\Tests;

use Illuminate\Support\Collection;
use PragmaRX\YamlConf\Package\Exceptions\InvalidYamlFile;
use PragmaRX\YamlConf\Package\Facade as YamlConfFacade;
use PragmaRX\YamlConf\Package\YamlConf as YamlConfService;

class YamlConfTest extends TestCase
{
    /**
     * @var YamlConfService
     */
    private $yamlConf;

    /**
     * @var Collection
     */
    private $single;

    /**
     * @var Collection
     */
    private $multiple;

    public function setUp()
    {
        parent::setup();

        $this->yamlConf = YamlConfFacade::instance();

        $this->multiple = $this->yamlConf->loadToConfig(__DIR__.'/stubs/conf/multiple', 'multiple');

        $this->single = $this->yamlConf->loadToConfig(__DIR__.'/stubs/conf/single/single-app.yml', 'single');
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

    public function test_can_load_many_directory_levels()
    {
        $this->assertEquals('Benoit', config('multiple.second-level.third-level.alter.person.name'));
    }

    public function test_can_list_files()
    {
        $this->assertEquals(3, $this->yamlConf->listFiles(__DIR__.'/stubs/conf/multiple')->count());
        $this->assertEquals(1, $this->yamlConf->listFiles(__DIR__.'/stubs/conf/single')->count());
        $this->assertEquals(0, $this->yamlConf->listFiles(__DIR__.'/stubs/conf/non-existent')->count());
    }

    public function test_can_detect_invalid_yaml_files()
    {
        $this->expectException(InvalidYamlFile::class);

        $this->yamlConf->loadToConfig(__DIR__.'/stubs/conf/wrong/invalid.yml', 'wrong');
    }

    public function test_can_dump_yaml_files()
    {
        $this->assertEquals(
            $this->cleanYamlString(file_get_contents(__DIR__.'/stubs/conf/single/single-app.yml')),
            $this->cleanYamlString($this->yamlConf->dump($this->single->toArray()))
        );
    }

    public function test_can_dump_yaml()
    {
        $this->assertEquals(
            $this->cleanYamlString(file_get_contents(__DIR__.'/stubs/conf/single/single-app.yml')),
            $this->cleanYamlString($this->yamlConf->dump($this->single->toArray()))
        );
    }

    public function test_can_save_yaml()
    {
        $this->yamlConf->saveAsYaml($this->single, $file = $this->getTempFile());

        $saved = $this->yamlConf->loadToConfig($file, 'saved');

        $this->assertEquals($this->single, $saved);
    }

    public function cleanYamlString($string)
    {
        return str_replace(
            ["\n", ' ', "'", '"'],
            ['', '', '', ''],
            $string
        );
    }

    public function getTempFile()
    {
        $dir = __DIR__.'/tmp';

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (file_exists($file = $dir.DIRECTORY_SEPARATOR.'temp.yaml')) {
            unlink($file);
        }

        return $file;
    }
}
