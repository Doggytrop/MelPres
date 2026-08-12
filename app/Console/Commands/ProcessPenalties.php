<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\CompanyAutomationEligibility;
use App\Services\CompanyContext;
use App\Services\PenaltyService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Throwable;

class ProcessPenalties extends Command
{
    protected $signature = 'loans:process-penalties';
    protected $description = 'Process daily penalties for all active and overdue loans';

    public function __construct(
        protected PenaltyService $penaltyService,
        protected WhatsAppService $whatsApp,
        protected CompanyContext $companyContext,
        protected CompanyAutomationEligibility $eligibility
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Procesando moras — '.now()->format('d/m/Y H:i'));

        $processed = 0;
        $withPenalty = 0;
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
                    ->whereIn('status', ['active', 'overdue'])
                    ->whereNotNull('next_payment_date')
                    ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $company->id))
                    ->with([
                        'customer' => fn ($query) => $query->where('customers.company_id', $company->id),
                    ])
                    ->get();

                foreach ($loans as $loan) {
                    $penaltyBefore = $loan->accumulated_penalty;

                    $this->penaltyService->processLoan($loan);

                    $loan->refresh();
                    $loan->load([
                        'customer' => fn ($query) => $query->where('customers.company_id', $company->id),
                    ]);

                    if ($loan->accumulated_penalty > $penaltyBefore) {
                        $withPenalty++;
                        $this->line("  ✓ Préstamo #{$loan->id} — {$loan->customer->full_name} → mora: \${$loan->accumulated_penalty}");

                        $customer = $loan->customer;
                        if ($customer?->phone) {
                            $this->whatsApp->sendOverdueAlert($customer, $loan);
                        }
                    }

                    $processed++;
                }
            } catch (Throwable $exception) {
                $errors++;
                report($exception);
                $this->error("Error procesando la empresa #{$company->id}.");
            } finally {
                $this->companyContext->clear();
            }
        }

        $this->info("Listo: {$processed} préstamos revisados, {$withPenalty} con mora nueva.");
        $this->info("Procesadas: {$processedCompanies}");
        $this->info("Omitidas por política comercial: {$skippedCompanies}");
        $this->info("Errores: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
