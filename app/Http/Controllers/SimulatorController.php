<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Setting;
use Illuminate\Http\Request;

class SimulatorController extends Controller
{
    public function index()
    {
        return view('simulator.index');
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'monto'              => ['required', 'numeric', 'min:1'],
            'interest_rate'      => ['required', 'numeric', 'min:0'],
            'type'               => ['required', 'in:interest,term'],
            'frequency'          => ['required', 'in:weekly,biweekly,monthly'],
            'number_of_periods'  => ['nullable', 'integer', 'min:1'],
            'customer_income'    => ['nullable', 'numeric', 'min:0'],
            'income_frequency'   => ['nullable', 'in:weekly,biweekly,monthly'],
            'customer_id'        => ['nullable', 'exists:customers,id'],
        ]);

        $amount   = floatval($request->monto);
        $interest = floatval($request->interest_rate);
        $type     = $request->type;
        $freq     = $request->frequency;
        $periods  = intval($request->number_of_periods ?? 1);

        // Convert periods to months
        $periodsInMonths = match($freq) {
            'weekly'   => round($periods / 4, 2),
            'biweekly' => round($periods / 2, 2),
            'monthly'  => $periods,
        };

        // Calculate based on type
        if ($type === 'term') {
            $monthlyInterest  = round($amount * ($interest / 100), 2);
            $totalInterest    = round($amount * ($interest / 100) * $periodsInMonths, 2);
            $totalAmount      = round($amount + $totalInterest, 2);
            $suggestedPayment = round($totalAmount / $periods, 2);
        } else {
            $monthlyInterest  = round($amount * ($interest / 100), 2);
            $totalAmount      = null;
            $suggestedPayment = $monthlyInterest;
            $totalInterest    = null;
        }

        // Evaluate payment capacity
        $evaluation = null;

        if ($request->customer_income && $request->income_frequency) {
            $income      = floatval($request->customer_income);
            $incomeFreq  = $request->income_frequency;

            $monthlyIncome = match($incomeFreq) {
                'weekly'   => $income * 4,
                'biweekly' => $income * 2,
                'monthly'  => $income,
            };

            $monthlyPayment = match($freq) {
                'weekly'   => $suggestedPayment * 4,
                'biweekly' => $suggestedPayment * 2,
                'monthly'  => $suggestedPayment,
            };

            $currentCommitment = 0;
            if ($request->customer_id) {
                $currentCommitment = Loan::where('customer_id', $request->customer_id)
                    ->whereIn('status', ['active', 'overdue'])
                    ->get()
                    ->sum(function ($loan) {
                        $payment = $loan->type === 'term'
                            ? round($loan->remaining_balance / max($loan->number_of_periods, 1), 2)
                            : round($loan->original_amount * ($loan->interest_rate / 100), 2);

                        return match($loan->payment_frequency) {
                            'weekly'   => $payment * 4,
                            'biweekly' => $payment * 2,
                            'monthly'  => $payment,
                        };
                    });
            }

            $totalMonthlyCommitment = $monthlyPayment + $currentCommitment;
            $percentage = round(($totalMonthlyCommitment / $monthlyIncome) * 100, 1);

            $maxLimit   = Setting::get('simulator_max_percentage', 40);
            $alertLimit = Setting::get('simulator_alert_percentage', 30);

            if ($percentage <= $alertLimit) {
                $evaluation = [
                    'status'  => 'green',
                    'title'   => 'Viable',
                    'message' => "El pago representa el {$percentage}% del ingreso mensual. El cliente puede pagarlo cómodamente.",
                    'color'   => '#1f6b21',
                    'bg'      => '#e8f5e9',
                ];
            } elseif ($percentage <= $maxLimit) {
                $evaluation = [
                    'status'  => 'yellow',
                    'title'   => 'Precaución',
                    'message' => "El pago representa el {$percentage}% del ingreso mensual. Este es el límite recomendado.",
                    'color'   => '#e65100',
                    'bg'      => '#fff3e0',
                ];
            } else {
                $evaluation = [
                    'status'  => 'red',
                    'title'   => 'No recomendado',
                    'message' => "El pago representa el {$percentage}% del ingreso mensual. El cliente podría tener dificultades para pagar.",
                    'color'   => '#c0392b',
                    'bg'      => '#fdecea',
                ];
            }

            $evaluation['percentage']          = $percentage;
            $evaluation['monthly_income']       = $monthlyIncome;
            $evaluation['monthly_payment']      = $monthlyPayment;
            $evaluation['current_commitment']   = $currentCommitment;
            $evaluation['total_commitment']     = $totalMonthlyCommitment;
        }

        return response()->json([
            'amount'            => $amount,
            'interest_rate'     => $interest,
            'type'              => $type,
            'frequency'         => $freq,
            'periods'           => $periods,
            'periods_in_months' => $periodsInMonths,
            'monthly_interest'  => $monthlyInterest,
            'total_interest'    => $totalInterest,
            'total_amount'      => $totalAmount,
            'suggested_payment' => $suggestedPayment,
            'evaluation'        => $evaluation,
        ]);
    }
}