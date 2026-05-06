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
    public function overdue()
    {
        $loans = Loan::with('customer')
                     ->where('status', 'overdue')
                     ->where('restructured', false)
                     ->latest()
                     ->paginate(15);

        return view('restructuring.overdue', compact('loans'));
    }

    public function active()
    {
        $loans = Loan::with(['customer', 'restructurings'])
                     ->where('restructured', true)
                     ->whereIn('status', ['active', 'overdue'])
                     ->latest()
                     ->paginate(15);

        return view('restructuring.active', compact('loans'));
    }

    public function history()
    {
        $loans = Loan::with(['customer', 'restructurings'])
                     ->where('restructured', true)
                     ->where('status', 'paid')
                     ->latest('updated_at')
                     ->paginate(15);

        return view('restructuring.history', compact('loans'));
    }

    public function create(Loan $loan)
    {
        if ($loan->status === 'paid') {
            return redirect()->route('restructuring.overdue')
                             ->with('error', 'Este préstamo ya está pagado.');
        }

        $loan->load(['customer', 'restructurings']);
        $daysOverdue = $this->calculateDaysOverdue($loan);

        return view('restructuring.create', compact('loan', 'daysOverdue'));
    }

    public function store(Request $request, Loan $loan)
    {
        $request->validate([
            'type'                   => ['required', 'in:forgiveness,extension,new_loan'],
            'reason'                 => ['required', 'string'],
            'notes'                  => ['nullable', 'string'],
            'percentage_forgiveness' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'new_periods'            => ['nullable', 'integer', 'min:1'],
            'new_frequency'          => ['nullable', 'in:weekly,biweekly,monthly,daily'],
            'new_amount'             => ['nullable', 'numeric', 'min:1'],
            'new_interest_rate'      => ['nullable', 'numeric', 'min:0'],
            'new_type'               => ['nullable', 'in:interest,term,daily'],
        ]);

        DB::transaction(function () use ($request, $loan) {
            $type = $request->type;

            $restructuringData = [
                'original_loan_id'         => $loan->id,
                'recorded_by'              => auth()->id(),
                'type'                     => $type,
                'original_penalty'         => $loan->accumulated_penalty,
                'balance_at_restructuring' => $loan->remaining_balance,
                'reason'                   => $request->reason,
                'notes'                    => $request->notes,
                'forgiven_penalty'         => 0,
                'remaining_penalty'        => $loan->accumulated_penalty,
            ];

            if ($type === 'forgiveness') {
                $percentage     = floatval($request->percentage_forgiveness);
                $forgivenAmount = round($loan->accumulated_penalty * ($percentage / 100), 2);
                $remaining      = round($loan->accumulated_penalty - $forgivenAmount, 2);

                $loan->accumulated_penalty = $remaining;
                $loan->status              = 'active';
                $loan->restructured        = true;
                $loan->next_payment_date   = $this->calculateNextPayment(
                    Carbon::today()->toDateString(),
                    $loan->payment_frequency
                );
                $loan->save();

                $restructuringData['forgiven_penalty']  = $forgivenAmount;
                $restructuringData['remaining_penalty'] = $remaining;

            } elseif ($type === 'extension') {
                $restructuringData['previous_periods'] = $loan->number_of_periods;
                $restructuringData['new_periods']      = $request->new_periods;

                $loan->number_of_periods  = $request->new_periods;
                $loan->payment_frequency  = $request->new_frequency ?? $loan->payment_frequency;
                $loan->accumulated_penalty = 0;
                $loan->status             = 'active';
                $loan->restructured       = true;
                $loan->next_payment_date  = $this->calculateNextPayment(
                    Carbon::today()->toDateString(),
                    $loan->payment_frequency
                );
                $loan->save();

            } elseif ($type === 'new_loan') {
                $loan->status       = 'refinanced';
                $loan->restructured = true;
                $loan->save();

                $newAmount    = floatval($request->new_amount ?? $loan->remaining_balance);
                $newType      = $request->new_type ?? $loan->type;
                $newFrequency = $request->new_frequency ?? $loan->payment_frequency;
                $newRate      = floatval($request->new_interest_rate ?? $loan->interest_rate);
                $newPeriods   = intval($request->new_periods ?? 1);

                $newBalance        = $newAmount;
                $newAccruedInterest = 0;
                $dailyPayment      = null;

                if ($newType === 'term') {
                    $periodsInMonths = match($newFrequency) {
                        'weekly'   => round($newPeriods / 4, 2),
                        'biweekly' => round($newPeriods / 2, 2),
                        'monthly'  => $newPeriods,
                    };
                    $newAccruedInterest = round($newAmount * ($newRate / 100) * $periodsInMonths, 2);
                    $newBalance = $newAmount + $newAccruedInterest;
                } elseif ($newType === 'daily') {
                    $newFrequency       = 'daily';
                    $newAccruedInterest = round($newAmount * ($newRate / 100), 2);
                    $newBalance         = $newAmount + $newAccruedInterest;
                    $dailyPayment       = round($newBalance / $newPeriods, 2);
                }

                $newLoan = Loan::create([
                    'customer_id'        => $loan->customer_id,
                    'type'               => $newType,
                    'payment_frequency'  => $newFrequency,
                    'number_of_periods'  => $newPeriods,
                    'original_amount'    => $newAmount,
                    'remaining_balance'  => $newBalance,
                    'interest_rate'      => $newRate,
                    'accrued_interest'   => $newAccruedInterest,
                    'pending_interest'   => 0,
                    'daily_payment'      => $dailyPayment,
                    'accumulated_penalty' => 0,
                    'penalty_type'       => $loan->penalty_type,
                    'penalty_value'      => $loan->penalty_value,
                    'grace_days'         => $loan->grace_days,
                    'start_date'         => Carbon::today()->toDateString(),
                    'next_payment_date'  => $this->calculateNextPayment(
                        Carbon::today()->toDateString(),
                        $newFrequency
                    ),
                    'status'             => 'active',
                    'restructured'       => true,
                    'notes'              => 'Reestructurado del préstamo #' . $loan->id,
                ]);

                $restructuringData['new_loan_id'] = $newLoan->id;
            }

            Restructuring::create($restructuringData);
            \App\Models\ActivityLog::log('create', 'restructuring', 'Creó reestructuración del préstamo #' . $loan->id, $loan);
        });

        return redirect()->route('restructuring.active')
                         ->with('success', 'Préstamo reestructurado correctamente.');
    }

    public function pdf(Restructuring $restructuring)
    {
        $restructuring->load([
            'originalLoan.customer',
            'newLoan',
            'recordedBy',
        ]);

        $pdf = Pdf::loadView('restructuring.pdf', compact('restructuring'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("restructuring-{$restructuring->id}.pdf");
    }

    private function calculateDaysOverdue(Loan $loan): int
    {
        if (!$loan->next_payment_date) return 0;
        return (int) max(0, Carbon::today()->diffInDays($loan->next_payment_date, false) * -1);
    }

    private function calculateNextPayment(string $date, string $frequency): string
    {
        $carbon = Carbon::parse($date);
        return match($frequency) {
            'daily'    => $carbon->addDay()->toDateString(),
            'weekly'   => $carbon->addWeek()->toDateString(),
            'biweekly' => $carbon->addDays(15)->toDateString(),
            'monthly'  => $carbon->addMonth()->toDateString(),
            default    => $carbon->addMonth()->toDateString(),
        };
    }
}