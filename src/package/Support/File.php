<?php

namespace PragmaRX\YamlConf\Package\Support;

trait File
{
    /**
     * Check if the string is a directory.
     *
     * @param $item
     *
     * @return bool
     */
    protected function isAllowedDirectory($item)
    {
        return
            is_dir($item) &&
            !ends_with($item, DIRECTORY_SEPARATOR.'.') &&
            !ends_with($item, DIRECTORY_SEPARATOR.'..');
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
        if (!file_exists($directory)) {
            return collect([]);
        }

        return $this->scanDir($directory)->reject(function ($item) {
            return !$this->isAllowedDirectory($item) && !$this->isYamlFile($item);
        })->mapWithKeys(function ($item, $key) {
            if (is_dir($item)) {
                return [basename($item) => $this->listFiles($item)->toArray()];
            }

            return [$this->cleanKey($item) => $item];
        });
    }

    /**
     * Load all yaml files from a directory.
     *
     * @param $path
     * @param $parseYaml
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadFromDirectory($path, $parseYaml)
    {
        return $this->listFiles($path)->mapWithKeys(function ($file, $key) use ($parseYaml) {
            return [$key => $this->loadFile($file, $parseYaml)];
        });
    }

    /**
     * Check if a string is a proper file.
     *
     * @param $path
     *
     * @return bool
     */
    public function isFile($path)
    {
        return is_string($path) && file_exists($path) && is_file($path);
    }

    /**
     * Scan the directory for files.
     *
     * @param string $dir
     *
     * @return \Illuminate\Support\Collection
     */
    protected function scanDir($dir)
    {
        return collect(scandir($dir))->map(function ($item) use ($dir) {
            return $dir.DIRECTORY_SEPARATOR.$item;
        });
    }
}
