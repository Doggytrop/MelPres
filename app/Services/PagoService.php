<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Prestamo;
use Illuminate\Support\Facades\DB;

class PagoService
{
    public function aplicarPago(Prestamo $prestamo, array $datos): Pago
    {
        return DB::transaction(function () use ($prestamo, $datos) {

            $montoPagado  = floatval($datos['monto_pagado']);
            $restante     = $montoPagado;
            $abonoMora    = 0;
            $abonoInteres = 0;
            $abonoCapital = 0;

            // 1 — Cubrir mora acumulada primero
            if ($prestamo->mora_acumulada > 0) {
                $abonoMora                = min($restante, floatval($prestamo->mora_acumulada));
                $restante                -= $abonoMora;
                $prestamo->mora_acumulada -= $abonoMora;
            }

            // 2 — Cubrir interés pendiente (solo si hay saldo pendiente de interés)
            if ($restante > 0 && $prestamo->interes_pendiente > 0) {
                $abonoInteres                 = min($restante, floatval($prestamo->interes_pendiente));
                $restante                    -= $abonoInteres;
                $prestamo->interes_pendiente -= $abonoInteres;
            }

            // 3 — El resto va a capital
            if ($restante > 0) {
                $abonoCapital              = min($restante, floatval($prestamo->saldo_restante));
                $prestamo->saldo_restante -= $abonoCapital;
            }

            // 4 — Determinar tipo de pago
            $tipoPago = $this->determinarTipoPago($abonoMora, $abonoInteres, $abonoCapital);

            // 5 — Actualizar estado si ya está saldado
            if ($prestamo->saldo_restante <= 0 && $prestamo->interes_pendiente <= 0) {
                $prestamo->estado         = 'pagado';
                $prestamo->saldo_restante = 0;
            } elseif ($prestamo->estado === 'vencido' && $prestamo->mora_acumulada <= 0) {
                $prestamo->estado = 'activo';
            }

            // 6 — Calcular próximo pago
            $prestamo->fecha_proximo_pago = $this->calcularProximoPago(
                $datos['fecha_pago'],
                $prestamo->frecuencia_pago
            );

            $prestamo->save();
            // Actualizar score del cliente
        app(\App\Services\ScoreService::class)->actualizar($prestamo->cliente);

            // 7 — Registrar movimiento
            return Pago::create([
                'prestamo_id'    => $prestamo->id,
                'monto_pagado'   => $montoPagado,
                'abono_mora'     => $abonoMora,
                'abono_interes'  => $abonoInteres,
                'abono_capital'  => $abonoCapital,
                'fecha_pago'     => $datos['fecha_pago'],
                'fecha_esperada' => $datos['fecha_esperada'] ?? null,
                'tipo_pago'      => $tipoPago,
                'observaciones'  => $datos['observaciones'] ?? null,
                'registrado_por' => auth()->id(),
            ]);
        });
    }

    private function determinarTipoPago(float $mora, float $interes, float $capital): string
    {
        if ($mora > 0 && $capital == 0 && $interes == 0) return 'mora';
        if ($interes > 0 && $capital == 0)               return 'solo_interes';
        if ($capital > 0 && $interes == 0)               return 'capital';
        if ($capital > 0 && $interes > 0)                return 'mixto';
        return 'parcial';
    }

    private function calcularProximoPago(string $fechaPago, string $frecuencia): string
    {
        $fecha = \Carbon\Carbon::parse($fechaPago);

        return match($frecuencia) {
            'semanal'   => $fecha->addWeek()->toDateString(),
            'quincenal' => $fecha->addDays(15)->toDateString(),
            'mensual'   => $fecha->addMonth()->toDateString(),
            default     => $fecha->addMonth()->toDateString(),
        };
    }
}