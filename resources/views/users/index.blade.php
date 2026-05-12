@extends('layouts.app')

@section('title', 'Gestión de usuarios')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Gestión de usuarios</h5>
        <span class="text-muted" style="font-size:13px;">{{ $users->total() }} usuarios del sistema</span>
    </div>
    <a href="{{ route('users.create') }}"
       class="btn btn-sm"
       style="background:var(--color-primary); color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
        + Nuevo usuario
    </a>
</div>

@if(session('success'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:var(--color-secondary); border-color:var(--color-secondary) !important; color:var(--color-primary); font-size:13px;">
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

<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:14px;">
            <thead style="background:#f8f9f8; border-bottom:1px solid #e8e8e8;">
                <tr>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Usuario</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Correo</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-md-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Teléfono</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Rol</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-sm-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Registro</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $roleBadge = match($user->role) {
                            'superadmin' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Super Admin'],
                            'admin'      => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Admin'],
                            'advisor'    => ['bg' => 'var(--color-secondary)', 'color' => 'var(--color-primary)', 'label' => 'Asesor'],
                            'collector'  => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Cobrador'],
                            default      => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => $user->role],
                        };
                    @endphp
                    <tr style="border-top:0.5px solid #f0f0f0;">
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium"
                                     style="width:34px; height:34px; background:{{ $roleBadge['bg'] }}; color:{{ $roleBadge['color'] }}; font-size:13px; flex-shrink:0;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <span style="color:#1a2e1a; font-weight:500;">{{ $user->name }}</span>
                                    @if($user->id === auth()->id())
                                        <span class="ms-1 px-2 py-0 rounded-pill" style="background:var(--color-secondary); color:var(--color-primary); font-size:10px;">Tú</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted" style="font-size:13px;">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-muted d-none d-md-table-cell" style="font-size:13px;">{{ $user->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-2"
                                  style="background:{{ $roleBadge['bg'] }}; color:{{ $roleBadge['color'] }}; font-size:11px; font-weight:500;">
                                {{ $roleBadge['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted d-none d-sm-table-cell" style="font-size:13px;">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            @if(!$user->isSuperAdmin())
                                <div class="d-flex gap-2">
                                    <a href="{{ route('users.edit', $user) }}"
                                       style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:4px 10px;">
                                        Editar
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              onsubmit="return confirm('¿Eliminar este usuario?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    style="font-size:12px; color:#c0392b; background:none; border:0.5px solid #f5c6c6; border-radius:6px; padding:4px 10px; cursor:pointer;">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted" style="font-size:12px;">Protegido</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted" style="font-size:13px;">
                            No hay usuarios registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($users->hasPages())
    <div class="mt-3">{{ $users->links() }}</div>
@endif

@endsection
