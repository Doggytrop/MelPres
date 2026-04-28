<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\PenaltyService;
use Illuminate\Console\Command;

class ProcessPenalties extends Command
{
    protected $signature   = 'loans:process-penalties';
    protected $description = 'Process daily penalties for all active and overdue loans';

    public function __construct(protected PenaltyService $penaltyService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Processing penalties — ' . now()->format('d/m/Y H:i'));

        $loans = Loan::whereIn('status', ['active', 'overdue'])
                     ->whereNotNull('next_payment_date')
                     ->get();

        $processed   = 0;
        $withPenalty = 0;

        foreach ($loans as $loan) {
            $penaltyBefore = $loan->accumulated_penalty;

            $this->penaltyService->processLoan($loan);

            if ($loan->accumulated_penalty > $penaltyBefore) {
                $withPenalty++;
                $this->line("  ✓ Loan #{$loan->id} — {$loan->customer->full_name} → penalty: \${$loan->accumulated_penalty}");
            }

            $processed++;
        }

        $this->info("Done: {$processed} loans reviewed, {$withPenalty} with new penalty.");
    }
}