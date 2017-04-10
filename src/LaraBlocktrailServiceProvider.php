<?php

namespace Blockavel\LaraBlocktrail;

use Illuminate\Support\ServiceProvider;

class LaraBlocktrailServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/main.php' => config_path('larablocktrail.php'),
        ]);

        $file = __DIR__ . '/../vendor/autoload.php';

        if (file_exists($file)) {
            require $file;
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('lara-blocktrail', function() {
            return new LaraBlocktrail;
        });
    }
}
