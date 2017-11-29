<?php

namespace PragmaRX\YamlConf\Package\Support;

use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;
use PragmaRX\YamlConf\Package\Exceptions\InvalidYamlFile;

trait Yaml
{
    /**
     * Dump array to yaml.
     *
     * @param $input
     * @param int $inline
     * @param int $indent
     * @param int $flags
     * @return string
     */
    public function dump($input, $inline = 5, $indent = 4, $flags = 0)
    {
        return SymfonyYaml::dump($input, $inline, $indent, $flags);
    }

    /**
     * Check if the file is a yaml file.
     *
     * @param $item
     * @return bool
     */
    protected function isYamlFile($item)
    {
        return
            $this->isFile($item) && (
                ends_with(strtolower($item), '.yml') ||
                ends_with(strtolower($item), '.yaml')
            );
    }

    /**
     * Parse a yaml file.
     *
     * @param $contents
     * @return mixed
     * @throws InvalidYamlFile
     */
    protected function parse($contents)
    {
        $yaml = SymfonyYaml::parse($contents);

        if (is_string($yaml)) {
            throw new InvalidYamlFile();
        }

        return $yaml;
    }

    /**
     * Convert array to yaml and save.
     * @param $array array
     * @param $file string
     */
    public function saveAsYaml($array, $file)
    {
        $array = $array instanceof Collection
            ? $array->toArray()
            : (array) $array;

        file_put_contents($file, SymfonyYaml::dump($array));
    }
}
