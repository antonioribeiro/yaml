<?php

namespace PragmaRX\Yaml\Package;

use Illuminate\Support\Collection;
use PragmaRX\Yaml\Package\Support\File;
use PragmaRX\Yaml\Package\Support\Parser;

class Yaml
{
    const NOT_RESOLVED = '!!__FUNCTION_NOT_RESOLVED__!!';

    protected $replaced = 0;

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
     * Version constructor.
     *
     * @param File|null $file
     * @param Parser|null $parser
     */
    public function __construct(File $file = null, Parser $parser= null)
    {
        $this->instantiate($file, $parser);
    }

    /**
     * Instantiate all dependencies.
     *
     * @param $file
     * @param $parser
     */
    protected function instantiate($file, $parser)
    {
        $this->instantiateClass(
            $parser,
            'parser',
            Parser::class,
            [
                $this->instantiateClass($file, 'file', File::class)
            ]
        );
    }

    /**
     * Instantiate a class.
     *
     * @param $instance  object
     * @param $property  string
     * @param $class     string
     *
     * @param array $arguments
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
        $loaded = $this->cleanArrayKeysRecursive(
            $this->file->isYamlFile($path)
                ? $this->loadFile($path)
                : $this->loadFromDirectory($path)
        );

        return $this->findAndReplaceExecutableCodeToExhaustion($loaded, $configKey);
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
        return $this->listFiles($path)->mapWithKeys(function ($file, $key) {
            return [$key => $this->loadFile($file)];
        });
    }

    /**
     * Get all files from dir.
     *
     * @param $directory
     *
     * @return \Illuminate\Support\Collection
     */
    public function listFiles($directory)
    {
        return $this->file->listFiles($directory);
    }

    /**
     * Dump array to yaml.
     *
     * @param $input
     * @param int $inline
     * @param int $indent
     * @param int $flags
     *
     * @return string
     */
    public function dump($input, $inline = 5, $indent = 4, $flags = 0)
    {
        return $this->parser->dump($input, $inline, $indent, $flags);
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
     * Remove quotes.
     *
     * @param $string
     *
     * @return string
     */
    protected function removeQuotes($string)
    {
        return trim(trim($string, "'"), '"');
    }

    /**
     * Exhaustively find and replace executable code.
     *
     * @param $contents
     *
     * @return Collection
     */
    public function findAndReplaceExecutableCodeToExhaustion($contents, $configKey)
    {
        do {
            $this->replaced = 0;

            $contents = $this->recursivelyFindAndReplaceKeysToSelf(
                $this->recursivelyFindAndReplaceExecutableCode($contents)
            );

            config([$configKey => $contents->toArray()]);
        } while ($this->replaced > 0);

        return $contents;
    }

    /**
     * Exhaustively find and replace executable code.
     *
     * @param $new
     *
     * @return Collection
     */
    public function recursivelyFindAndReplaceKeysToSelf($new, $keys = null)
    {
        $keys = is_null($keys) ? $new : $keys;

        do {
            $old = $new;

            if (is_array($old instanceof Collection ? $old->toArray() : $old)) {
                return collect($old)->map(function ($item) use ($keys) {
                    return $this->recursivelyFindAndReplaceKeysToSelf($item, $keys);
                });
            }

            $new = $this->replaceKeysToSelf($new, $keys);
        } while ($old !== $new);

        return $new;
    }

    /**
     * Replace keys to self.
     *
     * @param $string
     * @param $keys Collection
     *
     * @return mixed
     */
    public function replaceKeysToSelf($string, Collection $keys)
    {
        preg_match_all("/\{\{'((?:[^{}]|(?R))*)\'\}\}/", $string, $matches);

        foreach ($matches[0] as $key => $value) {
            $string = str_replace($matches[0][$key], array_get($keys->toArray(), $matches[1][$key]), $string);
        }

        return $string;
    }

    /**
     * Replace contents.
     *
     * @param $old
     *
     * @return Collection
     */
    protected function recursivelyFindAndReplaceExecutableCode($old)
    {
        if (is_array($old instanceof Collection ? $old->toArray() : $old)) {
            return collect($old)->map(function ($item) {
                return $this->recursivelyFindAndReplaceExecutableCode($item);
            });
        }

        $new = $this->replaceContents($old);

        if ($new !== $old) {
            $this->replaced++;
        }

        return $new;
    }

    /**
     * Replace contents.
     *
     * @param $contents
     *
     * @return mixed
     */
    protected function replaceContents($contents)
    {
        preg_match_all('/{{(.*)}}/', $contents, $matches);

        foreach ($matches[0] as $key => $match) {
            if (count($match)) {
                if (($resolved = $this->resolveVariable($matches[1][$key])) !== self::NOT_RESOLVED) {
                    $contents = str_replace($matches[0][$key], $resolved, $contents);
                }
            }
        }

        return $contents;
    }

    /**
     * Resolve variable.
     *
     * @param $key
     *
     * @return string
     */
    protected function resolveVariable($key)
    {
        $key = trim($key);

        if (($result = $this->executeFunction($key)) !== self::NOT_RESOLVED) {
            return $result;
        }

        return self::NOT_RESOLVED;
    }

    /**
     * Execute function.
     *
     * @param $string
     *
     * @return mixed
     */
    protected function executeFunction($string)
    {
        preg_match_all('/(.*)\((.*)\)/', $string, $matches);

        if (count($matches) && count($matches[0])) {
            $function = $matches[1][0];

            return $function($this->removeQuotes($matches[2][0]));
        }

        return self::NOT_RESOLVED;
    }

    /**
     * Remove extension from file name.
     *
     * @param $dirty
     *
     * @return \Illuminate\Support\Collection|mixed
     */
    public function cleanArrayKeysRecursive($dirty)
    {
        if (is_array($dirty instanceof Collection ? $dirty->toArray() : $dirty)) {
            return collect($dirty)->mapWithKeys(function ($item, $key) {
                return [
                    $this->file->cleanKey($key) => $this->cleanArrayKeysRecursive($item),
                ];
            });
        }

        return $dirty;
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

    /**
     * Convert array to yaml and save.
     *
     * @param $array array
     * @param $file string
     */
    public function saveAsYaml($array, $file)
    {
        $this->parser->saveAsYaml($array, $file);
    }

    /**
     * Parse a yaml file.
     *
     * @param $contents
     *
     * @return mixed
     */
    public function parse($contents)
    {
        return $this->parser->parse($contents);
    }

    /**
     * Parses a YAML file into a PHP value.
     *
     * @param string $filename The path to the YAML file to be parsed
     * @param int    $flags    A bit field of PARSE_* constants to customize the YAML parser behavior
     *
     * @throws \PragmaRX\Yaml\Package\Exceptions\InvalidYamlFile If the file could not be read or the YAML is not valid
     *
     * @return mixed The YAML converted to a PHP value
     */
    public function parseFile($filename, $flags = 0)
    {
        return $this->parser->parseFile($filename, $flags);
    }
}
