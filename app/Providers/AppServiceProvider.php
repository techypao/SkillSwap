<?php

namespace App\Providers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        View::composer('partials.notification-bell', function (ViewContract $view): void {
            $user = auth()->user();

            $view->with([
                'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
                'recentNotifications' => $user?->notifications()->limit(6)->get() ?? collect(),
            ]);
        });
    }
}
