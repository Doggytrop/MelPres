<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $configuraciones = [

            // — General —
            [
                'clave'       => 'negocio_nombre',
                'valor'       => 'Mi Negocio',
                'tipo'        => 'string',
                'grupo'       => 'general',
                'descripcion' => 'Nombre del negocio o empresa',
            ],
            [
                'clave'       => 'negocio_moneda',
                'valor'       => '$',
                'tipo'        => 'string',
                'grupo'       => 'general',
                'descripcion' => 'Símbolo de la moneda',
            ],
            [
                'clave'       => 'negocio_telefono',
                'valor'       => null,
                'tipo'        => 'string',
                'grupo'       => 'general',
                'descripcion' => 'Teléfono de contacto del negocio',
            ],

            // — Asesores —
            [
                'clave'       => 'modulo_asesores',
                'valor'       => '0',
                'tipo'        => 'boolean',
                'grupo'       => 'asesores',
                'descripcion' => '¿El sistema tiene asesores?',
            ],
            [
                'clave'       => 'asesores_ven_todos_clientes',
                'valor'       => '1',
                'tipo'        => 'boolean',
                'grupo'       => 'asesores',
                'descripcion' => '¿Los asesores pueden ver todos los clientes o solo los suyos?',
            ],
            [
                'clave'       => 'asesores_pueden_editar_prestamos',
                'valor'       => '0',
                'tipo'        => 'boolean',
                'grupo'       => 'asesores',
                'descripcion' => '¿Los asesores pueden editar préstamos?',
            ],

            // — Corte de caja —
            [
                'clave'       => 'modulo_corte_caja',
                'valor'       => '0',
                'tipo'        => 'boolean',
                'grupo'       => 'caja',
                'descripcion' => '¿El sistema usa corte de caja diario?',
            ],
            [
                'clave'       => 'asesores_hacen_corte',
                'valor'       => '0',
                'tipo'        => 'boolean',
                'grupo'       => 'caja',
                'descripcion' => '¿Los asesores generan su propio corte de caja?',
            ],

            // — Préstamos —
            [
                'clave'       => 'prestamos_dias_gracia_defecto',
                'valor'       => '0',
                'tipo'        => 'integer',
                'grupo'       => 'prestamos',
                'descripcion' => 'Días de gracia por defecto al crear un préstamo',
            ],
            [
                'clave'       => 'prestamos_mora_defecto_tipo',
                'valor'       => null,
                'tipo'        => 'string',
                'grupo'       => 'prestamos',
                'descripcion' => 'Tipo de mora por defecto (fija o porcentaje)',
            ],
            [
                'clave'       => 'prestamos_mora_defecto_valor',
                'valor'       => null,
                'tipo'        => 'string',
                'grupo'       => 'prestamos',
                'descripcion' => 'Valor de mora por defecto',
            ],
            // — Simulador —
            [
                'clave'       => 'simulador_porcentaje_maximo',
                'valor'       => '40',
                'tipo'        => 'integer',
                'grupo'       => 'simulador',
                'descripcion' => 'Porcentaje máximo del ingreso que puede comprometer el cliente (%)',
            ],
            [
                'clave'       => 'simulador_porcentaje_alerta',
                'valor'       => '30',
                'tipo'        => 'integer',
                'grupo'       => 'simulador',
                'descripcion' => 'Porcentaje de alerta amarilla (%) — entre este y el máximo se muestra precaución',
            ],
        ];

        foreach ($configuraciones as $config) {
            DB::table('configuraciones')->updateOrInsert(
                ['clave' => $config['clave']],
                array_merge($config, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}