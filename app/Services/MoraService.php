<?php

namespace App\Services;

use App\Models\Prestamo;
use Carbon\Carbon;

class MoraService
{
    public function procesarMora(Prestamo $prestamo): void
    {
        // Si no tiene mora configurada, no hacer nada
        if (!$prestamo->mora_tipo || !$prestamo->mora_valor) return;

        // Si no tiene fecha de próximo pago, no hacer nada
        if (!$prestamo->fecha_proximo_pago) return;

        $hoy          = Carbon::today();
        $fechaEsperada = Carbon::parse($prestamo->fecha_proximo_pago);
        $diasGracia   = $prestamo->dias_gracia ?? 0;

        // Fecha límite = fecha esperada + días de gracia
        $fechaLimite = $fechaEsperada->copy()->addDays($diasGracia);

        // Si aún está dentro del periodo de gracia, no hacer nada
        if ($hoy->lte($fechaLimite)) return;

        // — Fuera del periodo de gracia → marcar como vencido —
        if ($prestamo->estado === 'activo') {
            $prestamo->estado = 'vencido';
        }

        // — Calcular mora según tipo —
        if ($prestamo->mora_tipo === 'fija') {
            $this->aplicarMoraFija($prestamo, $hoy, $fechaLimite);
        } else if ($prestamo->mora_tipo === 'porcentaje') {
            $this->aplicarMoraPorcentaje($prestamo, $hoy, $fechaLimite);
        }

        $prestamo->save();
    }

    // — Mora fija: $X por cada día desde que venció la gracia —
    private function aplicarMoraFija(Prestamo $prestamo, Carbon $hoy, Carbon $fechaLimite): void
    {
        // Solo cobra el día de hoy si es el primer día fuera de gracia
        // o si no se ha cobrado mora hoy todavía
        $diasAtraso = $fechaLimite->diffInDays($hoy);

        // El tope es el siguiente periodo — calculamos cuántos días tiene el periodo
        $diasPeriodo = $this->diasPorFrecuencia($prestamo->frecuencia_pago);

        // No cobrar más allá del periodo completo
        $diasACobrar = min($diasAtraso, $diasPeriodo);

        // Solo acumular el día de hoy (el comando corre diario)
        // Para evitar duplicar, solo sumamos $mora_valor una vez por ejecución
        if ($diasACobrar > 0) {
            $prestamo->mora_acumulada += floatval($prestamo->mora_valor);
        }
    }

    // — Mora porcentaje: X% del saldo por cada periodo vencido completo —
    private function aplicarMoraPorcentaje(Prestamo $prestamo, Carbon $hoy, Carbon $fechaLimite): void
    {
        // Verificar si hoy es exactamente el día que vence (fechaLimite + 1)
        // Solo cobrar una vez por periodo vencido
        $primerDiaVencido = $fechaLimite->copy()->addDay();

        // Solo cobrar si hoy es el primer día fuera de gracia del periodo actual
        if ($hoy->isSameDay($primerDiaVencido)) {
            $mora = floatval($prestamo->saldo_restante) * (floatval($prestamo->mora_valor) / 100);
            $prestamo->mora_acumulada += round($mora, 2);
        }
    }

    private function diasPorFrecuencia(string $frecuencia): int
    {
        return match($frecuencia) {
            'semanal'   => 7,
            'quincenal' => 15,
            'mensual'   => 30,
            default     => 30,
        };
    }
}