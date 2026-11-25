<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExchangeRateService::class);
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'id'));
        Route::resourceVerbs(['create' => 'baru', 'edit' => 'ubah']);

        // Paksa https di hosting bila APP_URL memakai https
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $rates = app(ExchangeRateService::class);
            // Kurs yang ditampilkan di sidebar: mata uang asing yang paling sering dipakai, default USD.
            $code = $user?->transactions()->where('currency', '!=', 'IDR')
                ->selectRaw('currency, count(*) as n')->groupBy('currency')->orderByDesc('n')->value('currency') ?? 'USD';

            $view->with([
                'chipRate' => $rates->info($code),
                'theme' => config('themes.'.($user?->themeKey() ?? 'pink')),
                'pendingCount' => $user?->is_admin ? User::pending()->count() : 0,
            ]);
        });
    }
}
