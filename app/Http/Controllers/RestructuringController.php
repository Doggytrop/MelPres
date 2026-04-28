<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Restructuring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RestructuringController extends Controller
{
    // — Lista de préstamos vencidos —
    public function overdue()
    {
        $loans = Loan::with('customer')
                     ->where('status', 'overdue')
                     ->where('restructured', false)
                     ->latest()
                     ->paginate(15);

        return view('restructuring.vencidos', compact('loans'));
    }

    // — Lista de préstamos reestructurados activos —
    public function active()
    {
        $loans = Loan::with(['customer', 'restructurings'])
                     ->where('restructured', true)
                     ->whereIn('status', ['active', 'overdue'])
                     ->latest()
                     ->paginate(15);

        return view('restructuring.activos', compact('loans'));
    }

    // — Historial de reestructurados pagados —
    public function history()
    {
        $loans = Loan::with(['customer', 'restructurings'])
                     ->where('restructured', true)
                     ->where('status', 'paid')
                     ->latest('updated_at')
                     ->paginate(15);

        return view('restructuring.history', compact('loans'));
    }

    // — Formulario de reestructuración —
    public function create(loan $loan)
    {
        if ($loan->status === 'paid') {
            return redirect()->route('restructuring.overdues')
                             ->with('error', 'Este préstamo ya está paid.');
        }

        $loan->load(['customer', 'restructurings']);
        $diasAtraso = $this->calcularDiasAtraso($loan);

        return view('restructuring.create', compact('loan', 'diasAtraso'));
    }

    // — Aplicar reestructuración —
    public function store(Request $request, loan $loan)
    {
        $request->validate([
            'tipo'                   => ['required', 'in:forgiveness,extension,new_loan'],
            'motivo'                 => ['required', 'string'],
            'observaciones'          => ['nullable', 'string'],
            'percentage_forgiveness' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'periodos_nuevos'        => ['nullable', 'integer', 'min:1'],
            'frecuencia_nueva'       => ['nullable', 'in:weekly,biweekly,monthly'],
            'nuevo_monto'            => ['nullable', 'numeric', 'min:1'],
            'nuevo_interest_rate'          => ['nullable', 'numeric', 'min:0'],
            'nuevo_frecuencia'       => ['nullable', 'in:weekly,biweekly,monthly'],
            'nuevo_periodos'         => ['nullable', 'integer', 'min:1'],
            'nuevo_tipo'             => ['nullable', 'in:interest,term'],
        ]);

        DB::transaction(function () use ($request, $loan) {
            $tipo = $request->type;

            $datosrestructuring = [
                'loan_original_id'   => $loan->id,
                'recorded_by'         => auth()->id(),
                'tipo'                   => $tipo,
                'mora_original'          => $loan->accumulated_penalty,
                'saldo_al_reestructurar' => $loan->remaining_balance,
                'motivo'                 => $request->reason,
                'observaciones'          => $request->notes,
                'mora_condonada'         => 0,
                'mora_restante'          => $loan->accumulated_penalty,
            ];

            if ($tipo === 'forgiveness') {
                $percentage    = floatval($request->percentage_forgiveness);
                $moraCondonada = round($loan->accumulated_penalty * ($percentage / 100), 2);
                $moraRestante  = round($loan->accumulated_penalty - $moraCondonada, 2);

                $loan->accumulated_penalty    = $moraRestante;
                $loan->status            = 'active';
                $loan->restructured    = true;
                $loan->next_payment_date = $this->calcularProximopayment(
                    Carbon::today()->toDateString(),
                    $loan->payment_frequency
                );
                $loan->save();

                $datosrestructuring['mora_condonada'] = $moraCondonada;
                $datosrestructuring['mora_restante']  = $moraRestante;

            } elseif ($tipo === 'extension') {
                $datosrestructuring['periodos_anteriores'] = $loan->number_of_periods;
                $datosrestructuring['periodos_nuevos']     = $request->new_periods;

                $loan->number_of_periods    = $request->new_periods;
                $loan->payment_frequency    = $request->frecuencia_nueva ?? $loan->payment_frequency;
                $loan->accumulated_penalty     = 0;
                $loan->status             = 'active';
                $loan->restructured     = true;
                $loan->next_payment_date = $this->calcularProximopayment(
                    Carbon::today()->toDateString(),
                    $loan->payment_frequency
                );
                $loan->save();

            } elseif ($tipo === 'new_loan') {
                // Cerrar préstamo original
                $loan->status         = 'refinanced';
                $loan->restructured = true;
                $loan->save();

                // Crear nuevo préstamo reestructurado
                $nuevoMonto = floatval($request->nuevo_monto ?? $loan->remaining_balance);
                $nuevoTipo  = $request->nuevo_tipo ?? $loan->type;
                $nuevaFreq  = $request->nuevo_frecuencia ?? $loan->payment_frequency;
                $nuevoInt   = floatval($request->nuevo_interest_rate ?? $loan->interest_rate);
                $nuevoPer   = intval($request->nuevo_periodos ?? 1);

                $nuevoSaldo            = $nuevoMonto;
                $nuevoInterestAcumulado = 0;

                if ($nuevoTipo === 'term') {
                    $nuevoInterestAcumulado = round($nuevoMonto * ($nuevoInt / 100) * $nuevoPer, 2);
                    $nuevoSaldo            = $nuevoMonto + $nuevoInterestAcumulado;
                }

                $nuevoloan = loan::create([
                    'customer_id'          => $loan->customer_id,
                    'tipo'                => $nuevoTipo,
                    'payment_frequency'     => $nuevaFreq,
                    'number_of_periods'     => $nuevoPer,
                    'original_amount'      => $nuevoMonto,
                    'remaining_balance'      => $nuevoSaldo,
                    'interest_rate'        => $nuevoInt,
                    'accrued_interest'   => $nuevoInterestAcumulado,
                    'pending_interest'   => 0,
                    'accumulated_penalty'      => 0,
                    'penalty_type'           => $loan->penalty_type,
                    'penalty_value'          => $loan->penalty_value,
                    'grace_days'         => $loan->grace_days,
                    'start_date'        => Carbon::today()->toDateString(),
                    'next_payment_date'  => $this->calcularProximopayment(
                        Carbon::today()->toDateString(),
                        $nuevaFreq
                    ),
                    'status'              => 'active',
                    'reestructurado'      => true,
                    'observaciones'       => 'Reestructurado del préstamo #' . $loan->id,
                ]);

                $datosrestructuring['loan_nuevo_id'] = $nuevoloan->id;
            }

            restructuring::create($datosrestructuring);
        });

        return redirect()->route('restructuring.actives')
                         ->with('success', 'Préstamo reestructurado correctamente.');
    }

    public function pdf(restructuring $restructuring)
    {
        $restructuring->load([
            'loanOriginal.customer',
            'loanNuevo',
            'registradoPor',
        ]);

        $pdf = Pdf::loadView('restructuring.pdf', compact('restructuring'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("restructuring-{$restructuring->id}.pdf");
    }

    private function calcularDiasAtraso(loan $loan): int
    {
        if (!$loan->next_payment_date) return 0;
        return max(0, Carbon::today()->diffInDays($loan->next_payment_date, false) * -1);
    }

    private function calcularProximopayment(string $fecha, string $frecuencia): string
    {
        $date = Carbon::parse($fecha);
        return match($frecuencia) {
            'weekly'   => $date->addWeek()->toDateString(),
            'biweekly' => $date->addDays(15)->toDateString(),
            'monthly'   => $date->addMonth()->toDateString(),
            default     => $date->addMonth()->toDateString(),
        };
    }
}// Restructuring module with PDF legal letter
