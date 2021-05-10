<?php

namespace hiddenCorporation\dbConfig;

use Illuminate\Support\ServiceProvider;
use hiddenCorporation\dbConfig\dbConfig;
use hiddenCorporation\dbConfig\App\Commands\dbConfigTest;
use hiddenCorporation\dbConfig\App\Commands\dbConfigClear;

class dbConfigServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dbConfig.php', 'dbConfig');

        // Register the service the package provides.
        $this->app->singleton('dbConfig', function ($app) {
            return new dbConfig;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['dbConfig'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([__DIR__ . '/../config/dbConfig.php' => config_path('dbConfig.php')], 'dbConfig.config');

        // Publishing test command.
        /*
        $this->publishes([
            __DIR__.'/app/Console/Commands' => base_path('app/Console/Commands'),
        ], 'dbconfig.consoles');
        */

        // Registering package commands.
        $this->commands([
            dbConfigTest::class,
            dbConfigClear::class
        ]);
    }
}
