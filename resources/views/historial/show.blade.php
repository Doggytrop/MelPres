@extends('layouts.app')

@section('title', 'Detalle préstamo liquidado')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ route('historial.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver al historial
    </a>
    <a href="{{ route('historial.pdf', $prestamo) }}" target="_blank"
       class="btn btn-sm d-flex align-items-center gap-2"
       style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <path d="M12 18v-6M9 15l3 3 3-3"/>
        </svg>
        Descargar PDF
    </a>
</div>
{{-- Banner pagado --}}
<div class="rounded-3 p-3 mb-4 d-flex align-items-center gap-3"
     style="background:#e3f2fd; border:0.5px solid #bbdefb;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="1.5">
        <path d="M20 6 9 17l-5-5"/>
    </svg>
    <div>
        <p class="fw-medium mb-0" style="color:#1565c0; font-size:13px;">Préstamo liquidado</p>
        <p class="mb-0" style="color:#1565c0; font-size:12px;">
            Este préstamo fue pagado en su totalidad el {{ $prestamo->updated_at->format('d/m/Y') }}
        </p>
    </div>
</div>

{{-- Métricas --}}
@php
    $totalPagado  = $prestamo->pagos->sum('monto_pagado');
    $totalInteres = $prestamo->pagos->sum('abono_interes');
    $totalMora    = $prestamo->pagos->sum('abono_mora');
    $totalCapital = $prestamo->pagos->sum('abono_capital');
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
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Capital prestado</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1a2e1a;">${{ number_format($prestamo->monto_original, 2) }}</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés cobrado</span>
            <span class="d-block fw-medium" style="font-size:20px; color:#1f6b21;">${{ number_format($totalInteres, 2) }}</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="d-block text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora cobrada</span>
            <span class="d-block fw-medium" style="font-size:20px; color:{{ $totalMora > 0 ? '#c0392b' : '#1a2e1a' }};">${{ number_format($totalMora, 2) }}</span>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Info --}}
    <div class="col-md-4">
        <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">

            {{-- Cliente --}}
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente</p>
            <div class="d-flex align-items-center gap-3 mb-4">
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

            {{-- Detalle préstamo --}}
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Detalle del préstamo</p>
            @php
                $rows = [
                    'Monto original'  => '$' . number_format($prestamo->monto_original, 2),
                    'Tipo'            => ucfirst($prestamo->tipo),
                    'Interés'         => $prestamo->interes_rate . '% mensual',
                    'Frecuencia'      => ucfirst($prestamo->frecuencia_pago),
                    'Periodos'        => $prestamo->numero_periodos ?? '—',
                    'Fecha inicio'    => $prestamo->fecha_inicio->format('d/m/Y'),
                    'Fecha liquidado' => $prestamo->updated_at->format('d/m/Y'),
                    'Total pagos'     => $prestamo->pagos->count() . ' pagos',
                ];
            @endphp

            @foreach($rows as $key => $val)
                <div class="d-flex justify-content-between py-2"
                     style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                    <span class="text-muted">{{ $key }}</span>
                    <span style="color:#333;">{{ $val }}</span>
                </div>
            @endforeach

            {{-- Badge pagado --}}
            <div class="d-flex justify-content-between align-items-center pt-2" style="font-size:13px;">
                <span class="text-muted">Estado</span>
                <span class="px-2 py-1 rounded-2"
                      style="background:#e3f2fd; color:#1565c0; font-size:11px; font-weight:500;">
                    Pagado
                </span>
            </div>
        </div>
    </div>

    {{-- Historial de pagos --}}
    <div class="col-md-8">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">
                    Historial de pagos — {{ $prestamo->pagos->count() }} movimientos
                </span>
            </div>

            @foreach($prestamo->pagos as $pago)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span style="font-size:14px; color:#1a2e1a; font-weight:500;">
                                ${{ number_format($pago->monto_pagado, 2) }}
                            </span>
                            <span class="ms-2 px-2 py-1 rounded-2"
                                  style="background:#e8f5e9; color:#1f6b21; font-size:11px;">
                                {{ ucfirst(str_replace('_', ' ', $pago->tipo_pago)) }}
                            </span>
                        </div>
                        <span class="text-muted" style="font-size:12px;">
                            {{ $pago->fecha_pago->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="mt-1 d-flex gap-3" style="font-size:12px; color:#888;">
                        @if($pago->abono_mora > 0)
                            <span>Mora: ${{ number_format($pago->abono_mora, 2) }}</span>
                        @endif
                        @if($pago->abono_interes > 0)
                            <span>Interés: ${{ number_format($pago->abono_interes, 2) }}</span>
                        @endif
                        @if($pago->abono_capital > 0)
                            <span>Capital: ${{ number_format($pago->abono_capital, 2) }}</span>
                        @endif
                        @if($pago->observaciones)
                            <span>— {{ $pago->observaciones }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

@endsection