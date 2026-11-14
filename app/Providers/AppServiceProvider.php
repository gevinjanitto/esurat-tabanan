<?php

namespace App\Providers;

use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.modern');
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('notifications', $user ? AppNotification::where('user_id', $user->id)->latest()->take(8)->get() : collect());
            $view->with('unreadCount', $user ? AppNotification::where('user_id', $user->id)->whereNull('read_at')->count() : 0);
        });
    }
}
