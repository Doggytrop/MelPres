@extends('layouts.app')

@section('title', 'Detalle Préstamo')

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ route('prestamos.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a préstamos
    </a>
    <a href="{{ route('prestamos.edit', $prestamo) }}"
       style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:5px 12px;">
        Editar préstamo
    </a>
</div>

{{-- Alertas --}}
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

{{-- Banner de mora si está vencido --}}
@if($prestamo->estado === 'vencido')
    <div class="rounded-3 p-3 mb-4 d-flex align-items-center gap-3"
         style="background:#fdecea; border:0.5px solid #f5c6c6;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.5">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div>
            <p class="fw-medium mb-0" style="color:#c0392b; font-size:13px;">Préstamo vencido</p>
            <p class="mb-0" style="color:#c0392b; font-size:12px;">
                Este préstamo tiene pagos atrasados. Mora acumulada: ${{ number_format($prestamo->mora_acumulada, 2) }}
            </p>
        </div>
    </div>
@endif

{{-- Métricas rápidas --}}
@php
    $totalPagado  = $prestamo->pagos->sum('monto_pagado');
    $totalCapital = $prestamo->pagos->sum('abono_capital');
    $totalInteres = $prestamo->pagos->sum('abono_interes');
    $totalMora    = $prestamo->pagos->sum('abono_mora');
    $porcentajePagado = $prestamo->monto_original > 0
        ? min(100, round(($totalCapital / $prestamo->monto_original) * 100))
        : 0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total pagado</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1a2e1a;">${{ number_format($totalPagado, 2) }}</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo pendiente</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1f6b21;">${{ number_format($prestamo->saldo_restante, 2) }}</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés cobrado</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1a2e1a;">${{ number_format($totalInteres, 2) }}</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora cobrada</span>
            <span class="d-block fw-medium" style="font-size:20px; color:{{ $totalMora > 0 ? '#c0392b' : '#1a2e1a' }};">
                ${{ number_format($totalMora, 2) }}
            </span>
        </div>
    </div>
</div>

{{-- Barra de progreso --}}
<div class="bg-white border rounded-3 p-4 mb-4" style="border-color:#e8e8e8 !important;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span style="font-size:13px; color:#1a2e1a; font-weight:500;">Progreso del préstamo</span>
        <span style="font-size:13px; color:#1f6b21; font-weight:500;">{{ $porcentajePagado }}% pagado</span>
    </div>
    <div class="rounded-pill overflow-hidden" style="height:8px; background:#e8e8e8;">
        <div class="rounded-pill" style="height:8px; width:{{ $porcentajePagado }}%; background:#1f6b21; transition:width .3s;"></div>
    </div>
    <div class="d-flex justify-content-between mt-2">
        <span class="text-muted" style="font-size:11px;">${{ number_format($totalCapital, 2) }} abonado al capital</span>
        <span class="text-muted" style="font-size:11px;">${{ number_format($prestamo->monto_original, 2) }} total</span>
    </div>
</div>

<div class="row g-4">

    {{-- Panel izquierdo — info --}}
    <div class="col-md-4">

        {{-- Card cliente --}}
        <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente</p>
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium"
                     style="width:40px; height:40px; background:#e8f5e9; color:#1f6b21; font-size:16px; flex-shrink:0;">
                    {{ strtoupper(substr($prestamo->cliente->nombre, 0, 1)) }}
                </div>
                <div>
                    <a href="{{ route('clientes.show', $prestamo->cliente) }}"
                       style="font-weight:500; color:#1a2e1a; text-decoration:none; font-size:14px;">
                        {{ $prestamo->cliente->nombre_completo }}
                    </a>
                    <p class="mb-0 text-muted" style="font-size:12px;">{{ $prestamo->cliente->telefono ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Card resumen financiero --}}
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Detalle del préstamo</p>

            @php
                $rows = [
                    'Monto original'   => '$' . number_format($prestamo->monto_original, 2),
                    'Saldo restante'   => '$' . number_format($prestamo->saldo_restante, 2),
                    'Interés mensual'  => $prestamo->interes_rate . '%',
                    'Mora acumulada'   => '$' . number_format($prestamo->mora_acumulada, 2),
                    'Tipo'             => ucfirst($prestamo->tipo),
                    'Frecuencia'       => ucfirst($prestamo->frecuencia_pago),
                    'Periodos'         => $prestamo->numero_periodos ?? '—',
                    'Fecha inicio'     => $prestamo->fecha_inicio->format('d/m/Y'),
                    'Próximo pago'     => $prestamo->fecha_proximo_pago?->format('d/m/Y') ?? '—',
                    'Vencimiento'      => $prestamo->fecha_vencimiento?->format('d/m/Y') ?? '—',
                ];
            @endphp

            @foreach($rows as $key => $val)
                <div class="d-flex justify-content-between align-items-center py-2"
                     style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">{{ $key }}</span>
                    <span style="color:{{ in_array($key, ['Saldo restante']) ? '#1f6b21' : ($key === 'Mora acumulada' && $prestamo->mora_acumulada > 0 ? '#c0392b' : '#333') }};
                                 font-weight:{{ in_array($key, ['Saldo restante', 'Mora acumulada']) ? '500' : '400' }};">
                        {{ $val }}
                    </span>
                </div>
            @endforeach

            {{-- Estado --}}
            @php
                $estadoBadge = match($prestamo->estado) {
                    'activo'       => ['bg' => '#e8f5e9', 'color' => '#1f6b21',  'label' => 'Activo'],
                    'pagado'       => ['bg' => '#e3f2fd', 'color' => '#1565c0',  'label' => 'Pagado'],
                    'vencido'      => ['bg' => '#fdecea', 'color' => '#c0392b',  'label' => 'Vencido'],
                    'refinanciado' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a',  'label' => 'Refinanciado'],
                };
            @endphp
            <div class="d-flex justify-content-between align-items-center pt-2" style="font-size:13px;">
                <span class="text-muted">Estado</span>
                <span class="px-2 py-1 rounded-2"
                      style="background:{{ $estadoBadge['bg'] }}; color:{{ $estadoBadge['color'] }}; font-size:11px; font-weight:500;">
                    {{ $estadoBadge['label'] }}
                </span>
            </div>

            @if($prestamo->observaciones)
                <div class="mt-3 pt-2" style="border-top:0.5px solid #f5f5f5;">
                    <p class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Observaciones</p>
                    <p style="font-size:13px; color:#555; margin:0;">{{ $prestamo->observaciones }}</p>
                </div>
            @endif

            {{-- Info mora si tiene configurada --}}
            @if($prestamo->mora_tipo)
                <div class="mt-3 pt-2" style="border-top:0.5px solid #f5f5f5;">
                    <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Configuración de mora</p>
                    <div class="d-flex justify-content-between" style="font-size:12px;">
                        <span class="text-muted">Tipo</span>
                        <span>{{ $prestamo->mora_tipo === 'fija' ? 'Fija diaria' : 'Porcentaje por periodo' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:12px;">
                        <span class="text-muted">Valor</span>
                        <span>{{ $prestamo->mora_tipo === 'fija' ? '$' . $prestamo->mora_valor . ' / día' : $prestamo->mora_valor . '% del saldo' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:12px;">
                        <span class="text-muted">Días de gracia</span>
                        <span>{{ $prestamo->dias_gracia }} días</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Panel derecho — pagos --}}
    <div class="col-md-8">
        @include('prestamos._pagos', ['prestamo' => $prestamo])
    </div>

</div>

@endsection