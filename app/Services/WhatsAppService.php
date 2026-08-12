<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppService
{
    private ?string $token = null;
    private ?string $phoneNumberId = null;
    private string $apiUrl;
    private bool   $enabled;

    // ─── Notificaciones específicas ────────────────────────────────────────

    public function sendPaymentReminder(Customer $customer, Loan $loan): bool
    {
        if (Setting::get('whatsapp_reminder_enabled') == '0') return false;

        $template = Setting::get('whatsapp_reminder_message')
            ?? "Hola {nombre} 👋\n\nTe recordamos que tu pago de *\${monto}* vence *mañana*.\n\nPor favor realiza tu pago a tiempo para evitar cargos por mora.\n\n_{negocio}_";

        $message = $this->parseTemplate($template, $customer, $loan);
        return $this->sendText($customer->phone, $message);
    }

    public function sendPaymentConfirmation(Customer $customer, Loan $loan, Payment $payment): bool
    {
        if (Setting::get('whatsapp_confirmation_enabled') == '0') return false;

        $template = Setting::get('whatsapp_confirmation_message')
            ?? "✅ *Pago registrado*\n\nHola {nombre}, confirmamos tu pago:\n\n• Monto: *\${monto}*\n• Fecha: *{fecha}*\n• Saldo restante: *\${saldo}*\n\n_{negocio}_";

        // Caso especial: préstamo liquidado
        if ($loan->status === 'paid') {
            $template .= "\n\n🎉 *¡Préstamo liquidado! Gracias por tu puntualidad.*";
        }

        $message = $this->parseTemplate($template, $customer, $loan, $payment);
        return $this->sendText($customer->phone, $message);
    }

    public function sendOverdueAlert(Customer $customer, Loan $loan): bool
    {
        if (Setting::get('whatsapp_overdue_enabled') == '0') return false;

        $template = Setting::get('whatsapp_overdue_message')
            ?? "⚠️ *Aviso de pago vencido*\n\nHola {nombre}, tu préstamo presenta un saldo vencido:\n\n• Mora acumulada: *\${mora}*\n• Saldo pendiente: *\${saldo}*\n\nPor favor comunícate con tu asesor para regularizar tu cuenta.\n\n_{negocio}_";

        $message = $this->parseTemplate($template, $customer, $loan);
        return $this->sendText($customer->phone, $message);
    }

    public function sendVerification(Customer $customer): bool
    {
        $negocio = config('app.name');

        $message = "Hola {$customer->first_name} 👋\n\n"
            . "Eres cliente de *{$negocio}*.\n\n"
            . "A partir de ahora recibirás recordatorios de pago y notificaciones por este medio.\n\n"
            . "Responde *OK* para confirmar que recibiste este mensaje.\n\n"
            . "_Este es un mensaje automático, no es necesario responder._";

        return $this->sendText($customer->phone, $message);
    }

    // ─── Métodos base ───────────────────────────────────────────────────────

    public function sendText(string $phone, string $message): bool
    {
        $this->refreshConfiguration();

        if (!$this->enabled) return false;

        $phone = $this->formatPhone($phone);
        if (!$phone) {
            Log::warning("WhatsApp: número inválido — {$phone}");
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->post($this->apiUrl, [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp enviado a {$phone}");
                return true;
            }

            Log::error("WhatsApp error: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WhatsApp excepción: " . $e->getMessage());
            return false;
        }
    }

    public function sendTemplate(string $phone, string $templateName, array $components = [], string $language = 'es_MX'): bool
    {
        $this->refreshConfiguration();

        if (!$this->enabled) return false;

        $phone = $this->formatPhone($phone);
        if (!$phone) return false;

        try {
            $response = Http::withToken($this->token)
                ->post($this->apiUrl, [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $templateName,
                        'language'   => ['code' => $language],
                        'components' => $components,
                    ],
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("WhatsApp template excepción: " . $e->getMessage());
            return false;
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function refreshConfiguration(): void
    {
        if (Schema::hasTable('settings')) {
            $this->token         = Setting::get('whatsapp_token') ?: config('whatsapp.token');
            $this->phoneNumberId = Setting::get('whatsapp_phone_number_id') ?: config('whatsapp.phone_number_id');
            $this->enabled       = (Setting::get('whatsapp_enabled', config('whatsapp.enabled', false))) == '1';
        } else {
            $this->token         = config('whatsapp.token');
            $this->phoneNumberId = config('whatsapp.phone_number_id');
            $this->enabled       = false;
        }

        $this->apiUrl = 'https://graph.facebook.com/'
            . config('whatsapp.api_version')
            . '/'
            . $this->phoneNumberId
            . '/messages';
    }

    private function parseTemplate(string $template, Customer $customer, Loan $loan, ?Payment $payment = null): string
    {
        return str_replace(
            ['{nombre}', '{monto}', '{fecha}', '{saldo}', '{mora}', '{negocio}'],
            [
                $customer->first_name,
                number_format($payment?->amount_paid ?? $loan->suggested_payment, 2),
                $loan->next_payment_date?->format('d/m/Y') ?? '—',
                number_format($loan->remaining_balance, 2),
                number_format($loan->accumulated_penalty, 2),
                config('app.name'),
            ],
            $template
        );
    }

    private function formatPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '52') && strlen($digits) === 12) {
            return '+' . $digits;
        }

        if (strlen($digits) === 10) {
            return '+52' . $digits;
        }

        return null;
    }
}
