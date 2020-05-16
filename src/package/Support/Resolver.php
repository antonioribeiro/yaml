<?php

namespace PragmaRX\Yaml\Package\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Resolver
{
    protected $replaced = 0;

    /**
     * @param $old
     *
     * @return bool
     */
    private function isArray($old)
    {
        return is_array($old instanceof Collection ? $old->toArray() : $old);
    }

    /**
     * Replace contents.
     *
     * @param $contents
     *
     * @return mixed
     */
    public function replaceContents($contents)
    {
        preg_match_all('/{{(.*)}}/', $contents, $matches);

        foreach ($matches[0] as $key => $match) {
            if (!empty($match)) {
                if (($resolved = $this->resolveVariable($matches[1][$key])) !== Constants::NOT_RESOLVED) {
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
    public function resolveVariable($key)
    {
        $key = trim($key);

        if (($result = $this->executeFunction($key)) !== Constants::NOT_RESOLVED) {
            return $result;
        }

        return Constants::NOT_RESOLVED;
    }

    /**
     * Execute function.
     *
     * @param $string
     *
     * @return mixed
     */
    public function executeFunction($string)
    {
        preg_match_all('/(.*)\((.*)\)/', $string, $matches);

        if (count($matches) > 0 && is_array($matches[0]) && count($matches[0]) > 0) {
            $function = $matches[1][0];

            return $function($this->removeQuotes($matches[2][0]));
        }

        return Constants::NOT_RESOLVED;
    }

    /**
     * Replace contents.
     *
     * @param $old
     *
     * @return Collection
     */
    public function recursivelyFindAndReplaceExecutableCode($old)
    {
        if ($this->isArray($old)) {
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

            config([$configKey => array_merge($contents->toArray(), config($configKey, []))]);
        } while ($this->replaced > 0);

        return $contents;
    }

    /**
     * Exhaustively find and replace executable code.
     *
     * @param $new
     * @param null $keys
     *
     * @return Collection
     */
    public function recursivelyFindAndReplaceKeysToSelf($new, $keys = null)
    {
        $keys = is_null($keys) ? $new : $keys;

        do {
            $old = $new;

            if ($this->isArray($old)) {
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
            $string = str_replace($matches[0][$key], Arr::get($keys->toArray(), $matches[1][$key]), $string);
        }

        return $string;
    }
}
