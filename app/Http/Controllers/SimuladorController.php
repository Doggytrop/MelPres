<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Configuracion;
use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    public function index()
    {
        return view('simulador.index');
    }

    public function calcular(Request $request)
    {
        $request->validate([
            'monto'              => ['required', 'numeric', 'min:1'],
            'interes_rate'       => ['required', 'numeric', 'min:0'],
            'tipo'               => ['required', 'in:interes,plazo'],
            'frecuencia'         => ['required', 'in:semanal,quincenal,mensual'],
            'numero_periodos'    => ['nullable', 'integer', 'min:1'],
            'ingreso_cliente'    => ['nullable', 'numeric', 'min:0'],
            'frecuencia_ingreso' => ['nullable', 'in:semanal,quincenal,mensual'],
            'cliente_id'         => ['nullable', 'exists:clientes,id'],
        ]);

        $monto    = floatval($request->monto);
        $interes  = floatval($request->interes_rate);
        $tipo     = $request->tipo;
        $freq     = $request->frecuencia;
        $periodos = intval($request->numero_periodos ?? 1);

        // Convertir periodos a meses según frecuencia
        $periodosMeses = match($freq) {
            'semanal'   => round($periodos / 4, 2),
            'quincenal' => round($periodos / 2, 2),
            'mensual'   => $periodos,
        };

        // — Calcular según tipo —
        if ($tipo === 'plazo') {
            $interesMensual = round($monto * ($interes / 100), 2);
            $interesTotal   = round($monto * ($interes / 100) * $periodosMeses, 2);
            $totalPagar     = round($monto + $interesTotal, 2);
            $pagoSugerido   = round($totalPagar / $periodos, 2);
        } else {
            $interesMensual = round($monto * ($interes / 100), 2);
            $totalPagar     = null;
            $pagoSugerido   = $interesMensual;
            $interesTotal   = null;
        }

        // — Evaluación de capacidad de pago —
        $evaluacion = null;

        if ($request->ingreso_cliente && $request->frecuencia_ingreso) {
            $ingreso     = floatval($request->ingreso_cliente);
            $freqIngreso = $request->frecuencia_ingreso;

            // Convertir ingreso a mensual
            $ingresoMensual = match($freqIngreso) {
                'semanal'   => $ingreso * 4,
                'quincenal' => $ingreso * 2,
                'mensual'   => $ingreso,
            };

            // Convertir pago sugerido a mensual
            $pagoMensual = match($freq) {
                'semanal'   => $pagoSugerido * 4,
                'quincenal' => $pagoSugerido * 2,
                'mensual'   => $pagoSugerido,
            };

            // Compromisos actuales del cliente
            $compromisoActual = 0;
            if ($request->cliente_id) {
                $compromisoActual = Prestamo::where('cliente_id', $request->cliente_id)
                    ->whereIn('estado', ['activo', 'vencido'])
                    ->get()
                    ->sum(function ($p) {
                        $pago = $p->tipo === 'plazo'
                            ? round($p->saldo_restante / max($p->numero_periodos, 1), 2)
                            : round($p->monto_original * ($p->interes_rate / 100), 2);

                        return match($p->frecuencia_pago) {
                            'semanal'   => $pago * 4,
                            'quincenal' => $pago * 2,
                            'mensual'   => $pago,
                        };
                    });
            }

            $totalCompromisoMensual = $pagoMensual + $compromisoActual;
            $porcentaje = round(($totalCompromisoMensual / $ingresoMensual) * 100, 1);

            $limiteMax    = Configuracion::get('simulador_porcentaje_maximo', 40);
            $limiteAlerta = Configuracion::get('simulador_porcentaje_alerta', 30);

            if ($porcentaje <= $limiteAlerta) {
                $evaluacion = [
                    'estado'  => 'verde',
                    'titulo'  => 'Viable',
                    'mensaje' => "El pago representa el {$porcentaje}% del ingreso mensual. El cliente puede pagarlo cómodamente.",
                    'color'   => '#1f6b21',
                    'bg'      => '#e8f5e9',
                ];
            } elseif ($porcentaje <= $limiteMax) {
                $evaluacion = [
                    'estado'  => 'amarillo',
                    'titulo'  => 'Precaución',
                    'mensaje' => "El pago representa el {$porcentaje}% del ingreso mensual. Es el límite recomendable.",
                    'color'   => '#e65100',
                    'bg'      => '#fff3e0',
                ];
            } else {
                $evaluacion = [
                    'estado'  => 'rojo',
                    'titulo'  => 'No recomendado',
                    'mensaje' => "El pago representa el {$porcentaje}% del ingreso mensual. El cliente puede tener dificultades para pagar.",
                    'color'   => '#c0392b',
                    'bg'      => '#fdecea',
                ];
            }

            $evaluacion['porcentaje']        = $porcentaje;
            $evaluacion['ingreso_mensual']   = $ingresoMensual;
            $evaluacion['pago_mensual']      = $pagoMensual;
            $evaluacion['compromiso_actual'] = $compromisoActual;
            $evaluacion['total_compromiso']  = $totalCompromisoMensual;
        }

        return response()->json([
            'monto'           => $monto,
            'interes_rate'    => $interes,
            'tipo'            => $tipo,
            'frecuencia'      => $freq,
            'periodos'        => $periodos,
            'periodos_meses'  => $periodosMeses,
            'interes_mensual' => $interesMensual,
            'interes_total'   => $interesTotal,
            'total_pagar'     => $totalPagar,
            'pago_sugerido'   => $pagoSugerido,
            'evaluacion'      => $evaluacion,
        ]);
    }
}