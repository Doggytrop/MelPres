<?php

namespace App\Console\Commands;

use App\Models\Prestamo;
use App\Services\MoraService;
use Illuminate\Console\Command;

class ProcesarMora extends Command
{
    protected $signature   = 'prestamos:procesar-mora';
    protected $description = 'Procesa mora diaria de todos los préstamos activos y vencidos';

    public function __construct(protected MoraService $moraService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Procesando mora — ' . now()->format('d/m/Y H:i'));

        $prestamos = Prestamo::whereIn('estado', ['activo', 'vencido'])
                             ->whereNotNull('fecha_proximo_pago')
                             ->get();

        $procesados = 0;
        $conMora    = 0;

        foreach ($prestamos as $prestamo) {
            $moraAntes = $prestamo->mora_acumulada;

            $this->moraService->procesarMora($prestamo);

            if ($prestamo->mora_acumulada > $moraAntes) {
                $conMora++;
                $this->line("  ✓ Préstamo #{$prestamo->id} — {$prestamo->cliente->nombre_completo} → mora acumulada: \${$prestamo->mora_acumulada}");
            }

            $procesados++;
        }

        $this->info("Completado: {$procesados} préstamos revisados, {$conMora} con mora nueva.");
    }
}