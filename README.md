# Laravel YAML
### A Laravel YAML parser and config loader

[![Latest Stable Yaml](https://img.shields.io/packagist/v/pragmarx/yaml.svg?style=flat-square)](https://packagist.org/packages/pragmarx/yaml)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md) 
[![Code Quality](https://img.shields.io/scrutinizer/g/antonioribeiro/yaml.svg?style=flat-square)](https://scrutinizer-yaml.com/g/antonioribeiro/yaml/?branch=master) 
[![Build](https://img.shields.io/scrutinizer/build/g/antonioribeiro/yaml.svg?style=flat-square)](https://scrutinizer-yaml.com/g/antonioribeiro/yaml/?branch=master) 
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/antonioribeiro/yaml.svg?style=flat-square)](https://scrutinizer-yaml.com/g/antonioribeiro/yaml/?branch=master)
[![StyleCI](https://styleci.io/repos/112624437/shield)](https://styleci.io/repos/112624437)

## Rationale

Config files getting bigger, harder to maintain and look at, every day. Use Yaml format to load them!:

```
app-version:
  major: 1
  minor: 0
  patch: 0
  format: "{$major}.{$minor}.{$patch}"
```

## Key features

### Load one file to Laravel config 

``` php
Yaml::loadToConfig(config_path('myapp.yml'), 'my-app');
```

## Or a whole directory, recursively, so all those files would be loaded with a single command

``` php
Yaml::loadToConfig(config_path('myapp'), 'my-app');
```

To load a directory with all your config files:

``` text
.
└── myapp
    ├── multiple
    │   ├── alter.yml
    │   ├── app.yml
    │   └── second-level
    │       └── third-level
    │           ├── alter.yml
    │           └── app.yml
    ├── single
        └── single-app.yml
```

Then you would just have to use it like you usually do in Laravel

``` php
config('myapp.multiple.second-level.third-level.alter.person.name')
```

### Execute functions, like in the usual Laravel PHP array config.

``` php
repository: "{{ env('APP_NAME') }}"
path: "{{ storage_path('app') }}"
```

### Config values can reference config keys, you just have to quote it this way:

``` yaml
{{'format.version'}}
```

### You can add comments to your YAML files, something JSON wouldn't let you do

``` yaml
build:
  mode: git-local  #### other modes: git-remote or number
```

## Parser and dumper methods

In case you need to deal with YAML directly, you can use these public methods:

``` php
Yaml::parse($input, $flags) // Parses YAML into a PHP value.

Yaml::parseFile($filename, $flags) // Parses a YAML file into a PHP value.

Yaml::dump($input, $inline, $indent, $flags) // Dumps a PHP value to a YAML string.
```

Which are simple bridges to [Symfony's YAML](https://symfony.com/doc/current/components/yaml.html).

## Install

Via Composer

``` bash
$ composer require pragmarx/yaml
```

## Using

Publish your package as you would usually do:

``` php
$this->publishes([
    __DIR__.'/../config/version.yml' => $this->getConfigFile(),
]);
```

Load the configuration in your `boot()` method:

``` php
$this->app
     ->make('pragmarx.yaml')
     ->loadToConfig($this->getConfigFile(), 'my-package');
```

Or use the Facade:

``` php
Yaml::loadToConfig(config_path('myapp.yml'), 'my-package');
```

And it's merged to your Laravel config:

``` php
config('my-package.name');
```

## Example

This is a YAML file from another package using this package:

``` yaml
current:
  major: 1
  minor: 0
  patch: 0
  format: "{$major}.{$minor}.{$patch}"
cache:
  enabled: true
  key: pragmarx-version
build:
  mode: git-local # git-remote or number
  number: 701031
  git-local: "git rev-parse --verify HEAD"
  git-remote: "git ls-remote {$repository} refs/heads/master"
  repository: "{{ env('APP_GIT_REPOSITORY') }}"
  length: 6
format:
  version: "{$major}.{$minor}.{$patch} (build {$build})"
  full: "version {{'format.version'}}"
  compact: "v{$major}.{$minor}.{$patch}-{$build}"
  ## add as many formats as you need
```

## Minimum requirements

- Laravel 5.5
- PHP 7.0

## Author

[Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro)

## License

This package is licensed under the MIT License - see the `LICENSE` file for details

## Contributing

Pull requests and issues are welcome.


<!--[![Downloads](https://img.shields.io/packagist/dt/pragmarx/yaml.svg?style=flat-square)](https://packagist.org/packages/pragmarx/yaml)-->
