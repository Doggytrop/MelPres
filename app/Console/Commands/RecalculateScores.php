<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CompanyAutomationEligibility;
use App\Services\CompanyContext;
use App\Services\ScoreService;
use Illuminate\Console\Command;
use Throwable;

class RecalculateScores extends Command
{
    protected $signature = 'customers:recalcular-scores';
    protected $description = 'Recalcula el score de crédito de todos los customers';

    public function __construct(
        protected ScoreService $scoreService,
        protected CompanyContext $companyContext,
        protected CompanyAutomationEligibility $eligibility
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Recalculando scores...');

        $updated = 0;
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
                $customers = Customer::where('company_id', $company->id)->get();

                foreach ($customers as $customer) {
                    $this->scoreService->actualizar($customer);
                    $this->line("  ✓ {$customer->full_name} → {$customer->score} pts");
                    $updated++;
                }
            } catch (Throwable $exception) {
                $errors++;
                report($exception);
                $this->error("Error procesando la empresa #{$company->id}.");
            } finally {
                $this->companyContext->clear();
            }
        }

        $this->info("Completado: {$updated} customers actualizados.");
        $this->info("Procesadas: {$processedCompanies}");
        $this->info("Omitidas por política comercial: {$skippedCompanies}");
        $this->info("Errores: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
