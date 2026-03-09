<?php

namespace App\Providers;

use App\Models\Resolution\Resolution;
use App\Models\User\User;
use App\Policies\UserPolicy;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with('resolutions', Resolution::orderBy('issued_date', 'desc')->get());
        });

        Authenticate::redirectUsing(function ($request) {
            return route('auth.login');
        });
    }
}
