<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\PenaltyService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class ProcessPenalties extends Command
{
    protected $signature   = 'loans:process-penalties';
    protected $description = 'Process daily penalties for all active and overdue loans';

    public function __construct(
        protected PenaltyService  $penaltyService,
        protected WhatsAppService $whatsApp
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Procesando moras — ' . now()->format('d/m/Y H:i'));

        $loans = Loan::whereIn('status', ['active', 'overdue'])
                     ->whereNotNull('next_payment_date')
                     ->with('customer')
                     ->get();

        $processed   = 0;
        $withPenalty = 0;

        foreach ($loans as $loan) {
            $penaltyBefore = $loan->accumulated_penalty;

            $this->penaltyService->processLoan($loan);

            $loan->refresh();

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

        $this->info("Listo: {$processed} préstamos revisados, {$withPenalty} con mora nueva.");
    }
}