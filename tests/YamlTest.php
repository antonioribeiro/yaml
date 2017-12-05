<?php

namespace PragmaRX\Yaml\Tests;

use Illuminate\Support\Collection;
use PragmaRX\Yaml\Package\Exceptions\InvalidYamlFile;
use PragmaRX\Yaml\Package\Exceptions\MethodNotFound;
use PragmaRX\Yaml\Package\Facade as YamlFacade;
use PragmaRX\Yaml\Package\Yaml as YamlService;

class YamlTest extends TestCase
{
    /**
     * @var YamlService
     */
    private $yaml;

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

        $this->yaml = YamlFacade::instance();

        $this->multiple = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/multiple', 'multiple');

        $this->single = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/single/single-app.yml', 'single');
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(YamlService::class, $this->yaml);
    }

    public function test_loaded_results()
    {
        $this->assertEquals('Antonio Carlos', config('multiple.app.person.name'));

        $this->assertEquals(config('app.name'), config('multiple.app.environment.app.name'));

        $this->assertEquals('Benoit', config('multiple.alter.person.name'));

        $this->assertEquals('Antonio Carlos Brazil', config('multiple.app.recursive.name'));
    }

    public function test_can_load_many_directory_levels()
    {
        $this->assertEquals('Benoit', config('multiple.second-level.third-level.alter.person.name'));
    }

    public function test_can_list_files()
    {
        $this->assertEquals(3, $this->yaml->listFiles(__DIR__.'/stubs/conf/multiple')->count());
        $this->assertEquals(1, $this->yaml->listFiles(__DIR__.'/stubs/conf/single')->count());
        $this->assertEquals(0, $this->yaml->listFiles(__DIR__.'/stubs/conf/non-existent')->count());
    }

    public function test_can_detect_invalid_yaml_files()
    {
        $this->expectException(InvalidYamlFile::class);

        $this->yaml->loadToConfig(__DIR__.'/stubs/conf/wrong/invalid.yml', 'wrong');
    }

    public function test_can_dump_yaml_files()
    {
        $this->assertEquals(
            $this->cleanYamlString(file_get_contents(__DIR__.'/stubs/conf/single/single-app.yml')),
            $this->cleanYamlString($this->yaml->dump($this->single->toArray()))
        );
    }

    public function test_can_dump_yaml()
    {
        $this->assertEquals(
            $this->cleanYamlString(file_get_contents(__DIR__.'/stubs/conf/single/single-app.yml')),
            $this->cleanYamlString($this->yaml->dump($this->single->toArray()))
        );
    }

    public function test_can_save_yaml()
    {
        $this->yaml->saveAsYaml($this->single, $file = $this->getTempFile());

        $saved = $this->yaml->loadToConfig($file, 'saved');

        $this->assertEquals($this->single, $saved);
    }

    public function test_can_parse_yaml()
    {
        $this->assertEquals(['version' => 1], $this->yaml->parse('version: 1'));

        $this->expectException(InvalidYamlFile::class);

        $this->yaml->parse('version = 1');
    }

    public function test_can_parse_file()
    {
        $array = $this->yaml->parseFile(__DIR__.'/stubs/conf/multiple/second-level/third-level/app.yml');

        $this->assertEquals('Brazil Third Level', array_get($array, 'person.address.country'));
    }

    public function test_method_not_found()
    {
        $this->expectException(MethodNotFound::class);

        $this->yaml->inexistentMethod();
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
