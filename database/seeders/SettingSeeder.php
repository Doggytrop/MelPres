<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // — Empresa —
            ['key' => 'company_name',            'value' => 'MelPres',    'type' => 'string',  'group' => 'company', 'description' => 'Nombre de la empresa'],
            ['key' => 'company_slogan',           'value' => 'Tu socio financiero de confianza', 'type' => 'string', 'group' => 'company', 'description' => 'Slogan de la empresa'],
            ['key' => 'company_primary_color',    'value' => '#1f6b21',   'type' => 'string',  'group' => 'company', 'description' => 'Color primario del sistema'],
            ['key' => 'company_secondary_color',  'value' => '#e8f5e9',   'type' => 'string',  'group' => 'company', 'description' => 'Color secundario del sistema'],
            ['key' => 'company_phone',            'value' => null,        'type' => 'string',  'group' => 'company', 'description' => 'Teléfono de la empresa'],
            ['key' => 'company_email',            'value' => null,        'type' => 'string',  'group' => 'company', 'description' => 'Email de la empresa'],
            ['key' => 'company_whatsapp',         'value' => null,        'type' => 'string',  'group' => 'company', 'description' => 'WhatsApp Business'],
            ['key' => 'company_address',          'value' => null,        'type' => 'string',  'group' => 'company', 'description' => 'Dirección de la empresa'],
            ['key' => 'company_logo',             'value' => null,        'type' => 'string',  'group' => 'company', 'description' => 'Ruta del logo de la empresa'],

            // — Préstamos —
            ['key' => 'loans_min_amount',              'value' => '1000',   'type' => 'integer', 'group' => 'loans', 'description' => 'Monto mínimo de préstamo'],
            ['key' => 'loans_max_amount',              'value' => '100000', 'type' => 'integer', 'group' => 'loans', 'description' => 'Monto máximo de préstamo'],
            ['key' => 'loans_grace_days_default',      'value' => '3',      'type' => 'integer', 'group' => 'loans', 'description' => 'Días de gracia por defecto'],
            ['key' => 'loans_min_interest_rate',       'value' => '5',      'type' => 'integer', 'group' => 'loans', 'description' => 'Tasa de interés mínima (%)'],
            ['key' => 'loans_max_interest_rate',       'value' => '30',     'type' => 'integer', 'group' => 'loans', 'description' => 'Tasa de interés máxima (%)'],
            ['key' => 'loans_penalty_default_type',    'value' => null,     'type' => 'string',  'group' => 'loans', 'description' => 'Tipo de mora por defecto'],
            ['key' => 'loans_penalty_default_value',   'value' => '50',     'type' => 'string',  'group' => 'loans', 'description' => 'Valor de mora por defecto'],
            ['key' => 'loans_allow_weekly',            'value' => '1',      'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir pagos semanales'],
            ['key' => 'loans_allow_biweekly',          'value' => '1',      'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir pagos quincenales'],
            ['key' => 'loans_allow_monthly',           'value' => '1',      'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir pagos mensuales'],

            // — Asesores —
            ['key' => 'advisors_can_view_all_customers',        'value' => '1', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores ven todos los clientes'],
            ['key' => 'advisors_can_edit_all_loans',            'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores editan préstamos de otros'],
            ['key' => 'advisors_can_delete_payments',           'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores eliminan pagos'],
            ['key' => 'advisors_require_approval_restructure',  'value' => '1', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Requieren aprobación para reestructurar'],

            // — Notificaciones —
            ['key' => 'notifications_payment_reminder_days',  'value' => '3',  'type' => 'integer', 'group' => 'notifications', 'description' => 'Días antes para recordatorio de pago'],
            ['key' => 'notifications_overdue_alert_days',     'value' => '1',  'type' => 'integer', 'group' => 'notifications', 'description' => 'Días después para aviso de mora'],
            ['key' => 'notifications_payment_confirmation',   'value' => '1',  'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar confirmación de pago'],
            ['key' => 'notifications_welcome_customer',       'value' => '1',  'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar bienvenida a nuevo cliente'],

            // — Documentos —
            ['key' => 'documents_pdf_header',    'value' => null,                        'type' => 'string',  'group' => 'documents', 'description' => 'Encabezado de PDFs'],
            ['key' => 'documents_pdf_footer',    'value' => 'Gracias por su confianza',  'type' => 'string',  'group' => 'documents', 'description' => 'Pie de página de PDFs'],
            ['key' => 'documents_include_logo',  'value' => '1',                         'type' => 'boolean', 'group' => 'documents', 'description' => 'Incluir logo en contratos'],

            // — Avanzado —
            ['key' => 'advanced_session_timeout',   'value' => '120',                'type' => 'integer', 'group' => 'advanced', 'description' => 'Tiempo de sesión en minutos'],
            ['key' => 'advanced_enable_audit_log',  'value' => '1',                  'type' => 'boolean', 'group' => 'advanced', 'description' => 'Bitácora de cambios'],
            ['key' => 'advanced_currency_symbol',   'value' => '$',                  'type' => 'string',  'group' => 'advanced', 'description' => 'Símbolo de moneda'],
            ['key' => 'advanced_currency_code',     'value' => 'MXN',                'type' => 'string',  'group' => 'advanced', 'description' => 'Código de moneda'],
            ['key' => 'advanced_timezone',          'value' => 'America/Mexico_City','type' => 'string',  'group' => 'advanced', 'description' => 'Zona horaria'],

            // — Simulador —
            ['key' => 'simulator_max_percentage',   'value' => '40', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje máximo del ingreso (%)'],
            ['key' => 'simulator_alert_percentage', 'value' => '30', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje de alerta (%)'],

            // — Módulos —
            ['key' => 'modulo_asesores',       'value' => '0', 'type' => 'boolean', 'group' => 'modules', 'description' => 'Activar módulo de asesores'],
            ['key' => 'modulo_corte_caja',     'value' => '0', 'type' => 'boolean', 'group' => 'modules', 'description' => 'Activar corte de caja'],
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