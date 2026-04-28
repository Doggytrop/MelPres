@extends('layouts.app')

@section('title', 'clientes')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Clientes</h5>
        <span class="text-muted" style="font-size:13px;">{{ $customers->total() }} registrados</span>
    </div>
    <a href="{{ route('customers.create') }}"
        class="btn btn-sm"
        style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
        + Nuevo cliente
    </a>
</div>

@if(session('success'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
        style="background:#e8f5e9; border-color:#c8e6c9 !important; color:#1f6b21; font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 6 9 17l-5-5" />
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

<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:14px; min-width:600px;">
            <thead style="background:#f8f9f8; border-bottom: 1px solid #e8e8e8;">
                <tr>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nombre</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Documento</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-sm-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Teléfono</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr style="border-top: 0.5px solid #f0f0f0;">
                    <td class="px-4 py-3 text-muted">{{ $customer->id }}</td>
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            {{-- Avatar --}}
                            @if($customer->photo_url)
                                <img src="{{ $customer->photo_url }}" alt="Foto"
                                     class="rounded-circle"
                                     style="width:28px; height:28px; object-fit:cover; flex-shrink:0;">
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium flex-shrink-0"
                                     style="width:28px; height:28px; background:#e8f5e9; color:#1f6b21; font-size:11px;">
                                    {{ strtoupper(substr($customer->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <a href="{{ route('customers.show', $customer) }}"
                                style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                {{ $customer->first_name_complete }}
                            </a>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted">
                        {{ $customer->document_type ? strtoupper($customer->document_type) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-muted d-none d-sm-table-cell">
                        {{ $customer->phone ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $badge = match($customer->status) {
                                'active'    => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                                'inactive'  => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => 'Inactivo'],
                                'blocked' => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Bloqueado'],
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-2"
                            style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:11px; font-weight:500;">
                            {{ $badge['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="d-flex gap-2">
                            <a href="{{ route('customers.show', $customer) }}"
                                style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;"
                                title="Ver detalle">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}"
                                style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:4px 10px;">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                                onsubmit="return confirm('¿Eliminar este cliente?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    style="font-size:12px; color:#c0392b; background:none; border:0.5px solid #f5c6c6; border-radius:6px; padding:4px 10px; cursor:pointer;">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted" style="font-size:13px;">
                        No hay clientes registrados aún.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($customers->hasPages())
    <div class="mt-3">
        {{ $customers->links() }}
    </div>
@endif

@endsection
