<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\ScoreService;
use Illuminate\Console\Command;

class RecalcularScores extends Command
{
    protected $signature   = 'clientes:recalcular-scores';
    protected $description = 'Recalcula el score de crédito de todos los clientes';

    public function __construct(protected ScoreService $scoreService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Recalculando scores...');

        $clientes = Cliente::all();

        foreach ($clientes as $cliente) {
            $this->scoreService->actualizar($cliente);
            $this->line("  ✓ {$cliente->nombre_completo} → {$cliente->score} pts");
        }

        $this->info("Completado: {$clientes->count()} clientes actualizados.");
    }
}