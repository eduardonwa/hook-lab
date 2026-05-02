<?php

namespace App\Providers;

use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Carbon::setLocale('es');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        DateTimePicker::macro('dateMex', function () {
            return $this
                ->hint('DD/MM/AAAA')
                ->native(false)
                ->displayFormat('d / m / Y — h:i A')
                ->seconds(false);
        });
    }
}
