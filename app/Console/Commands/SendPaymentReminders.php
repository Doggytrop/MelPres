<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature   = 'loans:send-reminders';
    protected $description = 'Envía recordatorios de pago por WhatsApp un día antes del vencimiento';

    public function __construct(protected WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Enviando recordatorios — ' . now()->format('d/m/Y H:i'));

        $tomorrow = now()->addDay()->toDateString();

        $loans = Loan::where('status', 'active')
                     ->whereDate('next_payment_date', $tomorrow)
                     ->with('customer')
                     ->get();

        $sent   = 0;
        $failed = 0;

        foreach ($loans as $loan) {
            $customer = $loan->customer;

            if (!$customer || !$customer->phone) {
                $this->line("  ⚠ Préstamo #{$loan->id} — sin teléfono del cliente");
                $failed++;
                continue;
            }

            $ok = $this->whatsApp->sendPaymentReminder($customer, $loan);

            if ($ok) {
                $this->line("  ✓ Recordatorio enviado a {$customer->full_name} ({$customer->phone})");
                $sent++;
            } else {
                $this->line("  ✗ Fallo al enviar a {$customer->full_name}");
                $failed++;
            }
        }

        $this->info("Listo: {$sent} enviados, {$failed} fallidos.");
    }
}