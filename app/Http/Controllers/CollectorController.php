<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CollectorController extends Controller
{
    public function index(CompanyContext $companyContext)
    {
        $today     = Carbon::today();
        $collector = auth()->user();
        $companyId = $this->activeCollectorCompanyId($companyContext);

        $frequencies = $collector->collector_frequencies
            ?? ['daily', 'weekly', 'biweekly', 'monthly'];

        $overdueDays = $collector->collector_overdue_days ?? 15;

        $todayLoans = Loan::with([
                'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
            ])
            ->where('company_id', $companyId)
            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
            ->whereIn('status', ['active', 'overdue'])
            ->whereIn('payment_frequency', $frequencies)
            ->whereDate('next_payment_date', $today)
            ->whereDoesntHave('payments', function ($q) use ($today, $companyId) {
                $q->where('payments.company_id', $companyId)
                    ->whereDate('payment_date', $today);
            })
            ->get();

        $overdueLoans = collect();
        if ($overdueDays > 0) {
            $limitDate = $today->copy()->subDays($overdueDays);
            $overdueLoans = Loan::with([
                    'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                ])
                ->where('company_id', $companyId)
                ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
                ->whereIn('status', ['active', 'overdue'])
                ->whereIn('payment_frequency', $frequencies)
                ->whereDate('next_payment_date', '<', $today)
                ->whereDate('next_payment_date', '>=', $limitDate)
                ->whereDoesntHave('payments', function ($q) use ($today, $companyId) {
                    $q->where('payments.company_id', $companyId)
                        ->whereDate('payment_date', $today);
                })
                ->get();
        }

        $collectedToday = Payment::where('recorded_by', auth()->id())
            ->where('company_id', $companyId)
            ->whereDate('payment_date', $today)
            ->whereHas('loan', fn ($query) => $query
                ->where('loans.company_id', $companyId)
                ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId)))
            ->with([
                'loan' => fn ($query) => $query->where('loans.company_id', $companyId),
                'loan.customer' => fn ($query) => $query->where('customers.company_id', $companyId),
            ])
            ->latest()
            ->get();

        $allLoans = $todayLoans->merge($overdueLoans)->unique('id');

        $mapLoans = $allLoans->filter(function ($loan) {
            return $loan->customer && $loan->customer->latitude && $loan->customer->longitude;
        });

        $totalToday     = $todayLoans->count();
        $totalOverdue   = $overdueLoans->count();
        $totalPending   = $allLoans->sum('suggested_payment');
        $totalCollected = $collectedToday->sum('amount_paid');
        $collectCount   = $collectedToday->count();

        return view('collector.index', compact(
            'todayLoans', 'overdueLoans', 'allLoans', 'mapLoans',
            'collectedToday', 'totalToday', 'totalOverdue',
            'totalPending', 'totalCollected', 'collectCount'
        ));
    }

    public function collect(
        Request $request,
        Loan $loan,
        CompanyContext $companyContext,
        PaymentService $paymentService
    )
    {
        $companyId = $this->activeCollectorCompanyId($companyContext);
        $collector = auth()->user();
        $loan = $this->resolveCollectibleLoan($loan, $collector, $companyId);

        $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'notes'       => ['nullable', 'string'],
        ]);

        $paymentService->applyPayment($loan, [
            'amount_paid'  => $request->amount_paid,
            'payment_date' => Carbon::today()->toDateString(),
            'notes'        => $request->notes ?? 'Cobro en campo',
        ]);

        return back()->with('success', 'Cobro de $' . number_format($request->amount_paid, 2) . ' a ' . $loan->customer->full_name . ' registrado.');
    }

    public function adminUpdateConfig(Request $request, User $user, CompanyContext $companyContext)
    {
        $companyId = $this->activeAdminCompanyId($companyContext);
        $user = User::where('users.id', $user->getKey())
            ->where('users.company_id', $companyId)
            ->where('users.role', 'collector')
            ->firstOrFail();

        $request->validate([
            'collector_frequencies'   => ['nullable', 'array'],
            'collector_frequencies.*' => ['in:daily,weekly,biweekly,monthly'],
            'collector_overdue_days'  => ['required', 'integer', 'min:0', 'max:90'],
        ]);

        $frequencies = $request->collector_frequencies ?? [];

        if (empty($frequencies)) {
            return redirect()->route('settings.index')
                             ->with('error', 'El cobrador debe tener al menos una frecuencia asignada.');
        }

        $user->update([
            'collector_frequencies'  => $frequencies,
            'collector_overdue_days' => $request->collector_overdue_days,
        ]);

        \App\Models\ActivityLog::log('update', 'users', 'Actualizó configuración del cobrador ' . $user->name, $user);

        return redirect()->route('settings.index')
                         ->with('success', 'Configuración de ' . $user->name . ' guardada.');
    }

    private function activeCollectorCompanyId(CompanyContext $companyContext): int
    {
        $collector = auth()->user();
        $company = $companyContext->getCompany();

        if ($collector?->role !== 'collector') {
            abort(403, 'No tienes permiso para acceder al módulo de cobrador.');
        }

        if (! $company
            || $company->status !== 'active'
            || ! $collector->company_id
            || (int) $collector->company_id !== (int) $company->id) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        return (int) $company->id;
    }

    private function activeAdminCompanyId(CompanyContext $companyContext): int
    {
        $admin = auth()->user();
        $company = $companyContext->getCompany();

        if ($admin?->role !== 'admin') {
            abort(403, 'No tienes permiso para configurar cobradores.');
        }

        if (! $company
            || $company->status !== 'active'
            || ! $admin->company_id
            || (int) $admin->company_id !== (int) $company->id) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        return (int) $company->id;
    }

    private function resolveCollectibleLoan(Loan $loan, User $collector, int $companyId): Loan
    {
        $today = Carbon::today();
        $frequencies = $collector->collector_frequencies
            ?? ['daily', 'weekly', 'biweekly', 'monthly'];
        $overdueDays = $collector->collector_overdue_days ?? 15;

        return Loan::where('loans.id', $loan->getKey())
            ->where('loans.company_id', $companyId)
            ->whereIn('loans.status', ['active', 'overdue'])
            ->whereIn('loans.payment_frequency', $frequencies)
            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
            ->where(function ($query) use ($today, $overdueDays) {
                $query->whereDate('loans.next_payment_date', $today);

                if ($overdueDays > 0) {
                    $query->orWhere(function ($query) use ($today, $overdueDays) {
                        $query->whereDate('loans.next_payment_date', '<', $today)
                            ->whereDate('loans.next_payment_date', '>=', $today->copy()->subDays($overdueDays));
                    });
                }
            })
            ->whereDoesntHave('payments', function ($query) use ($today, $companyId) {
                $query->where('payments.company_id', $companyId)
                    ->whereDate('payments.payment_date', $today);
            })
            ->with([
                'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
            ])
            ->firstOrFail();
    }
}
