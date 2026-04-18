<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\Pago;

class ScoreService
{
    public function calcular(Cliente $cliente): int
    {
        $score = 100;

        $prestamos = Prestamo::where('cliente_id', $cliente->id)
                             ->with('pagos')
                             ->get();

        foreach ($prestamos as $prestamo) {

            // — Préstamo pagado completo —
            if ($prestamo->estado === 'pagado') {
                $score += 20;
            }

            // — Préstamo vencido actualmente —
            if ($prestamo->estado === 'vencido') {
                $score -= 20;
            }

            // — Reestructuración activa —
            if ($prestamo->reestructurado && in_array($prestamo->estado, ['activo', 'vencido'])) {
                $score -= 15;
            }

            // — Reestructuración completada —
            if ($prestamo->reestructurado && $prestamo->estado === 'pagado') {
                $score += 10;
            }

            foreach ($prestamo->pagos as $pago) {

                // — Pago puntual (sin mora en ese pago) —
                if ($pago->abono_mora == 0) {
                    $score += 5;
                }

                // — Pago con mora (se atrasó) —
                if ($pago->abono_mora > 0) {
                    $score -= 10;
                }
            }
        }

        // El score nunca baja de 0 ni sube de 1000
        return max(0, min(1000, $score));
    }

    public function actualizar(Cliente $cliente): void
    {
        $score = $this->calcular($cliente);

        $cliente->score                  = $score;
        $cliente->score_actualizado_at   = now();
        $cliente->save();
    }

    public function etiqueta(int $score): array
    {
        return match(true) {
            $score >= 80  => ['label' => 'Excelente', 'color' => '#1f6b21', 'bg' => '#e8f5e9'],
            $score >= 60  => ['label' => 'Bueno',     'color' => '#1565c0', 'bg' => '#e3f2fd'],
            $score >= 40  => ['label' => 'Regular',   'color' => '#e65100', 'bg' => '#fff3e0'],
            default       => ['label' => 'Alto riesgo','color' => '#c0392b', 'bg' => '#fdecea'],
        };
    }
}