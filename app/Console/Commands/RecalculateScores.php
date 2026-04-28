<?php

namespace App\Console\Commands;

use App\Models\customer;
use App\Services\ScoreService;
use Illuminate\Console\Command;

class RecalculateScores extends Command
{
    protected $signature   = 'customers:recalcular-scores';
    protected $description = 'Recalcula el score de crédito de todos los customers';

    public function __construct(protected ScoreService $scoreService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Recalculando scores...');

        $customers = customer::all();

        foreach ($customers as $customer) {
            $this->scoreService->actualizar($customer);
            $this->line("  ✓ {$customer->first_name_complete} → {$customer->score} pts");
        }

        $this->info("Completado: {$customers->count()} customers actualizados.");
    }
}