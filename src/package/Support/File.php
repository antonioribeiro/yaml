<?php

namespace PragmaRX\Yaml\Package\Support;

class File
{
    /**
     * Check if the string is a directory.
     *
     * @param $item
     *
     * @return bool
     */
    public function isAllowedDirectory($item)
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
        })->mapWithKeys(function ($item) {
            if (is_dir($item)) {
                return [basename($item) => $this->listFiles($item)->toArray()];
            }

            return [$this->cleanKey($item) => $item];
        });
    }

    /**
     * Check if the file is a yaml file.
     *
     * @param $item
     *
     * @return bool
     */
    public function isYamlFile($item)
    {
        return
            $this->isFile($item) && (
                ends_with(strtolower($item), '.yml') ||
                ends_with(strtolower($item), '.yaml')
            );
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
    public function scanDir($dir)
    {
        return collect(scandir($dir))->map(function ($item) use ($dir) {
            return $dir.DIRECTORY_SEPARATOR.$item;
        });
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
}
