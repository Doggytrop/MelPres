@extends('layouts.app')

@section('title', 'Corte de caja')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Corte de caja</h5>
        <span class="text-muted" style="font-size:13px;">Resumen de payments del día</span>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('cash-register.index') }}" class="d-flex gap-2">
            <input type="date" name="fecha" value="{{ $fecha }}"
                   class="form-control form-control-sm"
                   style="border-radius:8px; font-size:13px;"
                   onchange="this.form.submit()">
        </form>
        <a href="{{ route('cash-register.pdf', ['fecha' => $fecha]) }}" target="_blank"           class="btn btn-sm d-flex align-items-center gap-2"
           style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M12 18v-6M9 15l3 3 3-3"/>
            </svg>
            Exportar PDF
        </a>
    </div>
</div>

{{-- Métricas --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total cobrado</span>
            <h3 class="fw-medium mb-0" style="color:#1f6b21; font-size:24px;">${{ number_format($totalCobrado, 2) }}</h3>
            <span class="text-muted" style="font-size:12px;">{{ $payments->count() }} payments</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Abono capital</span>
            <h3 class="fw-medium mb-0" style="color:#1a2e1a; font-size:24px;">${{ number_format($totalCapital, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés cobrado</span>
            <h3 class="fw-medium mb-0" style="color:#1f6b21; font-size:24px;">${{ number_format($totalinterest, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:{{ $totalMora > 0 ? '#f5c6c6' : '#e8e8e8' }} !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora cobrada</span>
            <h3 class="fw-medium mb-0" style="color:{{ $totalMora > 0 ? '#c0392b' : '#1a2e1a' }}; font-size:24px;">${{ number_format($totalMora, 2) }}</h3>
        </div>
    </div>
</div>

{{-- Resumen por advisor (solo admin) --}}
@if(auth()->user()->isAdmin() && $poradvisor->count() > 1)
    <div class="bg-white border rounded-3 overflow-hidden mb-4" style="border-color:#e8e8e8 !important;">
        <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
            <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Resumen por advisor</span>
        </div>
        <table class="table mb-0" style="font-size:14px;">
            <thead style="background:#f8f9f8;">
                <tr>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">advisor</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">payments</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total cobrado</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora</th>
                </tr>
            </thead>
            <tbody>
                @foreach($poradvisor as $advisorId => $paymentsPoradvisor)
                    <tr style="border-top:0.5px solid #f0f0f0;">
                        <td class="px-4 py-3" style="color:#1a2e1a; font-weight:500;">
                            {{ $paymentsPoradvisor->first()->recordedBy?->name ?? 'Sin advisor' }}
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $paymentsPoradvisor->count() }}</td>
                        <td class="px-4 py-3" style="color:#1f6b21; font-weight:500;">
                            ${{ number_format($paymentsPoradvisor->sum('amount_paid'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-muted">
                            ${{ number_format($paymentsPoradvisor->sum('interestt_payment'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-muted">
                            ${{ number_format($paymentsPoradvisor->sum('penalty_payment'), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Listado detallado de payments --}}
<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">
            Detalle de payments — {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
        </span>
    </div>

    @if($payments->isEmpty())
        <div class="text-center py-5 text-muted" style="font-size:13px;">
            No hay payments registrados en esta fecha.
        </div>
    @else
        @foreach($payments as $payment)
            <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0 fw-medium" style="font-size:13px; color:#1a2e1a;">
                            {{ $payment->loan->customer?->first_name_complete ?? 'customer eliminado' }}
                        </p>
                        <p class="mb-0 text-muted" style="font-size:11px;">
                            Préstamo #{{ $payment->loan_id }}
                            @if(auth()->user()->isAdmin())
                                · advisor: {{ $payment->recordedBy?->name ?? '—' }}
                            @endif
                            · {{ $payment->payment_date->format('d/m/Y') }}
                        </p>
                        <div class="d-flex gap-2 mt-1" style="font-size:11px; color:#888;">
                            @if($payment->capital_payment > 0)
                                <span>Capital: ${{ number_format($payment->capital_payment, 2) }}</span>
                            @endif
                            @if($payment->interestt_payment > 0)
                                <span>Interés: ${{ number_format($payment->interestt_payment, 2) }}</span>
                            @endif
                            @if($payment->penalty_payment > 0)
                                <span>Mora: ${{ number_format($payment->penalty_payment, 2) }}</span>
                            @endif
                        </div>
                    </div>
                    <span style="font-size:15px; color:#1f6b21; font-weight:500;">
                        ${{ number_format($payment->amount_paid, 2) }}
                    </span>
                </div>
            </div>
        @endforeach
    @endif
</div>

@endsection