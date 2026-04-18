@extends('layouts.app')

@section('title', 'Préstamos reestructurados')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Préstamos reestructurados</h5>
        <span class="text-muted" style="font-size:13px;">{{ $prestamos->total() }} en proceso de pago</span>
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

@if(!$prestamos->total())
    <div class="bg-white border rounded-3 p-5 text-center" style="border-color:#e8e8e8 !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
             style="width:56px; height:56px; background:#fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="1.5">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                <path d="M3 3v5h5"/>
            </svg>
        </div>
        <p class="fw-medium mb-1" style="color:#1a2e1a;">Sin préstamos reestructurados activos</p>
        <p class="text-muted mb-0" style="font-size:13px;">Los préstamos reestructurados aparecerán aquí.</p>
    </div>
@else
    <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:14px; min-width:640px;">
                <thead style="background:#f8f9f8; border-bottom:1px solid #e8e8e8;">
                    <tr>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Próximo pago</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prestamos as $prestamo)
                        <tr style="border-top:0.5px solid #f0f0f0;">
                            <td class="px-4 py-3 text-muted">{{ $prestamo->id }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('prestamos.show', $prestamo) }}"
                                   style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                    {{ $prestamo->cliente?->nombre_completo ?? 'Cliente eliminado' }}
                                </a>
                            </td>
                            <td class="px-4 py-3" style="color:#1f6b21; font-weight:500;">
                                ${{ number_format($prestamo->saldo_restante, 2) }}
                            </td>
                            <td class="px-4 py-3" style="color:{{ $prestamo->mora_acumulada > 0 ? '#c0392b' : '#888' }};">
                                ${{ number_format($prestamo->mora_acumulada, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match($prestamo->estado) {
                                        'activo'  => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                                        'vencido' => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Vencido'],
                                        default   => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => ucfirst($prestamo->estado)],
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-2"
                                      style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:11px; font-weight:500;">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted">
                                {{ $prestamo->fecha_proximo_pago?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('prestamos.show', $prestamo) }}"
                                   style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                                    Ver / Abonar
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('prestamos.show', $prestamo) }}"
                                    style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                                        Ver / Abonar
                                    </a>
                                    @foreach($prestamo->reestructuraciones as $r)
                                        <a href="{{ route('reestructuracion.pdf', $r) }}" target="_blank"
                                        style="font-size:12px; color:#e65100; text-decoration:none; border:0.5px solid #ffcc80; border-radius:6px; padding:4px 10px;">
                                            Carta PDF
                                        </a>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($prestamos->hasPages())
        <div class="mt-3">{{ $prestamos->links() }}</div>
    @endif
@endif

@endsection