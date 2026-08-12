<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DefaultCompanySettings
{
    public function catalog(): array
    {
        return [
            ['key' => 'company_name', 'value' => 'MelPres', 'type' => 'string', 'group' => 'company', 'description' => 'Nombre de la empresa'],
            ['key' => 'company_slogan', 'value' => 'Tu socio financiero de confianza', 'type' => 'string', 'group' => 'company', 'description' => 'Slogan de la empresa'],
            ['key' => 'company_logo', 'value' => null, 'type' => 'string', 'group' => 'company', 'description' => 'Ruta del logo de la empresa'],
            ['key' => 'company_favicon', 'value' => null, 'type' => 'file', 'group' => 'company', 'description' => 'Favicon'],
            ['key' => 'company_primary_color', 'value' => '#1f6b21', 'type' => 'string', 'group' => 'company', 'description' => 'Color primario del sistema'],
            ['key' => 'company_secondary_color', 'value' => '#e8f5e9', 'type' => 'string', 'group' => 'company', 'description' => 'Color secundario del sistema'],
            ['key' => 'company_phone', 'value' => null, 'type' => 'string', 'group' => 'company', 'description' => 'Teléfono de la empresa'],
            ['key' => 'company_email', 'value' => null, 'type' => 'string', 'group' => 'company', 'description' => 'Email de la empresa'],
            ['key' => 'company_address', 'value' => null, 'type' => 'string', 'group' => 'company', 'description' => 'Dirección de la empresa'],
            ['key' => 'company_facebook', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Facebook'],
            ['key' => 'company_whatsapp', 'value' => null, 'type' => 'string', 'group' => 'company', 'description' => 'WhatsApp Business'],
            ['key' => 'company_instagram', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Instagram'],

            ['key' => 'loans_min_amount', 'value' => '1000', 'type' => 'integer', 'group' => 'loans', 'description' => 'Monto mínimo de préstamo'],
            ['key' => 'loans_max_amount', 'value' => '100000', 'type' => 'integer', 'group' => 'loans', 'description' => 'Monto máximo de préstamo'],
            ['key' => 'loans_min_periods', 'value' => '1', 'type' => 'integer', 'group' => 'loans', 'description' => 'Plazo mínimo en periodos'],
            ['key' => 'loans_max_periods', 'value' => '24', 'type' => 'integer', 'group' => 'loans', 'description' => 'Plazo máximo en periodos'],
            ['key' => 'loans_grace_days_default', 'value' => '3', 'type' => 'integer', 'group' => 'loans', 'description' => 'Días de gracia por defecto'],
            ['key' => 'loans_min_interest_rate', 'value' => '5', 'type' => 'integer', 'group' => 'loans', 'description' => 'Tasa de interés mínima (%)'],
            ['key' => 'loans_max_interest_rate', 'value' => '30', 'type' => 'integer', 'group' => 'loans', 'description' => 'Tasa de interés máxima (%)'],
            ['key' => 'loans_penalty_default_type', 'value' => null, 'type' => 'string', 'group' => 'loans', 'description' => 'Tipo de mora por defecto'],
            ['key' => 'loans_penalty_default_value', 'value' => '50', 'type' => 'string', 'group' => 'loans', 'description' => 'Valor de mora por defecto'],
            ['key' => 'loans_allow_weekly', 'value' => '1', 'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir pagos semanales'],
            ['key' => 'loans_allow_biweekly', 'value' => '1', 'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir pagos quincenales'],
            ['key' => 'loans_allow_monthly', 'value' => '1', 'type' => 'boolean', 'group' => 'loans', 'description' => 'Permitir pagos mensuales'],

            ['key' => 'advisors_can_view_all_customers', 'value' => '1', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores ven todos los clientes'],
            ['key' => 'advisors_can_edit_all_loans', 'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores editan préstamos de otros'],
            ['key' => 'advisors_can_delete_payments', 'value' => '0', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Asesores eliminan pagos'],
            ['key' => 'advisors_require_approval_restructure', 'value' => '1', 'type' => 'boolean', 'group' => 'advisors', 'description' => 'Requieren aprobación para reestructurar'],

            ['key' => 'notifications_payment_reminder_days', 'value' => '3', 'type' => 'integer', 'group' => 'notifications', 'description' => 'Días antes para recordatorio de pago'],
            ['key' => 'notifications_overdue_alert_days', 'value' => '1', 'type' => 'integer', 'group' => 'notifications', 'description' => 'Días después para aviso de mora'],
            ['key' => 'notifications_payment_confirmation', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar confirmación de pago'],
            ['key' => 'notifications_welcome_customer', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar bienvenida a nuevo cliente'],

            ['key' => 'whatsapp_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Activar WhatsApp'],
            ['key' => 'whatsapp_token', 'value' => '', 'type' => 'string', 'group' => 'whatsapp', 'description' => 'Token de acceso Meta'],
            ['key' => 'whatsapp_phone_number_id', 'value' => '', 'type' => 'string', 'group' => 'whatsapp', 'description' => 'Phone Number ID'],
            ['key' => 'whatsapp_reminder_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Recordatorio de pago activo'],
            ['key' => 'whatsapp_reminder_time', 'value' => '09:00', 'type' => 'string', 'group' => 'whatsapp', 'description' => 'Hora de envío recordatorio'],
            ['key' => 'whatsapp_reminder_message', 'value' => "Hola {nombre} 👋\n\nTe recordamos que tu pago de *\${monto}* vence *mañana*.\n\nRealiza tu pago a tiempo para evitar cargos por mora.\n\n_{negocio}_", 'type' => 'text', 'group' => 'whatsapp', 'description' => 'Mensaje recordatorio'],
            ['key' => 'whatsapp_confirmation_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Confirmación de pago activa'],
            ['key' => 'whatsapp_confirmation_message', 'value' => "✅ *Pago registrado*\n\nHola {nombre}, confirmamos tu pago:\n\n• Monto: *\${monto}*\n• Fecha: *{fecha}*\n• Saldo restante: *\${saldo}*\n\n_{negocio}_", 'type' => 'text', 'group' => 'whatsapp', 'description' => 'Mensaje confirmación'],
            ['key' => 'whatsapp_overdue_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Alerta mora activa'],
            ['key' => 'whatsapp_overdue_message', 'value' => "⚠️ *Aviso de pago vencido*\n\nHola {nombre}, tu préstamo presenta un saldo vencido:\n\n• Mora acumulada: *\${mora}*\n• Saldo pendiente: *\${saldo}*\n\nComunícate con tu asesor para regularizar tu cuenta.\n\n_{negocio}_", 'type' => 'text', 'group' => 'whatsapp', 'description' => 'Mensaje mora'],

            ['key' => 'documents_pdf_header', 'value' => null, 'type' => 'string', 'group' => 'documents', 'description' => 'Encabezado de PDFs'],
            ['key' => 'documents_pdf_footer', 'value' => 'Gracias por su confianza', 'type' => 'string', 'group' => 'documents', 'description' => 'Pie de página de PDFs'],
            ['key' => 'documents_include_logo', 'value' => '1', 'type' => 'boolean', 'group' => 'documents', 'description' => 'Incluir logo en contratos'],
            ['key' => 'documents_terms_conditions', 'value' => '', 'type' => 'text', 'group' => 'documents', 'description' => 'Términos y condiciones'],

            ['key' => 'simulator_max_percentage', 'value' => '40', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje máximo del ingreso (%)'],
            ['key' => 'simulator_alert_percentage', 'value' => '30', 'type' => 'integer', 'group' => 'simulator', 'description' => 'Porcentaje de alerta (%)'],
            ['key' => 'simulator_show_capacity_analysis', 'value' => '1', 'type' => 'boolean', 'group' => 'simulator', 'description' => 'Mostrar análisis de capacidad'],

            ['key' => 'cash_register_include_loans', 'value' => '1', 'type' => 'boolean', 'group' => 'cash_register', 'description' => 'Incluir préstamos otorgados'],
            ['key' => 'cash_register_include_payments', 'value' => '1', 'type' => 'boolean', 'group' => 'cash_register', 'description' => 'Incluir pagos recibidos'],
            ['key' => 'cash_register_include_charts', 'value' => '1', 'type' => 'boolean', 'group' => 'cash_register', 'description' => 'Incluir gráficas'],
            ['key' => 'cash_register_signature_name', 'value' => '', 'type' => 'string', 'group' => 'cash_register', 'description' => 'Nombre para firma digital'],

            ['key' => 'advanced_session_timeout', 'value' => '120', 'type' => 'integer', 'group' => 'advanced', 'description' => 'Tiempo de sesión en minutos'],
            ['key' => 'advanced_require_password_delete_loan', 'value' => '1', 'type' => 'boolean', 'group' => 'advanced', 'description' => 'Requerir contraseña para eliminar préstamos'],
            ['key' => 'advanced_require_password_delete_customer', 'value' => '1', 'type' => 'boolean', 'group' => 'advanced', 'description' => 'Requerir contraseña para eliminar clientes'],
            ['key' => 'advanced_enable_audit_log', 'value' => '1', 'type' => 'boolean', 'group' => 'advanced', 'description' => 'Bitácora de cambios'],
            ['key' => 'advanced_currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'advanced', 'description' => 'Símbolo de moneda'],
            ['key' => 'advanced_currency_code', 'value' => 'MXN', 'type' => 'string', 'group' => 'advanced', 'description' => 'Código de moneda'],
            ['key' => 'advanced_timezone', 'value' => 'America/Mexico_City', 'type' => 'string', 'group' => 'advanced', 'description' => 'Zona horaria'],

            ['key' => 'modulo_asesores', 'value' => '0', 'type' => 'boolean', 'group' => 'modules', 'description' => 'Activar módulo de asesores'],
            ['key' => 'modulo_corte_caja', 'value' => '0', 'type' => 'boolean', 'group' => 'modules', 'description' => 'Activar corte de caja'],
        ];
    }

    public function initialize(int $companyId, array $valueOverrides = []): void
    {
        $timestamp = now();

        foreach ($this->catalog() as $setting) {
            $key = $setting['key'];
            $setting['value'] = array_key_exists($key, $valueOverrides)
                ? $valueOverrides[$key]
                : $setting['value'];

            $query = DB::table('settings')
                ->where('company_id', $companyId)
                ->where('key', $key);

            $values = [
                'value' => $setting['value'],
                'type' => $setting['type'],
                'group' => $setting['group'],
                'description' => $setting['description'],
                'updated_at' => $timestamp,
            ];

            if ($query->exists()) {
                $query->update($values);
            } else {
                DB::table('settings')->insert($values + [
                    'company_id' => $companyId,
                    'key' => $key,
                    'created_at' => $timestamp,
                ]);
            }
        }
    }
}
