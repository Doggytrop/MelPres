@extends('layouts.app')

@section('title', 'Detalle Cliente')

@section('content')

<div class="mb-4">
    <a href="{{ route('clientes.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a clientes
    </a>
</div>

<div class="row g-4">

    {{-- Info del cliente --}}
    <div class="col-12 col-md-4">
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">

            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium flex-shrink-0"
                     style="width:48px; height:48px; background:#e8f5e9; color:#1f6b21; font-size:18px;">
                    {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                </div>
                <div>
                    <p class="fw-medium mb-0" style="color:#1a2e1a;">{{ $cliente->nombre_completo }}</p>
                    <span style="font-size:11px; color:#888;">ID #{{ $cliente->id }}</span>
                </div>
            </div>

            @php
                $rows = [
                    'DUI'         => $cliente->dui ?? '—',
                    'Teléfono'    => $cliente->telefono ?? '—',
                    'Dirección'   => $cliente->direccion ?? '—',
                    'Referencias' => $cliente->referencias ?? '—',
                    'Notas'       => $cliente->notas ?? '—',
                ];
            @endphp

            @foreach($rows as $key => $val)
                <div class="mb-3">
                    <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">{{ $key }}</span>
                    <span style="font-size:14px; color:#333;">{{ $val }}</span>
                </div>
            @endforeach

            <div class="mt-4">
                <a href="{{ route('clientes.edit', $cliente) }}"
                   class="btn btn-sm w-100"
                   style="background:#1f6b21; color:white; border-radius:8px; font-size:13px;">
                    Editar
                </a>
            </div>
        </div>
    </div>

    {{-- Préstamos activos --}}
    <div class="col-12 col-md-8">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
                 style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamos activos</span>
            </div>

            @forelse($cliente->prestamosActivos as $prestamo)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important; font-size:14px;">
                    <div class="d-flex justify-content-between">
                        <span style="color:#1a2e1a;">#{{ $prestamo->id }} — {{ ucfirst($prestamo->tipo) }}</span>
                        <span style="color:#1f6b21; font-weight:500;">${{ number_format($prestamo->saldo_restante, 2) }}</span>
                    </div>
                    <span class="text-muted" style="font-size:12px;">{{ ucfirst($prestamo->frecuencia_pago) }} · {{ $prestamo->interes_rate }}% mensual</span>
                </div>
            @empty
                <div class="text-center py-5 text-muted" style="font-size:13px;">
                    Este cliente no tiene préstamos activos.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection