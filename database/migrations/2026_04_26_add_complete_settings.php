<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insertar todas las nuevas configuraciones
        $settings = [
            // EMPRESA
            ['key' => 'company_name', 'value' => 'MelPres', 'type' => 'string', 'group' => 'company', 'description' => 'Nombre de la empresa'],
            ['key' => 'company_slogan', 'value' => 'Tu socio financiero de confianza', 'type' => 'string', 'group' => 'company', 'description' => 'Slogan'],
            ['key' => 'company_logo', 'value' => null, 'type' => 'file', 'group' => 'company', 'description' => 'Logo de la empresa'],
            ['key' => 'company_favicon', 'value' => null, 'type' => 'file', 'group' => 'company', 'description' => 'Favicon'],
            ['key' => 'company_primary_color', 'value' => '#1f6b21', 'type' => 'color', 'group' => 'company', 'description' => 'Color primario'],
            ['key' => 'company_secondary_color', 'value' => '#e8f5e9', 'type' => 'color', 'group' => 'company', 'description' => 'Color secundario'],
            ['key' => 'company_phone', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Teléfono principal'],
            ['key' => 'company_email', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Email de contacto'],
            ['key' => 'company_address', 'value' => '', 'type' => 'text', 'group' => 'company', 'description' => 'Dirección física'],
            ['key' => 'company_facebook', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Facebook'],
            ['key' => 'company_whatsapp', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'WhatsApp Business'],
            ['key' => 'company_instagram', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Instagram'],

            // PRÉSTAMOS
            ['key' => 'loans_penalty_default_type', 'value' => 'fixed', 'type' => 'string', 'group' => 'loans', 'description' => 'Tipo de penalización por defecto'],
            ['key' => 'loans_penalty_default_value', 'value' => '50', 'type' => 'decimal', 'group' => 'loans', 'description' => 'Valor de penalización por defecto'],
            ['key' => 'loans_grace_days_default', 'value' => '3', 'type' => 'integer', 'group' => 'loans', 'description' => 'Días de gracia por defecto'],
            ['key' => 'loans_min_interest_rate', 'value' => '5', 'type' => 'decimal', 'group' => 'loans', 'description' => 'Tasa de interés mínima (%)'],
            ['key' => 'loans_max_interest_rate', 'value' => '30', 'type' => 'decimal', 'group' => 'loans', 'description' => 'Tasa de interés máxima (%)'],
            ['key' => 'loans_min_amount', 'value' => '1000', 'type' => 'decimal', 'group' => 'loans', 'description' => 'Monto mínimo de préstamo'],
            ['key' => 'loans_max_amount', 'value' => '100000', 'type' => 'decimal', 'group' => 'loans', 'description' => 'Monto máximo de préstamo'],
            ['key' => 'loans_min_periods', 'value' => '1', 'type' => 'integer', 'group' => 'loans', 'description' => 'Plazo mínimo (periodos)'],
            ['key' => 'loans_max_periods', 'value' => '24', 'type' => 'integer', 'group' => 'loans', 'description' => 'Plazo máximo (periodos)'],
            ['key' => 'loans_allow_weekly', 'value' => '1', 'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir frecuencia semanal'],
            ['key' => 'loans_allow_biweekly', 'value' => '1', 'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir frecuencia quincenal'],
            ['key' => 'loans_allow_monthly', 'value' => '1', 'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir frecuencia mensual'],

            // USUARIOS Y PERMISOS
            ['key' => 'advisors_can_view_all_customers', 'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores pueden ver clientes de otros'],
            ['key' => 'advisors_can_edit_all_loans', 'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores pueden editar préstamos de otros'],
            ['key' => 'advisors_can_delete_payments', 'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores pueden eliminar pagos'],
            ['key' => 'advisors_require_approval_restructure', 'value' => '1', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Requieren aprobación para reestructurar'],

            // NOTIFICACIONES
            ['key' => 'notifications_payment_reminder_days', 'value' => '3', 'type' => 'integer', 'group' => 'notifications', 'description' => 'Recordatorio de pago (días antes)'],
            ['key' => 'notifications_overdue_alert_days', 'value' => '1', 'type' => 'integer', 'group' => 'notifications', 'description' => 'Aviso de mora (días después)'],
            ['key' => 'notifications_payment_confirmation', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Confirmación de pago recibido'],
            ['key' => 'notifications_welcome_customer', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Bienvenida a nuevo cliente'],

            // DOCUMENTOS
            ['key' => 'documents_pdf_header', 'value' => '', 'type' => 'text', 'group' => 'documents', 'description' => 'Encabezado en PDFs'],
            ['key' => 'documents_pdf_footer', 'value' => 'Gracias por su confianza', 'type' => 'text', 'group' => 'documents', 'description' => 'Pie de página en PDFs'],
            ['key' => 'documents_include_logo', 'value' => '1', 'type' => 'boolean', 'group' => 'documents', 'description' => 'Incluir logo en contratos'],
            ['key' => 'documents_terms_conditions', 'value' => '', 'type' => 'text', 'group' => 'documents', 'description' => 'Términos y condiciones'],

            // SIMULADOR
            ['key' => 'simulator_max_percentage', 'value' => '40', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje máximo de compromiso'],
            ['key' => 'simulator_alert_percentage', 'value' => '30', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje de alerta'],
            ['key' => 'simulator_show_capacity_analysis', 'value' => '1', 'type' => 'boolean', 'group' => 'simulator', 'description' => 'Mostrar análisis de capacidad'],

            // CORTE DE CAJA
            ['key' => 'cash_register_include_loans', 'value' => '1', 'type' => 'boolean', 'group' => 'cash_register', 'description' => 'Incluir préstamos otorgados'],
            ['key' => 'cash_register_include_payments', 'value' => '1', 'type' => 'boolean', 'group' => 'cash_register', 'description' => 'Incluir pagos recibidos'],
            ['key' => 'cash_register_include_charts', 'value' => '1', 'type' => 'boolean', 'group' => 'cash_register', 'description' => 'Incluir gráficas'],
            ['key' => 'cash_register_signature_name', 'value' => '', 'type' => 'string', 'group' => 'cash_register', 'description' => 'Nombre para firma digital'],

            // AVANZADO
            ['key' => 'advanced_session_timeout', 'value' => '120', 'type' => 'integer', 'group' => 'advanced', 'description' => 'Tiempo de sesión (minutos)'],
            ['key' => 'advanced_require_password_delete_loan', 'value' => '1', 'type' => 'boolean', 'group' => 'advanced', 'description' => 'Requerir contraseña para eliminar préstamos'],
            ['key' => 'advanced_require_password_delete_customer', 'value' => '1', 'type' => 'boolean', 'group' => 'advanced', 'description' => 'Requerir contraseña para eliminar clientes'],
            ['key' => 'advanced_enable_audit_log', 'value' => '1', 'type' => 'boolean', 'group' => 'advanced', 'description' => 'Bitácora de cambios importantes'],
            ['key' => 'advanced_currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'advanced', 'description' => 'Símbolo de moneda'],
            ['key' => 'advanced_currency_code', 'value' => 'MXN', 'type' => 'string', 'group' => 'advanced', 'description' => 'Código de moneda'],
            ['key' => 'advanced_timezone', 'value' => 'America/Mexico_City', 'type' => 'string', 'group' => 'advanced', 'description' => 'Zona horaria'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'company_name', 'company_slogan', 'company_logo', 'company_favicon',
            'company_primary_color', 'company_secondary_color', 'company_phone',
            'company_email', 'company_address', 'company_facebook', 'company_whatsapp',
            'company_instagram', 'loans_penalty_default_type', 'loans_penalty_default_value',
            'loans_grace_days_default', 'loans_min_interest_rate', 'loans_max_interest_rate',
            'loans_min_amount', 'loans_max_amount', 'loans_min_periods', 'loans_max_periods',
            'loans_allow_weekly', 'loans_allow_biweekly', 'loans_allow_monthly',
            'advisors_can_view_all_customers', 'advisors_can_edit_all_loans',
            'advisors_can_delete_payments', 'advisors_require_approval_restructure',
            'notifications_payment_reminder_days', 'notifications_overdue_alert_days',
            'notifications_payment_confirmation', 'notifications_welcome_customer',
            'documents_pdf_header', 'documents_pdf_footer', 'documents_include_logo',
            'documents_terms_conditions', 'simulator_max_percentage', 'simulator_alert_percentage',
            'simulator_show_capacity_analysis', 'cash_register_include_loans',
            'cash_register_include_payments', 'cash_register_include_charts',
            'cash_register_signature_name', 'advanced_session_timeout',
            'advanced_require_password_delete_loan', 'advanced_require_password_delete_customer',
            'advanced_enable_audit_log', 'advanced_currency_symbol', 'advanced_currency_code',
            'advanced_timezone',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
