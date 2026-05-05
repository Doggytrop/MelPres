@extends('layouts.app')

@section('title', 'Detalle Préstamo')

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ route('loans.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a préstamos
    </a>
    <div class="d-flex gap-2">
        @if($loan->status !== 'paid' && $loan->status !== 'refinanced')
            <a href="{{ route('restructuring.create', $loan) }}"
               style="font-size:12px; color:#e65100; text-decoration:none; border:0.5px solid #ffcc80; border-radius:6px; padding:5px 12px;">
                Reestructurar
            </a>
        @endif
        <a href="{{ route('loans.edit', $loan) }}"
           style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:5px 12px;">
            Editar préstamo
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:#e8f5e9; border-color:#c8e6c9 !important; color:#1f6b21; font-size:13px;">
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

@if($loan->status === 'overdue')
    <div class="rounded-3 p-3 mb-4 d-flex align-items-center gap-3"
         style="background:#fdecea; border:0.5px solid #f5c6c6;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.5">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div>
            <p class="fw-medium mb-0" style="color:#c0392b; font-size:13px;">Préstamo vencido</p>
            <p class="mb-0" style="color:#c0392b; font-size:12px;">
                Este préstamo tiene pagos atrasados. Mora acumulada: ${{ number_format($loan->accumulated_penalty, 2) }}
            </p>
        </div>
    </div>
@endif

@php
    $totalPagado   = $loan->payments->sum('amount_paid');
    $totalCapital  = $loan->payments->sum('capital_payment');
    $totalInteres  = $loan->payments->sum('interest_payment');
    $totalMora     = $loan->payments->sum('penalty_payment');

    $typeColors = [
        'interest' => ['bg' => '#fff3e0', 'color' => '#e65100', 'accent' => '#e65100'],
        'term'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'accent' => '#1f6b21'],
        'daily'    => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'accent' => '#1565c0'],
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
@endphp

{{-- Badge de tipo --}}
<div class="rounded-3 p-3 mb-4 d-flex align-items-center justify-content-between"
     style="background:{{ $tc['bg'] }}; border:0.5px solid {{ $tc['color'] }}20;">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center"
             style="width:42px; height:42px; background:{{ $tc['color'] }}15;">
            @if($loan->type === 'daily')
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $tc['color'] }}" stroke-width="1.5">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
                </svg>
            @elseif($loan->type === 'term')
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $tc['color'] }}" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/>
                </svg>
            @else
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $tc['color'] }}" stroke-width="1.5">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            @endif
        </div>
        <div>
            <span class="fw-medium" style="font-size:15px; color:{{ $tc['color'] }};">
                Préstamo {{ $loan->type_label }}
            </span>
            <span class="d-block" style="font-size:12px; color:{{ $tc['color'] }}99;">
                #{{ $loan->id }} — {{ $loan->customer->full_name }}
            </span>
        </div>
    </div>
    <div class="text-end">
        @php
            $statusBadge = match($loan->status) {
                'active'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                'paid'       => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Pagado'],
                'overdue'    => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Vencido'],
                'refinanced' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Refinanciado'],
            };
        @endphp
        <span class="px-3 py-1 rounded-pill fw-medium"
              style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['color'] }}; font-size:12px;">
            {{ $statusBadge['label'] }}
        </span>
        @if($diasRestantes !== null && $loan->status === 'active')
            <span class="d-block mt-1" style="font-size:11px; color:{{ $tc['color'] }}99;">
                {{ $diasRestantes }} días restantes
            </span>
        @endif
    </div>
</div>

{{-- Métricas --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total pagado</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1a2e1a;">${{ number_format($totalPagado, 2) }}</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo pendiente</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1f6b21;">${{ number_format($loan->remaining_balance, 2) }}</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                {{ $loan->type === 'daily' ? 'Pago diario' : ($loan->type === 'interest' ? 'Pago por periodo' : 'Pago sugerido') }}
            </span>
            <span class="d-block fw-medium" style="font-size:20px; color:{{ $tc['color'] }};">
                @if($loan->type === 'daily')
                    ${{ number_format($loan->daily_payment, 2) }}
                @elseif($loan->type === 'interest')
                    ${{ number_format($loan->monthly_interest, 2) }}
                @else
                    ${{ number_format($loan->suggested_payment, 2) }}
                @endif
            </span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:{{ $totalMora > 0 ? '#f5c6c6' : '#e8e8e8' }} !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora cobrada</span>
            <span class="d-block fw-medium" style="font-size:20px; color:{{ $totalMora > 0 ? '#c0392b' : '#1a2e1a' }};">
                ${{ number_format($totalMora, 2) }}
            </span>
        </div>
    </div>
</div>

{{-- Barra de progreso --}}
<div class="bg-white border rounded-3 p-3 p-md-4 mb-4" style="border-color:#e8e8e8 !important;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span style="font-size:13px; color:#1a2e1a; font-weight:500;">Progreso del préstamo</span>
        <span style="font-size:13px; color:{{ $tc['color'] }}; font-weight:500;">{{ $porcentajePagado }}% pagado</span>
    </div>
    <div class="rounded-pill overflow-hidden" style="height:10px; background:#e8e8e8;">
        <div class="rounded-pill" style="height:10px; width:{{ $porcentajePagado }}%; background:{{ $tc['color'] }}; transition:width .3s;"></div>
    </div>
    <div class="d-flex justify-content-between mt-2">
        <span class="text-muted" style="font-size:11px;">${{ number_format($totalPagado, 2) }} pagado</span>
        <span class="text-muted" style="font-size:11px;">
            @if($loan->type === 'interest')
                ${{ number_format($loan->original_amount, 2) }} capital
            @else
                ${{ number_format($loan->original_amount + $loan->accrued_interest, 2) }} total
            @endif
        </span>
    </div>

    {{-- Barra de tiempo para daily --}}
    @if($loan->type === 'daily' && $loan->number_of_periods > 0)
        <div class="mt-3 pt-3" style="border-top:0.5px solid #f0f0f0;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:12px; color:#1a2e1a;">Tiempo transcurrido</span>
                <span style="font-size:12px; color:#1565c0; font-weight:500;">
                    {{ min($diasTranscurridos, $loan->number_of_periods) }} / {{ $loan->number_of_periods }} días
                </span>
            </div>
            <div class="rounded-pill overflow-hidden" style="height:6px; background:#e3f2fd;">
                <div class="rounded-pill" style="height:6px; width:{{ min(100, round(($diasTranscurridos / $loan->number_of_periods) * 100)) }}%; background:#1565c0; transition:width .3s;"></div>
            </div>
        </div>
    @endif
</div>

<div class="row g-4">

    {{-- Panel izquierdo --}}
    <div class="col-12 col-md-4">

        {{-- Cliente --}}
        <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente</p>
            <div class="d-flex align-items-center gap-3">
                @if($loan->customer->photo_url)
                    <img src="{{ $loan->customer->photo_url }}" alt="Foto"
                         class="rounded-circle" style="width:42px; height:42px; object-fit:cover; flex-shrink:0;">
                @else
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium flex-shrink-0"
                         style="width:42px; height:42px; background:#e8f5e9; color:#1f6b21; font-size:16px;">
                        {{ strtoupper(substr($loan->customer->first_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <a href="{{ route('customers.show', $loan->customer) }}"
                       style="font-weight:500; color:#1a2e1a; text-decoration:none; font-size:14px;">
                        {{ $loan->customer->full_name }}
                    </a>
                    <p class="mb-0 text-muted" style="font-size:12px;">{{ $loan->customer->phone ?? '—' }}</p>
                </div>
            </div>

            {{-- Score del cliente --}}
            @php
                $scoreService = app(\App\Services\ScoreService::class);
                $sd = $scoreService->etiqueta($loan->customer->score ?? 100);
            @endphp
            <div class="mt-3 pt-3 d-flex align-items-center justify-content-between" style="border-top:0.5px solid #f0f0f0;">
                <span class="text-muted" style="font-size:12px;">Score de crédito</span>
                <span class="px-2 py-1 rounded-2"
                      style="background:{{ $sd['bg'] }}; color:{{ $sd['color'] }}; font-size:11px; font-weight:500;">
                    {{ $loan->customer->score ?? 100 }} — {{ $sd['label'] }}
                </span>
            </div>
        </div>

        {{-- Detalle del préstamo --}}
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Detalle del préstamo</p>

            @php
                $rows = [];

                // Campos comunes
                $rows['Monto original'] = '$' . number_format($loan->original_amount, 2);
                $rows['Saldo restante'] = '$' . number_format($loan->remaining_balance, 2);

                // Campos específicos por tipo
                if ($loan->type === 'daily') {
                    $rows['Interés total']  = $loan->interest_rate . '%';
                    $rows['Interés en $']   = '$' . number_format($loan->accrued_interest, 2);
                    $rows['Total a pagar']  = '$' . number_format($loan->original_amount + $loan->accrued_interest, 2);
                    $rows['Pago diario']    = '$' . number_format($loan->daily_payment, 2);
                    $rows['Días del plazo'] = $loan->number_of_periods . ' días';
                } elseif ($loan->type === 'term') {
                    $rows['Interés mensual'] = $loan->interest_rate . '%';
                    $rows['Interés total']   = '$' . number_format($loan->accrued_interest, 2);
                    $rows['Total a pagar']   = '$' . number_format($loan->original_amount + $loan->accrued_interest, 2);
                    $rows['Frecuencia']      = $loan->frequency_label;
                    $rows['Periodos']        = $loan->number_of_periods ?? '—';
                } else {
                    $rows['Interés mensual']  = $loan->interest_rate . '%';
                    $rows['Pago por periodo'] = '$' . number_format($loan->monthly_interest, 2);
                    $rows['Frecuencia']       = $loan->frequency_label;
                }

                $rows['Mora acumulada'] = '$' . number_format($loan->accumulated_penalty, 2);
                $rows['Fecha inicio']   = $loan->start_date->format('d/m/Y');
                $rows['Próximo pago']   = $loan->next_payment_date?->format('d/m/Y') ?? '—';
                $rows['Vencimiento']    = $loan->due_date?->format('d/m/Y') ?? '—';
            @endphp

            {{-- Tipo badge --}}
            <div class="d-flex justify-content-between align-items-center py-2"
                 style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                <span class="text-muted">Tipo</span>
                <span class="px-2 py-1 rounded-2"
                      style="background:{{ $tc['bg'] }}; color:{{ $tc['color'] }}; font-size:11px; font-weight:500;">
                    {{ $loan->type_label }}
                </span>
            </div>

            @foreach($rows as $key => $val)
                <div class="d-flex justify-content-between align-items-center py-2"
                     style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">{{ $key }}</span>
                    <span style="color:{{ 
                        $key === 'Saldo restante' ? '#1f6b21' : 
                        ($key === 'Mora acumulada' && $loan->accumulated_penalty > 0 ? '#c0392b' : 
                        (in_array($key, ['Pago diario', 'Total a pagar']) ? $tc['color'] : '#333')) 
                    }};
                    font-weight:{{ in_array($key, ['Saldo restante', 'Mora acumulada', 'Pago diario', 'Total a pagar', 'Pago por periodo']) ? '500' : '400' }};">
                        {{ $val }}
                    </span>
                </div>
            @endforeach

            @if($loan->notes)
                <div class="mt-3 pt-2" style="border-top:0.5px solid #f5f5f5;">
                    <p class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Observaciones</p>
                    <p style="font-size:13px; color:#555; margin:0;">{{ $loan->notes }}</p>
                </div>
            @endif

            @if($loan->penalty_type)
                <div class="mt-3 pt-2" style="border-top:0.5px solid #f5f5f5;">
                    <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Configuración de mora</p>
                    <div class="d-flex justify-content-between" style="font-size:12px;">
                        <span class="text-muted">Tipo</span>
                        <span>{{ $loan->penalty_type === 'fixed' ? 'Fija diaria' : 'Porcentaje por periodo' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:12px;">
                        <span class="text-muted">Valor</span>
                        <span>{{ $loan->penalty_type === 'fixed' ? '$' . $loan->penalty_value . ' / día' : $loan->penalty_value . '% del saldo' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:12px;">
                        <span class="text-muted">Días de gracia</span>
                        <span>{{ $loan->grace_days }} días</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Panel derecho --}}
    <div class="col-12 col-md-8">
        @include('loans._payments', ['loan' => $loan])
    </div>

</div>

@endsection