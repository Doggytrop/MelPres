<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', function ($view) {
            try {
                $view->with('config_sistema', [
                    'negocio_nombre'    => Setting::get('negocio_nombre', 'MelPres'),
                    'negocio_moneda'    => Setting::get('negocio_moneda', '$'),
                    'modulo_asesores'   => Setting::get('modulo_asesores', false),
                    'modulo_corte_caja' => Setting::get('modulo_corte_caja', false),
                ]);
            } catch (\Exception $e) {
                //
            }
        });
    }
}