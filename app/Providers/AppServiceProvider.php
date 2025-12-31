<?php

namespace App\Providers;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;


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
         Scramble::routes(function () {
        $allowedPrefixes = [
            'shop',
            'cart',
            'wishlist',
            'checkout',
            'admin',
        ];

        return collect(Route::getRoutes())->filter(function ($route) use ($allowedPrefixes) {
            $uri = $route->uri();   // contoh: "shop/{product_slug}"

            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    return true;
                }
            }

            return false;
        });
    });
    }
}
