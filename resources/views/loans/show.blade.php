@extends('layouts.app')

@section('title', 'Detalle Préstamo')

@section('content')

<div class="mb-4">
    <a href="{{ route('loans.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a préstamos
    </a>
</div>

@if(session('success'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:var(--color-secondary); border-color:var(--color-secondary) !important; color:var(--color-primary); font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:#fdecea; border-color:#f5c6c6 !important; color:#c0392b; font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
        </svg>
        {{ session('error') }}
    </div>
@endif

@php
    $totalPagado  = $loan->payments->sum('amount_paid');
    $totalCapital = $loan->payments->sum('capital_payment');
    $totalInteres = $loan->payments->sum('interest_payment');
    $totalMora    = $loan->payments->sum('penalty_payment');

    $typeColors = [
        'interest' => ['bg' => '#fff3e0', 'color' => '#e65100'],
        'term'     => ['bg' => 'var(--color-secondary)', 'color' => 'var(--color-primary)'],
        'daily'    => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
    ];
    $tc = $typeColors[$loan->type];

    if ($loan->type === 'daily' || $loan->type === 'term') {
        $totalBase = $loan->original_amount + $loan->accrued_interest;
        $porcentajePagado = $totalBase > 0 ? min(100, round(($totalPagado / $totalBase) * 100)) : 0;
    } else {
        $porcentajePagado = $loan->original_amount > 0
            ? min(100, round(($totalCapital / $loan->original_amount) * 100))
            : 0;
    }

    $diasRestantes = $loan->due_date ? (int) max(0, now()->diffInDays($loan->due_date, false)) : null;
    $diasTranscurridos = $loan->start_date ? (int) $loan->start_date->diffInDays(now()) : 0;

    $statusBadge = match($loan->status) {
        'active'     => ['bg' => 'var(--color-secondary)', 'color' => 'var(--color-primary)', 'label' => 'Activo'],
        'paid'       => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Pagado'],
        'overdue'    => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Vencido'],
        'refinanced' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Refinanciado'],
    };
@endphp

{{-- Alerta vencido --}}
@if($loan->status === 'overdue')
    <div class="rounded-3 p-3 mb-4 d-flex align-items-center gap-3"
         style="background:#fdecea; border:0.5px solid #f5c6c6;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.5">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div>
            <p class="fw-medium mb-0" style="color:#c0392b; font-size:13px;">Préstamo vencido</p>
            <p class="mb-0" style="color:#c0392b; font-size:12px;">Mora acumulada: ${{ number_format($loan->accumulated_penalty, 2) }}</p>
        </div>
    </div>
@endif

{{-- HEADER --}}
<div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
    <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex align-items-center gap-3">
            @if($loan->customer->photo_url)
                <img src="{{ $loan->customer->photo_url }}" alt="Foto"
                     class="rounded-circle" style="width:44px; height:44px; object-fit:cover; flex-shrink:0;">
            @else
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium flex-shrink-0"
                     style="width:44px; height:44px; background:{{ $tc['bg'] }}; color:{{ $tc['color'] }}; font-size:17px;">
                    {{ strtoupper(substr($loan->customer->first_name, 0, 1)) }}
                </div>
            @endif
            <div>
                <a href="{{ route('customers.show', $loan->customer) }}"
                   style="font-weight:500; color:#1a2e1a; text-decoration:none; font-size:15px;">
                    {{ $loan->customer->full_name }}
                </a>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="px-2 py-1 rounded-2"
                          style="background:{{ $tc['bg'] }}; color:{{ $tc['color'] }}; font-size:10px; font-weight:600;">
                        {{ $loan->type_label }}
                    </span>
                    <span class="text-muted" style="font-size:12px;">#{{ $loan->id }} · {{ $loan->frequency_label }} · {{ $loan->interest_rate }}%{{ $loan->type === 'daily' ? ' total' : '' }}</span>
                </div>
            </div>
        </div>
        <div class="text-end">
            <span class="px-3 py-1 rounded-pill fw-medium"
                  style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['color'] }}; font-size:11px;">
                {{ $statusBadge['label'] }}
            </span>
            @if($diasRestantes !== null && $loan->status === 'active')
                <span class="d-block mt-1 text-muted" style="font-size:11px;">{{ $diasRestantes }} días restantes</span>
            @endif
            @if($loan->status !== 'paid' && $loan->status !== 'refinanced')
                <a href="{{ route('restructuring.create', $loan) }}" class="d-block mt-1"
                   style="font-size:11px; color:#e65100; text-decoration:none;">Reestructurar →</a>
            @endif

            <div class="d-flex gap-2 mt-2">
                <a href="{{ route('loans.contract', $loan) }}" target="_blank"
                    class="px-3 py-1 rounded-2 text-decoration-none"
                    style="background:var(--color-secondary); color:var(--color-primary); font-size:11px; font-weight:500;">
                    📄 Contrato
                </a>
                <a href="{{ route('loans.promissory-note', $loan) }}" target="_blank"
                    class="px-3 py-1 rounded-2 text-decoration-none"
                     style="background:#e3f2fd; color:#1565c0; font-size:11px; font-weight:500;">
                    📋 Pagaré
                </a>
            </div>
        </div>
    </div>
</div>

{{-- DESGLOSE FINANCIERO --}}
<div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Capital</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1a2e1a;">${{ number_format($loan->original_amount, 2) }}</span>
        </div>
        <div class="col-6 col-md-3">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                Interés ({{ $loan->interest_rate }}%{{ $loan->type === 'daily' ? ' total' : ' mensual' }})
            </span>
            <span class="d-block fw-medium" style="font-size:20px; color:{{ $tc['color'] }};">${{ number_format($loan->accrued_interest, 2) }}</span>
        </div>
        <div class="col-6 col-md-3">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                {{ $loan->type === 'interest' ? 'Capital pendiente' : 'Total a pagar' }}
            </span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1a2e1a;">
                @if($loan->type === 'interest')
                    ${{ number_format($loan->original_amount, 2) }}
                @else
                    ${{ number_format($loan->original_amount + $loan->accrued_interest, 2) }}
                @endif
            </span>
        </div>
        <div class="col-6 col-md-3">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo restante</span>
            <span class="d-block fw-medium" style="font-size:20px; color:var(--color-primary);">${{ number_format($loan->remaining_balance, 2) }}</span>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
        <span class="text-muted">Progreso</span>
        <span style="color:{{ $tc['color'] }}; font-weight:500;">{{ $porcentajePagado }}%</span>
    </div>
    <div class="rounded-pill overflow-hidden" style="height:8px; background:#e8e8e8;">
        <div class="rounded-pill" style="height:8px; width:{{ $porcentajePagado }}%; background:{{ $tc['color'] }};"></div>
    </div>
    <div class="d-flex justify-content-between mt-1" style="font-size:11px; color:#888;">
        <span>${{ number_format($totalPagado, 2) }} pagado</span>
        <span>
            @if($loan->type === 'interest')
                ${{ number_format($loan->original_amount, 2) }} capital
            @else
                ${{ number_format($loan->original_amount + $loan->accrued_interest, 2) }} total
            @endif
        </span>
    </div>

    @if($loan->type === 'daily' && $loan->number_of_periods > 0)
        <div class="mt-3 pt-3" style="border-top:0.5px solid #f0f0f0;">
            <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                <span class="text-muted">Tiempo</span>
                <span style="color:#1565c0; font-weight:500;">{{ min($diasTranscurridos, $loan->number_of_periods) }} / {{ $loan->number_of_periods }} días</span>
            </div>
            <div class="rounded-pill overflow-hidden" style="height:6px; background:#e3f2fd;">
                <div class="rounded-pill" style="height:6px; width:{{ min(100, round(($diasTranscurridos / $loan->number_of_periods) * 100)) }}%; background:#1565c0;"></div>
            </div>
        </div>
    @endif
</div>

{{-- RESUMEN RÁPIDO --}}
<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="bg-white border rounded-3 p-3 text-center" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                {{ $loan->type === 'daily' ? 'Pago diario' : 'Pago por periodo' }}
            </span>
            <span class="d-block fw-medium mt-1" style="font-size:22px; color:{{ $tc['color'] }};">
                ${{ number_format($loan->suggested_payment, 2) }}
            </span>
            <span class="text-muted" style="font-size:11px;">{{ $loan->frequency_label }}</span>
        </div>
    </div>
    <div class="col-4">
        <div class="bg-white border rounded-3 p-3 text-center" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total pagado</span>
            <span class="d-block fw-medium mt-1" style="font-size:22px; color:#1a2e1a;">${{ number_format($totalPagado, 2) }}</span>
            <span class="text-muted" style="font-size:11px;">{{ $loan->payments->count() }} pagos</span>
        </div>
    </div>
    <div class="col-4">
        <div class="bg-white border rounded-3 p-3 text-center" style="border-color:{{ $loan->accumulated_penalty > 0 ? '#f5c6c6' : '#e8e8e8' }} !important;">
            <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora</span>
            <span class="d-block fw-medium mt-1" style="font-size:22px; color:{{ $loan->accumulated_penalty > 0 ? '#c0392b' : '#1a2e1a' }};">
                ${{ number_format($loan->accumulated_penalty, 2) }}
            </span>
            @if($totalMora > 0)
                <span class="text-muted" style="font-size:11px;">${{ number_format($totalMora, 2) }} cobrada</span>
            @endif
        </div>
    </div>
</div>

{{-- DETALLE + CALENDARIO --}}
<div class="row g-3 mb-3">

    {{-- Detalle --}}
    <div class="col-md-5">
        <div class="bg-white border rounded-3 p-4 h-100" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Información</p>

            @php
                $details = [];
                if ($loan->type === 'daily') {
                    $details['Interés total']  = $loan->interest_rate . '%';
                    $details['Días del plazo'] = $loan->number_of_periods . ' días';
                    $details['Pago diario']    = '$' . number_format($loan->daily_payment, 2);
                } elseif ($loan->type === 'term') {
                    $details['Interés mensual'] = $loan->interest_rate . '%';
                    $details['Frecuencia']      = $loan->frequency_label;
                    $details['Periodos']        = $loan->number_of_periods;
                } else {
                    $details['Interés mensual']  = $loan->interest_rate . '%';
                    $details['Pago por periodo'] = '$' . number_format($loan->monthly_interest, 2);
                    $details['Frecuencia']       = $loan->frequency_label;
                }
                $details['Inicio']      = $loan->start_date->format('d/m/Y');
                $details['Próximo pago'] = $loan->next_payment_date?->format('d/m/Y') ?? '—';
                $details['Vencimiento'] = $loan->due_date?->format('d/m/Y') ?? '—';
            @endphp

            @foreach($details as $key => $val)
                <div class="d-flex justify-content-between py-2" style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">{{ $key }}</span>
                    <span style="color:{{ in_array($key, ['Pago diario', 'Pago por periodo']) ? $tc['color'] : '#333' }};
                                 font-weight:{{ in_array($key, ['Pago diario', 'Pago por periodo']) ? '500' : '400' }};">
                        {{ $val }}
                    </span>
                </div>
            @endforeach

            @if($loan->penalty_type)
                <div class="mt-3 pt-2" style="border-top:0.5px solid #f0f0f0;">
                    <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora configurada</p>
                    <div class="d-flex justify-content-between" style="font-size:12px;">
                        <span class="text-muted">Tipo</span>
                        <span>{{ $loan->penalty_type === 'fixed' ? 'Fija $' . $loan->penalty_value . '/día' : $loan->penalty_value . '%' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:12px;">
                        <span class="text-muted">Gracia</span>
                        <span>{{ $loan->grace_days }} días</span>
                    </div>
                </div>
            @endif

            @if($loan->notes)
                <div class="mt-3 pt-2" style="border-top:0.5px solid #f0f0f0;">
                    <p class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Notas</p>
                    <p style="font-size:12px; color:#555; margin:0;">{{ $loan->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Calendario de pagos --}}
    <div class="col-md-7">
        <div class="bg-white border rounded-3 p-4 h-100" style="border-color:#e8e8e8 !important;">
            @if($loan->type !== 'interest')
                <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Calendario de pagos</p>

                            @php
                $paymentSchedule = [];
                $periods       = $loan->number_of_periods ?? 0;
                $paymentAmount = $loan->suggested_payment;
                $startDate     = $loan->start_date->copy();

                // Para diario: calcular cuántos periodos cubre el capital pagado
                // Para otros: cada pago = 1 periodo
                if ($loan->type === 'daily' && $paymentAmount > 0) {
                    $totalCapitalPaid = $loan->payments->sum('capital_payment');
                    $paidCount = (int) floor($totalCapitalPaid / $paymentAmount);
                } else {
                    $paidCount = $loan->payments->count();
                }

                for ($i = 1; $i <= $periods; $i++) {
                    if ($loan->type === 'daily') {
                        $date = $startDate->copy()->addDays($i);
                    } else {
                        $date = match($loan->payment_frequency) {
                            'weekly'   => $startDate->copy()->addWeeks($i),
                            'biweekly' => $startDate->copy()->addDays($i * 15),
                            'monthly'  => $startDate->copy()->addMonths($i),
                            default    => $startDate->copy()->addMonths($i),
                        };
                    }

                    $isPaid    = $i <= $paidCount;
                    $isNext    = !$isPaid && ($i === $paidCount + 1);
                    $isOverdue = !$isPaid && $date->isPast();

                    $paymentSchedule[] = [
                        'number'    => $i,
                        'date'      => $date,
                        'amount'    => $paymentAmount,
                        'isPaid'    => $isPaid,
                        'isNext'    => $isNext,
                        'isOverdue' => $isOverdue,
                    ];
                }
@endphp

                <div style="max-height:320px; overflow-y:auto;">
                    @foreach($paymentSchedule as $p)
                        <div class="d-flex align-items-center justify-content-between py-2 px-2 rounded-2 mb-1"
                             style="font-size:12px; {{ $p['isNext'] ? 'background:' . $tc['bg'] . ';' : '' }}">
                            <div class="d-flex align-items-center gap-2">
                                @if($p['isPaid'])
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                @elseif($p['isOverdue'])
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                                @elseif($p['isNext'])
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $tc['color'] }}" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                                @else
                                    <div class="rounded-circle" style="width:14px; height:14px; border:1.5px solid #ccc;"></div>
                                @endif
                                <span style="color:{{ $p['isPaid'] ? 'var(--color-primary)' : ($p['isOverdue'] ? '#c0392b' : ($p['isNext'] ? $tc['color'] : '#888')) }};
                                             font-weight:{{ $p['isNext'] ? '500' : '400' }};">
                                    #{{ $p['number'] }} — {{ $p['date']->format('d/m/Y') }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="color:{{ $p['isPaid'] ? 'var(--color-primary)' : '#888' }};">
                                    ${{ number_format($p['amount'], 2) }}
                                </span>
                                @if($p['isPaid'])
                                    <span class="px-2 rounded-pill" style="background:var(--color-secondary); color:var(--color-primary); font-size:10px;">Pagado</span>
                                @elseif($p['isOverdue'])
                                    <span class="px-2 rounded-pill" style="background:#fdecea; color:#c0392b; font-size:10px;">Atrasado</span>
                                @elseif($p['isNext'])
                                    <span class="px-2 rounded-pill" style="background:{{ $tc['color'] }}; color:white; font-size:10px;">Siguiente</span>
                                @else
                                    <span class="px-2 rounded-pill" style="background:#f5f5f5; color:#888; font-size:10px;">Pendiente</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 pt-2 d-flex justify-content-between" style="border-top:0.5px solid #f0f0f0; font-size:11px;">
                    <span class="text-muted">{{ collect($paymentSchedule)->where('isPaid', true)->count() }} pagados de {{ $periods }}</span>
                    <span style="color:{{ $tc['color'] }}; font-weight:500;">{{ collect($paymentSchedule)->where('isPaid', false)->count() }} pendientes</span>
                </div>
            @else
                <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cobros de interés</p>
                <div class="d-flex justify-content-between py-2" style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">Interés mensual</span>
                    <span class="fw-medium" style="color:#e65100;">${{ number_format($loan->monthly_interest, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">Cobros realizados</span>
                    <span class="fw-medium">{{ $loan->payments->count() }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">Interés cobrado</span>
                    <span class="fw-medium" style="color:var(--color-primary);">${{ number_format($totalInteres, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="font-size:13px;">
                    <span class="text-muted">Capital abonado</span>
                    <span class="fw-medium">${{ number_format($totalCapital, 2) }}</span>
                </div>
                <div class="mt-3 p-2 rounded-2 text-center" style="background:#fff3e0; font-size:11px; color:#e65100;">
                    El capital no disminuye hasta que el cliente liquide
                </div>
            @endif
        </div>
    </div>

</div>

{{-- REGISTRAR PAGO + HISTORIAL --}}
@include('loans._payments', ['loan' => $loan])

@endsection