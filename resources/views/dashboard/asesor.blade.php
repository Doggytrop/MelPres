@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Bienvenido, {{ auth()->user()->name }}</h5>
    <span class="text-muted" style="font-size:13px;">{{ now()->format('l, d \d\e F \d\e Y') }}</span>
</div>

{{-- Métricas del asesor --}}
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cobrado hoy</span>
            <h3 class="fw-medium mb-0" style="color:#1f6b21; font-size:24px;">${{ number_format($totalCobradoHoy, 2) }}</h3>
            <span class="text-muted" style="font-size:12px;">{{ $pagosHoy->count() }} pagos registrados</span>
        </div>
    </div>

    <div class="col-md-4">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Vencen hoy</span>
            <h3 class="fw-medium mb-0" style="color:{{ $vencenHoy->count() > 0 ? '#e65100' : '#1a2e1a' }}; font-size:24px;">
                {{ $vencenHoy->count() }}
            </h3>
            <span class="text-muted" style="font-size:12px;">clientes por cobrar</span>
        </div>
    </div>

    <div class="col-md-4">
        <div class="p-4 rounded-3 bg-white border" style="border-color:{{ $vencidos->count() > 0 ? '#f5c6c6' : '#e8e8e8' }} !important;">
            <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Vencidos</span>
            <h3 class="fw-medium mb-0" style="color:{{ $vencidos->count() > 0 ? '#c0392b' : '#1a2e1a' }}; font-size:24px;">
                {{ $vencidos->count() }}
            </h3>
            <span class="text-muted" style="font-size:12px;">préstamos atrasados</span>
        </div>
    </div>

</div>

<div class="row g-4">

    {{-- Clientes que vencen hoy --}}
    <div class="col-md-6">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Por cobrar hoy</span>
            </div>

            @forelse($vencenHoy as $prestamo)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('prestamos.show', $prestamo) }}"
                               style="font-size:13px; color:#1a2e1a; font-weight:500; text-decoration:none;">
                                {{ $prestamo->cliente?->nombre_completo ?? 'Cliente eliminado' }}
                            </a>
                            <p class="mb-0 text-muted" style="font-size:11px;">
                                {{ ucfirst($prestamo->tipo) }} — {{ ucfirst($prestamo->frecuencia_pago) }}
                            </p>
                        </div>
                        <a href="{{ route('prestamos.show', $prestamo) }}"
                           style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                            Cobrar
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    No hay cobros pendientes hoy.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagos registrados hoy --}}
    <div class="col-md-6">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Mis pagos de hoy</span>
            </div>

            @forelse($pagosHoy as $pago)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-medium" style="font-size:13px; color:#1a2e1a;">
                                {{ $pago->prestamo->cliente?->nombre_completo ?? 'Cliente eliminado' }}
                            </p>
                            <p class="mb-0 text-muted" style="font-size:11px;">
                                {{ $pago->fecha_pago->format('d/m/Y') }}
                            </p>
                        </div>
                        <span style="font-size:14px; color:#1f6b21; font-weight:500;">
                            ${{ number_format($pago->monto_pagado, 2) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    No has registrado pagos hoy.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection