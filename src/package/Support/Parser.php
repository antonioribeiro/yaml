<?php

namespace PragmaRX\Yaml\Package\Support;

use PragmaRX\Yaml\Package\Exceptions\InvalidYamlFile;

/**
 * @codeCoverageIgnore
 */
interface Parser
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
    public function dump($input, $inline = 5, $indent = 4, $flags = 0);

    /**
     * Parse a yaml file.
     *
     * @param $contents
     *
     * @throws InvalidYamlFile
     *
     * @return mixed
     */
    public function parse($contents);

    /**
     * Check parsed contents.
     *
     * @param $contents
     *
     * @throws InvalidYamlFile
     *
     * @return array
     */
    public function checkYaml($contents);

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
    public function parseFile($filename, $flags = 0);

    /**
     * Convert array to yaml and save.
     *
     * @param $array array
     * @param $file string
     */
    public function saveAsYaml($array, $file, $inline = 2, $indent = 4, $flags = 0);
}
