<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // — General —
            ['key' => 'negocio_nombre',    'value' => 'Mi Negocio', 'type' => 'string',  'group' => 'general',   'description' => 'Nombre del negocio o empresa'],
            ['key' => 'negocio_moneda',    'value' => '$',          'type' => 'string',  'group' => 'general',   'description' => 'Símbolo de la moneda'],
            ['key' => 'negocio_telefono',  'value' => null,         'type' => 'string',  'group' => 'general',   'description' => 'Teléfono de contacto del negocio'],

            // — Asesores —
            ['key' => 'modulo_asesores',                  'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => '¿El sistema tiene asesores?'],
            ['key' => 'asesores_ven_todos_clientes',      'value' => '1', 'type' => 'boolean', 'group' => 'advisors', 'description' => '¿Los asesores pueden ver todos los clientes?'],
            ['key' => 'asesores_pueden_editar_prestamos', 'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => '¿Los asesores pueden editar préstamos?'],

            // — Corte de caja —
            ['key' => 'modulo_corte_caja',    'value' => '0', 'type' => 'boolean', 'group' => 'cash_register', 'description' => '¿El sistema usa corte de caja diario?'],
            ['key' => 'asesores_hacen_corte', 'value' => '0', 'type' => 'boolean', 'group' => 'cash_register', 'description' => '¿Los asesores generan su propio corte de caja?'],

            // — Préstamos —
            ['key' => 'loans_default_grace_days',    'value' => '0',  'type' => 'integer', 'group' => 'loans', 'description' => 'Días de gracia por defecto al crear un préstamo'],
            ['key' => 'loans_default_penalty_type',  'value' => null, 'type' => 'string',  'group' => 'loans', 'description' => 'Tipo de mora por defecto (fixed o percentage)'],
            ['key' => 'loans_default_penalty_value', 'value' => null, 'type' => 'string',  'group' => 'loans', 'description' => 'Valor de mora por defecto'],

            // — Simulador —
            ['key' => 'simulator_max_percentage',   'value' => '40', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje máximo del ingreso que puede comprometer el cliente (%)'],
            ['key' => 'simulator_alert_percentage', 'value' => '30', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje de alerta amarilla (%)'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}