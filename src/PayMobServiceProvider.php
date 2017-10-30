<?php

namespace BaklySystems\PayMob;

use Illuminate\Support\ServiceProvider;

class PayMobServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            // Config file.
            __DIR__.'/config/paymob.php' => config_path('paymob.php'),
            // Controller
            __DIR__.'/Http/Controllers/PayMobController.php' => app_path('Http/Controllers/PayMobController.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // PayMob Facede.
        $this->app->singleton('paymob', function () {
            return new PayMob;
        });
    }
}
