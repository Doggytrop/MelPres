<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Reestructuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReestructuracionController extends Controller
{
    // — Lista de préstamos vencidos —
    public function vencidos()
    {
        $prestamos = Prestamo::with('cliente')
                             ->where('estado', 'vencido')
                             ->where('reestructurado', false)
                             ->latest()
                             ->paginate(15);

        return view('reestructuracion.vencidos', compact('prestamos'));
    }

    // — Lista de préstamos reestructurados activos —
    public function activos()
    {
        $prestamos = Prestamo::with(['cliente', 'reestructuraciones'])
                            ->where('reestructurado', true)
                            ->whereIn('estado', ['activo', 'vencido'])
                            ->latest()
                            ->paginate(15);

        return view('reestructuracion.activos', compact('prestamos'));
    }

    // — Historial de reestructurados pagados —
    public function historial()
    {
        $prestamos = Prestamo::with(['cliente', 'reestructuraciones'])
                             ->where('reestructurado', true)
                             ->where('estado', 'pagado')
                             ->latest('updated_at')
                             ->paginate(15);

        return view('reestructuracion.historial', compact('prestamos'));
    }

    // — Formulario de reestructuración —
    public function create(Prestamo $prestamo)
    {
        if ($prestamo->estado === 'pagado') {
            return redirect()->route('reestructuracion.vencidos')
                             ->with('error', 'Este préstamo ya está pagado.');
        }

        $prestamo->load(['cliente', 'reestructuraciones']);
        $diasAtraso = $this->calcularDiasAtraso($prestamo);

        return view('reestructuracion.create', compact('prestamo', 'diasAtraso'));
    }

    // — Aplicar reestructuración —
    public function store(Request $request, Prestamo $prestamo)
    {
        $request->validate([
            'tipo'                   => ['required', 'in:condonacion,extension,nuevo_prestamo'],
            'motivo'                 => ['required', 'string'],
            'observaciones'          => ['nullable', 'string'],
            'porcentaje_condonacion' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'periodos_nuevos'        => ['nullable', 'integer', 'min:1'],
            'frecuencia_nueva'       => ['nullable', 'in:semanal,quincenal,mensual'],
            'nuevo_monto'            => ['nullable', 'numeric', 'min:1'],
            'nuevo_interes'          => ['nullable', 'numeric', 'min:0'],
            'nuevo_frecuencia'       => ['nullable', 'in:semanal,quincenal,mensual'],
            'nuevo_periodos'         => ['nullable', 'integer', 'min:1'],
            'nuevo_tipo'             => ['nullable', 'in:interes,plazo'],
        ]);

        DB::transaction(function () use ($request, $prestamo) {
            $tipo = $request->tipo;

            $datosReestructuracion = [
                'prestamo_original_id'   => $prestamo->id,
                'registrado_por'         => auth()->id(),
                'tipo'                   => $tipo,
                'mora_original'          => $prestamo->mora_acumulada,
                'saldo_al_reestructurar' => $prestamo->saldo_restante,
                'motivo'                 => $request->motivo,
                'observaciones'          => $request->observaciones,
                'mora_condonada'         => 0,
                'mora_restante'          => $prestamo->mora_acumulada,
            ];

            if ($tipo === 'condonacion') {
                $porcentaje    = floatval($request->porcentaje_condonacion);
                $moraCondonada = round($prestamo->mora_acumulada * ($porcentaje / 100), 2);
                $moraRestante  = round($prestamo->mora_acumulada - $moraCondonada, 2);

                $prestamo->mora_acumulada    = $moraRestante;
                $prestamo->estado            = 'activo';
                $prestamo->reestructurado    = true;
                $prestamo->fecha_proximo_pago = $this->calcularProximoPago(
                    Carbon::today()->toDateString(),
                    $prestamo->frecuencia_pago
                );
                $prestamo->save();

                $datosReestructuracion['mora_condonada'] = $moraCondonada;
                $datosReestructuracion['mora_restante']  = $moraRestante;

            } elseif ($tipo === 'extension') {
                $datosReestructuracion['periodos_anteriores'] = $prestamo->numero_periodos;
                $datosReestructuracion['periodos_nuevos']     = $request->periodos_nuevos;

                $prestamo->numero_periodos    = $request->periodos_nuevos;
                $prestamo->frecuencia_pago    = $request->frecuencia_nueva ?? $prestamo->frecuencia_pago;
                $prestamo->mora_acumulada     = 0;
                $prestamo->estado             = 'activo';
                $prestamo->reestructurado     = true;
                $prestamo->fecha_proximo_pago = $this->calcularProximoPago(
                    Carbon::today()->toDateString(),
                    $prestamo->frecuencia_pago
                );
                $prestamo->save();

            } elseif ($tipo === 'nuevo_prestamo') {
                // Cerrar préstamo original
                $prestamo->estado         = 'refinanciado';
                $prestamo->reestructurado = true;
                $prestamo->save();

                // Crear nuevo préstamo reestructurado
                $nuevoMonto = floatval($request->nuevo_monto ?? $prestamo->saldo_restante);
                $nuevoTipo  = $request->nuevo_tipo ?? $prestamo->tipo;
                $nuevaFreq  = $request->nuevo_frecuencia ?? $prestamo->frecuencia_pago;
                $nuevoInt   = floatval($request->nuevo_interes ?? $prestamo->interes_rate);
                $nuevoPer   = intval($request->nuevo_periodos ?? 1);

                $nuevoSaldo            = $nuevoMonto;
                $nuevoInteresAcumulado = 0;

                if ($nuevoTipo === 'plazo') {
                    $nuevoInteresAcumulado = round($nuevoMonto * ($nuevoInt / 100) * $nuevoPer, 2);
                    $nuevoSaldo            = $nuevoMonto + $nuevoInteresAcumulado;
                }

                $nuevoPrestamo = Prestamo::create([
                    'cliente_id'          => $prestamo->cliente_id,
                    'tipo'                => $nuevoTipo,
                    'frecuencia_pago'     => $nuevaFreq,
                    'numero_periodos'     => $nuevoPer,
                    'monto_original'      => $nuevoMonto,
                    'saldo_restante'      => $nuevoSaldo,
                    'interes_rate'        => $nuevoInt,
                    'interes_acumulado'   => $nuevoInteresAcumulado,
                    'interes_pendiente'   => 0,
                    'mora_acumulada'      => 0,
                    'mora_tipo'           => $prestamo->mora_tipo,
                    'mora_valor'          => $prestamo->mora_valor,
                    'dias_gracia'         => $prestamo->dias_gracia,
                    'fecha_inicio'        => Carbon::today()->toDateString(),
                    'fecha_proximo_pago'  => $this->calcularProximoPago(
                        Carbon::today()->toDateString(),
                        $nuevaFreq
                    ),
                    'estado'              => 'activo',
                    'reestructurado'      => true,
                    'observaciones'       => 'Reestructurado del préstamo #' . $prestamo->id,
                ]);

                $datosReestructuracion['prestamo_nuevo_id'] = $nuevoPrestamo->id;
            }

            Reestructuracion::create($datosReestructuracion);
        });

        return redirect()->route('reestructuracion.activos')
                         ->with('success', 'Préstamo reestructurado correctamente.');
    }

    public function pdf(Reestructuracion $reestructuracion)
    {
        $reestructuracion->load([
            'prestamoOriginal.cliente',
            'prestamoNuevo',
            'registradoPor',
        ]);

        $pdf = Pdf::loadView('reestructuracion.pdf', compact('reestructuracion'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("reestructuracion-{$reestructuracion->id}.pdf");
    }

    private function calcularDiasAtraso(Prestamo $prestamo): int
    {
        if (!$prestamo->fecha_proximo_pago) return 0;
        return max(0, Carbon::today()->diffInDays($prestamo->fecha_proximo_pago, false) * -1);
    }

    private function calcularProximoPago(string $fecha, string $frecuencia): string
    {
        $date = Carbon::parse($fecha);
        return match($frecuencia) {
            'semanal'   => $date->addWeek()->toDateString(),
            'quincenal' => $date->addDays(15)->toDateString(),
            'mensual'   => $date->addMonth()->toDateString(),
            default     => $date->addMonth()->toDateString(),
        };
    }
}