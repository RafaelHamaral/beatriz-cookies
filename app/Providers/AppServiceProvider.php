<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('partials.store-contact', function ($view) {
            $view->with('storeContact', \App\Models\StoreContact::current());
        });

        Password::defaults(fn () => Password::min(8)->max(255));

        if ($this->app->environment('production')) {
            config(['session.secure' => true]);
        }
    }
}
