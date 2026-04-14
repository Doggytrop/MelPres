@extends('layouts.app')

@section('title', 'Clientes')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Clientes</h5>
        <span class="text-muted" style="font-size:13px;">{{ $clientes->total() }} registrados</span>
    </div>
    <a href="{{ route('clientes.create') }}"
       class="btn btn-sm"
       style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
        + Nuevo cliente
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
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:14px; min-width:600px;">
            <thead style="background:#f8f9f8; border-bottom: 1px solid #e8e8e8;">
                <tr>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nombre</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-md-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">DUI</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-sm-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Teléfono</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                    <tr style="border-top: 0.5px solid #f0f0f0;">
                        <td class="px-4 py-3 text-muted">{{ $cliente->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('clientes.show', $cliente) }}"
                               style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                {{ $cliente->nombre_completo }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-muted d-none d-md-table-cell">{{ $cliente->dui ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted d-none d-sm-table-cell">{{ $cliente->telefono ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($cliente->estado) {
                                    'activo'    => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                                    'inactivo'  => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => 'Inactivo'],
                                    'bloqueado' => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Bloqueado'],
                                };
                            @endphp
                            <span class="px-2 py-1 rounded-2"
                                  style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:11px; font-weight:500;">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex gap-2">
                                <a href="{{ route('clientes.edit', $cliente) }}"
                                   style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:4px 10px;">
                                    Editar
                                </a>

                                {{-- Botón eliminar --}}
                                <button type="button"
                                        style="font-size:12px; color:#c0392b; background:none; border:0.5px solid #f5c6c6; border-radius:6px; padding:4px 10px; cursor:pointer;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEliminar{{ $cliente->id }}">
                                    Eliminar
                                </button>
                            </div>

                            {{-- Modal confirmación --}}
                            <div class="modal fade" id="modalEliminar{{ $cliente->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border rounded-3" style="border-color:#e8e8e8 !important;">
                                        <div class="modal-body p-4">
                                            <h6 class="fw-medium mb-2" style="color:#1a2e1a;">¿Eliminar cliente?</h6>
                                            <p class="text-muted mb-4" style="font-size:13px;">
                                                Estás a punto de eliminar a <strong>{{ $cliente->nombre_completo }}</strong>. Esta acción no se puede deshacer.
                                            </p>
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button"
                                                        class="btn btn-sm"
                                                        style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:8px 20px;"
                                                        data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>
                                                <form method="POST" action="{{ route('clientes.destroy', $cliente) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm"
                                                            style="background:#c0392b; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                                                        Sí, eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

@if($clientes->hasPages())
    <div class="mt-3">
        {{ $clientes->links() }}
    </div>
@endif

@endsection