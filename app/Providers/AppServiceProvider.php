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
                    'negocio_nombre'    => Setting::get('company_name', 'MelPres'),
                    'negocio_moneda'    => Setting::get('advanced_currency_symbol', '$'),
                    'negocio_logo'      => Setting::get('company_logo'),
                    'color_primario'    => Setting::get('company_primary_color', '#1f6b21'),
                    'color_secundario'  => Setting::get('company_secondary_color', '#e8f5e9'),
                    'negocio_telefono'  => Setting::get('company_phone'),
                    'negocio_email'     => Setting::get('company_email'),
                    'negocio_whatsapp'  => Setting::get('company_whatsapp'),
                    'negocio_direccion' => Setting::get('company_address'),
                    'negocio_slogan'    => Setting::get('company_slogan'),
                    'modulo_asesores'   => Setting::get('modulo_asesores', false),
                    'modulo_corte_caja' => Setting::get('modulo_corte_caja', false),
                ]);
            } catch (\Exception $e) {
                //
            }
        });
    }
}// Backend fully refactored from Spanish to English
