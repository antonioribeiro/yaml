<?php

namespace PragmaRX\Yaml\Package;

use Illuminate\Support\Collection;
use PragmaRX\Yaml\Package\Support\File;
use PragmaRX\Yaml\Package\Support\Parser;

class Yaml
{
    use File, Parser;

    const NOT_RESOLVED = '!!__FUNCTION_NOT_RESOLVED__!!';

    protected $replaced = 0;

    /**
     * Load yaml files from directory and add to Laravel config.
     *
     * @param string $path
     * @param string $configKey
     * @param bool   $parseYaml
     *
     * @return Collection
     */
    public function loadToConfig($path, $configKey, $parseYaml = true)
    {
        $loaded = $this->cleanArrayKeysRecursive(
            $this->isYamlFile($path)
                ? $this->loadFile($path, $parseYaml)
                : $this->loadFromDirectory($path, $parseYaml)
        );

        return $this->findAndReplaceExecutableCodeToExhaustion($loaded, $configKey);
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
     * @param $keys
     *
     * @return mixed
     */
    public function replaceKeysToSelf($string, $keys)
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
     * Load yaml file.
     *
     * @param $file
     * @param bool $parseYaml
     *
     * @return mixed|string
     */
    public function loadFile($file, $parseYaml = true)
    {
        if (is_array($file)) {
            return collect($file)->mapWithKeys(function ($subFile, $key) use ($parseYaml) {
                return [$key => $this->loadFile($subFile, $parseYaml)];
            })->toArray();
        }

        $contents = file_get_contents($file);

        if ($parseYaml) {
            $contents = $this->parse($contents);
        }

        return $contents;
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
                    $this->cleanKey($key) => $this->cleanArrayKeysRecursive($item),
                ];
            });
        }

        return $dirty;
    }

    /**
     * Clean the array key.
     *
     * @param $key
     *
     * @return mixed|string
     */
    public function cleanKey($key)
    {
        return is_string($key) && file_exists(trim($key))
            ? preg_replace('/\.[^.]+$/', '', basename($key))
            : $key;
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
