<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Http\Requests\StoreLoanRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with('customer')
                    ->whereIn('status', ['active', 'overdue', 'refinanced']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->latest()->paginate(15);

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();
        $loan      = new Loan();

        return view('loans.create', compact('customers', 'loan'));
    }

    public function store(StoreLoanRequest $request)
    {
        $data = $request->validated();
        $data['number_of_periods'] = isset($data['number_of_periods']) ? (int) $data['number_of_periods'] : null;
        $data['remaining_balance']   = $data['original_amount'];
        $data['accumulated_penalty'] = 0;
        $data['pending_interest']    = 0;
        $data['accrued_interest']    =  0;

        // — Préstamo a Plazo —
        if ($data['type'] === 'term') {
            $data['payment_frequency'] = $data['payment_frequency'] ?? 'monthly';

            $periodsInMonths = match($data['payment_frequency']) {
                'weekly'   => round($data['number_of_periods'] / 4, 2),
                'biweekly' => round($data['number_of_periods'] / 2, 2),
                'monthly'  => $data['number_of_periods'],
            };

            $data['accrued_interest']  = round(
                $data['original_amount'] * ($data['interest_rate'] / 100) * $periodsInMonths, 2
            );
            $data['remaining_balance'] = $data['original_amount'] + $data['accrued_interest'];

            $data['due_date'] = $this->calculateDueDate(
                $data['start_date'],
                $data['payment_frequency'],
                $data['number_of_periods']
            );
        }

        // — Préstamo de Interés —
        if ($data['type'] === 'interest') {
            $data['payment_frequency'] = $data['payment_frequency'] ?? 'monthly';
        }

        // — Préstamo Diario —
        if ($data['type'] === 'daily') {
            $data['payment_frequency'] = 'daily';

            $totalInterest = round($data['original_amount'] * ($data['interest_rate'] / 100), 2);
            $totalAmount   = $data['original_amount'] + $totalInterest;

            $data['accrued_interest']  = $totalInterest;
            $data['remaining_balance'] = $totalAmount;
            $data['daily_payment']     = round($totalAmount / $data['number_of_periods'], 2);

            $data['due_date'] = Carbon::parse($data['start_date'])
                                      ->addDays($data['number_of_periods'])
                                      ->toDateString();
        }

        // — Calcular próximo pago —
        $data['next_payment_date'] = $this->calculateNextPayment(
            $data['start_date'],
            $data['payment_frequency']
        );

        $loan = Loan::create($data);
        \App\Models\ActivityLog::log('create', 'loans', 'Creó préstamo #' . $loan->id, $loan);

        return redirect()->route('loans.index')
                         ->with('success', 'Préstamo registrado correctamente.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['customer', 'payments']);

        return view('loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();

        return view('loans.edit', compact('loan', 'customers'));
    }

    public function update(StoreLoanRequest $request, Loan $loan)
    {
        $loan->update($request->validated());

        return redirect()->route('loans.show', $loan)
                         ->with('success', 'Préstamo actualizado correctamente.');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return redirect()->route('loans.index')
                         ->with('success', 'Préstamo eliminado correctamente.');
    }

    public function searchCustomer(Request $request)
    {
        $search = $request->get('q');

        $customers = Customer::where('status', 'active')
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->with(['activeLoans'])
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id'    => $customer->id,
                    'name'  => $customer->full_name,
                    'phone' => $customer->phone ?? '—',
                    'loans' => $customer->activeLoans->map(function ($loan) {
                        return [
                            'id'      => $loan->id,
                            'type'    => $loan->type_label,
                            'balance' => number_format($loan->remaining_balance, 2),
                            'penalty' => number_format($loan->accumulated_penalty, 2),
                            'url'     => route('loans.payments.store', $loan->id),
                        ];
                    }),
                ];
            });

        return response()->json($customers);
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

    private function calculateDueDate(string $startDate, string $frequency, int $periods): string
    {
        $carbon = Carbon::parse($startDate);

        return match($frequency) {
            'weekly'   => $carbon->addWeeks($periods)->toDateString(),
            'biweekly' => $carbon->addDays($periods * 15)->toDateString(),
            'monthly'  => $carbon->addMonths($periods)->toDateString(),
            default    => $carbon->addMonths($periods)->toDateString(),
        };
    }

    public function contract(Loan $loan)
    {
        $loan->load('customer');
        $company = [
            'name'    => Setting::get('company_name', 'Mi Empresa'),
            'phone'   => Setting::get('company_phone'),
            'email'   => Setting::get('company_email'),
            'address' => Setting::get('company_address'),
        ];
        $pdf = Pdf::loadView('loans.pdf.contract', compact('loan', 'company'));
        return $pdf->download("contrato-{$loan->id}.pdf");
    }

    public function promissoryNote(Loan $loan)
    {
        $loan->load('customer');
        $company = [
            'name'    => Setting::get('company_name', 'Mi Empresa'),
            'phone'   => Setting::get('company_phone'),
            'email'   => Setting::get('company_email'),
            'address' => Setting::get('company_address'),
        ];
        $pdf = Pdf::loadView('loans.pdf.promissory-note', compact('loan', 'company'));
        return $pdf->download("pagaré-{$loan->id}.pdf");
    }

}