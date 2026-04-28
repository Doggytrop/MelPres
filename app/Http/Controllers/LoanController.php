<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Http\Requests\StoreLoanRequest;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('customer')
                     ->whereIn('status', ['active', 'overdue', 'refinanced'])
                     ->latest()
                     ->paginate(15);

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

        $data['remaining_balance']   = $data['original_amount'];
        $data['accumulated_penalty'] = 0;
        $data['pending_interest']    = 0;

        if ($data['type'] === 'term' && isset($data['number_of_periods'])) {
            $periodsInMonths = match($data['payment_frequency']) {
                'weekly'   => round($data['number_of_periods'] / 4, 2),
                'biweekly' => round($data['number_of_periods'] / 2, 2),
                'monthly'  => $data['number_of_periods'],
            };

            $data['accrued_interest']  = round(
                $data['original_amount'] * ($data['interest_rate'] / 100) * $periodsInMonths, 2
            );
            $data['remaining_balance'] = $data['original_amount'] + $data['accrued_interest'];
        }

        $data['next_payment_date'] = $this->calculateNextPayment(
            $data['start_date'],
            $data['payment_frequency']
        );

        Loan::create($data);

        return redirect()->route('loans.index')
                         ->with('success', 'Loan created successfully.');
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
                         ->with('success', 'Loan updated successfully.');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return redirect()->route('loans.index')
                         ->with('success', 'Loan deleted successfully.');
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
                    'id'       => $customer->id,
                    'name'     => $customer->full_name,
                    'phone'    => $customer->phone ?? '—',
                    'loans'    => $customer->activeLoans->map(function ($loan) {
                        return [
                            'id'      => $loan->id,
                            'type'    => ucfirst($loan->type),
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
        $carbon = \Carbon\Carbon::parse($date);

        return match($frequency) {
            'weekly'   => $carbon->addWeek()->toDateString(),
            'biweekly' => $carbon->addDays(15)->toDateString(),
            'monthly'  => $carbon->addMonth()->toDateString(),
            default    => $carbon->addMonth()->toDateString(),
        };
    }
}