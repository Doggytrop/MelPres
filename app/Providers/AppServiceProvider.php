<?php

namespace App\Providers;

use App\Models\Configuracion;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

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
        if (!Schema::hasTable('configuraciones')) {
            return;
        }

        $configSistema = Configuracion::query()
            ->get()
            ->mapWithKeys(function (Configuracion $config) {
                $valor = match ($config->tipo) {
                    'boolean' => (bool) $config->valor,
                    'integer' => (int) $config->valor,
                    default => $config->valor,
                };

                return [$config->clave => $valor];
            })
            ->all();

        View::share('config_sistema', $configSistema);
    }
}
