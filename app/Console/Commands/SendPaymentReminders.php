<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\CompanyAutomationEligibility;
use App\Services\CompanyContext;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Throwable;

class SendPaymentReminders extends Command
{
    protected $signature = 'loans:send-reminders';
    protected $description = 'Envía recordatorios de pago por WhatsApp un día antes del vencimiento';

    public function __construct(
        protected WhatsAppService $whatsApp,
        protected CompanyContext $companyContext,
        protected CompanyAutomationEligibility $eligibility
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Enviando recordatorios — '.now()->format('d/m/Y H:i'));

        $tomorrow = now()->addDay()->toDateString();
        $sent = 0;
        $failed = 0;
        $processedCompanies = 0;
        $skippedCompanies = 0;
        $errors = 0;

        $this->companyContext->clear();

        foreach ($this->eligibility->companies() as $company) {
            if (! $this->eligibility->allows($company)) {
                $skippedCompanies++;
                continue;
            }

            $this->companyContext->setCompany($company);

            try {
                $processedCompanies++;
                $loans = Loan::where('company_id', $company->id)
                    ->where('status', 'active')
                    ->whereDate('next_payment_date', $tomorrow)
                    ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $company->id))
                    ->with([
                        'customer' => fn ($query) => $query->where('customers.company_id', $company->id),
                    ])
                    ->get();

                foreach ($loans as $loan) {
                    $customer = $loan->customer;

                    if (! $customer || ! $customer->phone) {
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
            } catch (Throwable $exception) {
                $errors++;
                report($exception);
                $this->error("Error procesando la empresa #{$company->id}.");
            } finally {
                $this->companyContext->clear();
            }
        }

        $this->info("Listo: {$sent} enviados, {$failed} fallidos.");
        $this->info("Procesadas: {$processedCompanies}");
        $this->info("Omitidas por política comercial: {$skippedCompanies}");
        $this->info("Errores: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
