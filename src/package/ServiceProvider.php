<?php

namespace PragmaRX\Yaml\Package;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use PragmaRX\Yaml\Package\Support\Parser;
use PragmaRX\Yaml\Package\Support\SymfonyParser;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerService();
    }

    /**
     * Register service service.
     */
    private function registerService()
    {
        $this->app->bind(Parser::class, SymfonyParser::class);
        $this->app->singleton('pragmarx.yaml', function ($app) {
            return new Yaml(null, $app->make(Parser::class), null);
        });
    }
}
