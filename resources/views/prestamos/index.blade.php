@extends('layouts.app')

@section('title', 'Préstamos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Préstamos</h5>
        <span class="text-muted" style="font-size:13px;">{{ $prestamos->total() }} registrados</span>
    </div>
    <a href="{{ route('prestamos.create') }}"
       class="btn btn-sm"
       style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
        + Nuevo préstamo
    </a>
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

<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    <table class="table mb-0" style="font-size:14px;">
        <thead style="background:#f8f9f8; border-bottom: 1px solid #e8e8e8;">
            <tr>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Tipo</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Monto</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prestamos as $prestamo)
                <tr style="border-top: 0.5px solid #f0f0f0;">
                    <td class="px-4 py-3 text-muted">{{ $prestamo->id }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('prestamos.show', $prestamo) }}"
                           style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                            {{ $prestamo->cliente->nombre_completo }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $tipoBadge = match($prestamo->tipo) {
                                'interes' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Interés'],
                                'plazo'   => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Plazo'],
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-2"
                              style="background:{{ $tipoBadge['bg'] }}; color:{{ $tipoBadge['color'] }}; font-size:11px; font-weight:500;">
                            {{ $tipoBadge['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3" style="color:#1a2e1a;">
                        ${{ number_format($prestamo->monto_original, 2) }}
                    </td>
                    <td class="px-4 py-3" style="color:#1f6b21; font-weight:500;">
                        ${{ number_format($prestamo->saldo_restante, 2) }}
                    </td>
                    <td class="px-4 py-3 text-muted">
                        {{ $prestamo->interes_rate }}%
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $estadoBadge = match($prestamo->estado) {
                                'activo'       => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                                'pagado'       => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Pagado'],
                                'vencido'      => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Vencido'],
                                'refinanciado' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Refinanciado'],
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-2"
                              style="background:{{ $estadoBadge['bg'] }}; color:{{ $estadoBadge['color'] }}; font-size:11px; font-weight:500;">
                            {{ $estadoBadge['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="d-flex gap-2">
                            <a href="{{ route('prestamos.show', $prestamo) }}"
                               style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                                Ver
                            </a>
                            <a href="{{ route('prestamos.edit', $prestamo) }}"
                               style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:4px 10px;">
                                Editar
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted" style="font-size:13px;">
                        No hay préstamos registrados aún.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($prestamos->hasPages())
    <div class="mt-3">{{ $prestamos->links() }}</div>
@endif

@endsection