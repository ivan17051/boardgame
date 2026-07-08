<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $request = $this->app->make('request');
        $rootUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');

        if ($rootUrl !== '') {
            URL::forceRootUrl($rootUrl);
        }
    }
}
