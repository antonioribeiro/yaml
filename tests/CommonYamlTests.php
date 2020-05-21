<?php

namespace PragmaRX\Yaml\Tests;

use App;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PragmaRX\Yaml\Package\Exceptions\InvalidYamlFile;
use PragmaRX\Yaml\Package\Exceptions\MethodNotFound;
use PragmaRX\Yaml\Package\Yaml as YamlService;

trait CommonYamlTests
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

    public function test_can_load_and_merge_with_laravel_config()
    {
        $laravelConfig1 = ['key' => 'value'];
        $laravelConfig2 = ['key' => 'value'];
        config(['mix.alter' => $laravelConfig1]);
        config(['mix.second-level.third-level.alter' => $laravelConfig2]);

        $this->yaml->loadToConfig(__DIR__.'/stubs/conf/multiple', 'mix');

        $this->assertEquals($laravelConfig1, config('mix.alter'));
        $this->assertEquals(config('multiple.app'), config('mix.app'));
        $this->assertEquals($laravelConfig2, config('mix.second-level.third-level.alter'));
        $this->assertNull(config('mix.second-level.third-level.app'));
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

        $this->assertEquals('Brazil Third Level', Arr::get($array, 'person.address.country'));
    }

    public function test_method_not_found()
    {
        $this->expectException(MethodNotFound::class);

        $this->yaml->inexistentMethod();
    }

    public function test_do_not_load_when_configuration_is_cached()
    {
        App::shouldReceive('configurationIsCached')->andReturn(true);

        $loaded = $this->yaml->loadToConfig(__DIR__.'/stubs/conf/single', 'single');

        $this->assertEmpty($loaded);
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
