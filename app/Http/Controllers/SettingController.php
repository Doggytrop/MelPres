<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // Mapa de clave → grupo para crear registros nuevos si no existen
    private array $keyGroups = [
        // company
        'company_name'            => 'company',
        'company_slogan'          => 'company',
        'company_primary_color'   => 'company',
        'company_secondary_color' => 'company',
        'company_logo'            => 'company',
        'company_phone'           => 'company',
        'company_email'           => 'company',
        'company_whatsapp'        => 'company',
        'company_address'         => 'company',
        // loans
        'loans_min_amount'                => 'loans',
        'loans_max_amount'                => 'loans',
        'loans_grace_days_default'        => 'loans',
        'loans_min_interest_rate'         => 'loans',
        'loans_max_interest_rate'         => 'loans',
        'loans_penalty_default_type'      => 'loans',
        'loans_penalty_default_value'     => 'loans',
        'loans_allow_weekly'              => 'loans',
        'loans_allow_biweekly'            => 'loans',
        'loans_allow_monthly'             => 'loans',
        // advisors
        'advisors_can_view_all_customers'       => 'advisors',
        'advisors_can_edit_all_loans'           => 'advisors',
        'advisors_can_delete_payments'          => 'advisors',
        'advisors_require_approval_restructure' => 'advisors',
        // notifications
        'notifications_payment_reminder_days' => 'notifications',
        'notifications_overdue_alert_days'    => 'notifications',
        'notifications_payment_confirmation'  => 'notifications',
        'notifications_welcome_customer'      => 'notifications',
        // whatsapp
        'whatsapp_enabled'               => 'whatsapp',
        'whatsapp_token'                 => 'whatsapp',
        'whatsapp_phone_number_id'       => 'whatsapp',
        'whatsapp_reminder_enabled'      => 'whatsapp',
        'whatsapp_reminder_time'         => 'whatsapp',
        'whatsapp_reminder_message'      => 'whatsapp',
        'whatsapp_confirmation_enabled'  => 'whatsapp',
        'whatsapp_confirmation_message'  => 'whatsapp',
        'whatsapp_overdue_enabled'       => 'whatsapp',
        'whatsapp_overdue_message'       => 'whatsapp',
        // documents
        'documents_pdf_header'    => 'documents',
        'documents_pdf_footer'    => 'documents',
        'documents_include_logo'  => 'documents',
        // advanced
        'advanced_session_timeout'   => 'advanced',
        'advanced_enable_audit_log'  => 'advanced',
        'advanced_currency_symbol'   => 'advanced',
        'advanced_currency_code'     => 'advanced',
        'advanced_timezone'          => 'advanced',
    ];

    // Claves que son booleanas
    private array $booleanKeys = [
        'loans_allow_weekly', 'loans_allow_biweekly', 'loans_allow_monthly',
        'advisors_can_view_all_customers', 'advisors_can_edit_all_loans',
        'advisors_can_delete_payments', 'advisors_require_approval_restructure',
        'notifications_payment_confirmation', 'notifications_welcome_customer',
        'whatsapp_enabled', 'whatsapp_reminder_enabled',
        'whatsapp_confirmation_enabled', 'whatsapp_overdue_enabled',
        'documents_include_logo', 'advanced_enable_audit_log',
    ];

    public function index()
    {
        $groups = Setting::all()->groupBy('group');
        return view('settings.index', compact('groups'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method', 'company_logo_file']);

        // Logo
        if ($request->hasFile('company_logo_file')) {
            $path = $request->file('company_logo_file')->store('logos', 'public');
            $this->saveSetting('company_logo', $path);
        }

        // Booleanos no enviados = false
        foreach ($this->booleanKeys as $boolKey) {
            $data[$boolKey] = isset($data[$boolKey]) ? '1' : '0';
        }

        // Guardar todo
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->keyGroups)) continue;
            $this->saveSetting($key, $value);
        }

        // Sincronizar WhatsApp al config en tiempo de ejecución
        $this->syncWhatsAppConfig();

        return redirect()->route('settings.index')
                         ->with('success', 'Configuración guardada correctamente.');
    }

    private function saveSetting(string $key, mixed $value): void
    {
        $group = $this->keyGroups[$key] ?? 'general';
        $type  = in_array($key, $this->booleanKeys) ? 'boolean' : 'string';

        Setting::updateOrCreate(
            ['key'   => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );
    }

    private function syncWhatsAppConfig(): void
    {
        $token   = Setting::where('key', 'whatsapp_token')->value('value');
        $phoneId = Setting::where('key', 'whatsapp_phone_number_id')->value('value');
        $enabled = Setting::where('key', 'whatsapp_enabled')->value('value');

        config([
            'whatsapp.token'           => $token,
            'whatsapp.phone_number_id' => $phoneId,
            'whatsapp.enabled'         => $enabled == '1',
        ]);
    }
}