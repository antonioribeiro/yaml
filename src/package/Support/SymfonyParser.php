<?php

namespace PragmaRX\Yaml\Package\Support;

use Illuminate\Support\Collection;
use PragmaRX\Yaml\Package\Exceptions\InvalidYamlFile;
use Symfony\Component\Yaml\Parser as BaseSymfonyParser;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class SymfonyParser implements Parser
{
    /**
     * Dump array to yaml.InvalidYamlFile.
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
        return SymfonyYaml::dump($input, $inline, $indent, $flags);
    }

    /**
     * Parse a yaml file.
     *
     * @param $contents
     *
     * @throws InvalidYamlFile
     *
     * @return mixed
     */
    public function parse($contents)
    {
        return $this->checkYaml(
            SymfonyYaml::parse($contents)
        );
    }

    /**
     * Check parsed contents.
     *
     * @param $contents
     *
     * @throws InvalidYamlFile
     *
     * @return array
     */
    public function checkYaml($contents)
    {
        if (is_string($contents)) {
            throw new InvalidYamlFile();
        }

        return $contents;
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
        try {
            return $this->checkYaml(
                (new BaseSymfonyParser())->parseFile($filename, $flags)
            );
        } catch (\Symfony\Component\Yaml\Exception\ParseException $exception) {
            throw new InvalidYamlFile(
                sprintf('%s is not valid YAML: %s', $filename, $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Convert array to yaml and save.
     *
     * @param $array array
     * @param $file string
     */
    public function saveAsYaml($array, $file, $inline = 2, $indent = 4, $flags = 0)
    {
        $array = $array instanceof Collection
            ? $array->toArray()
            : (array) $array;

        file_put_contents($file, SymfonyYaml::dump($array, $inline, $indent, $flags));
    }
}
