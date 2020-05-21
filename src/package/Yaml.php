<?php

namespace PragmaRX\Yaml\Package;

use App;
use Illuminate\Support\Collection;
use PragmaRX\Yaml\Package\Exceptions\MethodNotFound;
use PragmaRX\Yaml\Package\Support\File;
use PragmaRX\Yaml\Package\Support\Parser;
use PragmaRX\Yaml\Package\Support\Resolver;
use PragmaRX\Yaml\Package\Support\SymfonyParser;

class Yaml
{
    /**
     * File class.
     *
     * @var \PragmaRX\Yaml\Package\Support\File
     */
    protected $file;

    /**
     * Parser object.
     *
     * @var \PragmaRX\Yaml\Package\Support\Parser
     */
    protected $parser;

    /**
     * Resolver object.
     *
     * @var \PragmaRX\Yaml\Package\Support\Resolver
     */
    protected $resolver;

    /**
     * Version constructor.
     *
     * @param File|null     $file
     * @param Parser|null   $parser
     * @param Resolver|null $resolver
     */
    public function __construct(File $file = null, Parser $parser = null, Resolver $resolver = null)
    {
        $this->instantiate($file, $parser, $resolver);
    }

    /**
     * Instantiate all dependencies.
     *
     * @param $file
     * @param $parser
     * @param $resolver
     */
    protected function instantiate($file, $parser, $resolver)
    {
        $this->instantiateClass($file, 'file', File::class);

        $this->instantiateClass($parser, 'parser', SymfonyParser::class);

        $this->instantiateClass($resolver, 'resolver', Resolver::class);
    }

    /**
     * Dynamically call format types.
     *
     * @param $name
     * @param array $arguments
     *
     * @throws MethodNotFound
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        foreach (['file', 'parser'] as $object) {
            if (method_exists($this->{$object}, $name)) {
                return call_user_func_array([$this->{$object}, $name], $arguments);
            }
        }

        throw new MethodNotFound("Method '{$name}' doesn't exists in this object.");
    }

    /**
     * Instantiate a class.
     *
     * @param $instance  object
     * @param $property  string
     * @param $class     string
     * @param array $arguments
     *
     * @return object|Yaml
     */
    protected function instantiateClass($instance, $property, $class = null, $arguments = [])
    {
        return $this->{$property} = is_null($instance)
            ? $instance = new $class(...$arguments)
            : $instance;
    }

    /**
     * Load yaml files from directory and add to Laravel config.
     *
     * @param string $path
     * @param string $configKey
     *
     * @return Collection
     */
    public function loadToConfig($path, $configKey)
    {
        if (App::configurationIsCached()) {
            return collect();
        }

        $loaded = $this->file->isYamlFile($path)
            ? $this->loadFile($path)
            : $this->loadFromDirectory($path);

        return $this->resolver->findAndReplaceExecutableCodeToExhaustion($loaded, $configKey);
    }

    /**
     * Load all yaml files from a directory.
     *
     * @param $path
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadFromDirectory($path)
    {
        return $this->file->listFiles($path)->mapWithKeys(function ($file, $key) {
            return [$key => $this->loadFile($file)];
        });
    }

    /**
     * Load yaml file.
     *
     * @param $file
     *
     * @return mixed|string
     */
    public function loadFile($file)
    {
        if (is_array($file)) {
            return collect($file)->mapWithKeys(function ($subFile, $key) {
                return [$key => $this->loadFile($subFile)];
            })->toArray();
        }

        return $this->parser->parseFile($file);
    }

    /**
     * Get this object instance.
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }
}
