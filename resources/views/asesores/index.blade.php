@extends('layouts.app')

@section('title', 'Asesores')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Asesores</h5>
        <span class="text-muted" style="font-size:13px;">{{ $asesores->total() }} registrados</span>
    </div>
    <a href="{{ route('asesores.create') }}"
       class="btn btn-sm"
       style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
        + Nuevo asesor
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
        <thead style="background:#f8f9f8; border-bottom:1px solid #e8e8e8;">
            <tr>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Asesor</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Correo</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Registro</th>
                <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asesores as $asesor)
                <tr style="border-top:0.5px solid #f0f0f0;">
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium"
                                 style="width:34px; height:34px; background:#e8f5e9; color:#1f6b21; font-size:13px; flex-shrink:0;">
                                {{ strtoupper(substr($asesor->name, 0, 1)) }}
                            </div>
                            <span style="color:#1a2e1a; font-weight:500;">{{ $asesor->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $asesor->email }}</td>
                    <td class="px-4 py-3 text-muted">{{ $asesor->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="d-flex gap-2">
                            <a href="{{ route('asesores.edit', $asesor) }}"
                               style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:4px 10px;">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('asesores.destroy', $asesor) }}"
                                  onsubmit="return confirm('¿Eliminar este asesor?')">
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
                    <td colspan="4" class="text-center py-5 text-muted" style="font-size:13px;">
                        No hay asesores registrados aún.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($asesores->hasPages())
    <div class="mt-3">{{ $asesores->links() }}</div>
@endif

@endsection