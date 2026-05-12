@extends('layouts.app')

@section('title', 'Bitácora de movimientos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Bitácora de movimientos</h5>
        <span class="text-muted" style="font-size:13px;">{{ $logs->total() }} registros</span>
    </div>

    {{-- Filtros --}}
    <div class="d-flex gap-2">
        <div class="position-relative">
            <button onclick="document.getElementById('filtroLog').classList.toggle('show')"
                    class="btn btn-sm d-flex align-items-center gap-2"
                    style="background:#fff; border:0.5px solid #ddd; border-radius:8px; font-size:13px; padding:7px 14px; color:#555;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                </svg>
                Filtrar
                @if(request('module') || request('action') || request('user'))
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                          style="width:18px; height:18px; background:var(--color-primary); color:white; font-size:10px;">!</span>
                @endif
            </button>

            <div id="filtroLog" class="position-absolute bg-white rounded-3 shadow-sm mt-1 end-0"
                 style="display:none; min-width:220px; border:0.5px solid #e8e8e8; z-index:100;">
                <form method="GET" action="{{ route('activity-logs.index') }}" class="p-3">
                    <div class="mb-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase;">Módulo</label>
                        <select name="module" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            @foreach($modules as $m)
                                <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase;">Acción</label>
                        <select name="action" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="create" {{ request('action') === 'create' ? 'selected' : '' }}>Creación</option>
                            <option value="update" {{ request('action') === 'update' ? 'selected' : '' }}>Edición</option>
                            <option value="delete" {{ request('action') === 'delete' ? 'selected' : '' }}>Eliminación</option>
                            <option value="payment" {{ request('action') === 'payment' ? 'selected' : '' }}>Pago</option>
                            <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                            <option value="restructure" {{ request('action') === 'restructure' ? 'selected' : '' }}>Reestructuración</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase;">Usuario</label>
                        <select name="user" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm flex-fill"
                                style="background:var(--color-primary); color:white; border-radius:6px; font-size:12px;">
                            Aplicar
                        </button>
                        <a href="{{ route('activity-logs.index') }}" class="btn btn-sm flex-fill"
                           style="background:#f5f5f5; color:#555; border-radius:6px; font-size:12px; text-decoration:none;">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    @forelse($logs as $log)
        @php
            $actionBadge = $log->action_badge;
            $moduleBadge = $log->module_badge;
        @endphp
        <div class="px-4 py-3 border-bottom d-flex align-items-start gap-3" style="border-color:#f8f8f8 !important;">
            {{-- Ícono --}}
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:32px; height:32px; background:{{ $actionBadge['bg'] }};">
                @if($log->action === 'create')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $actionBadge['color'] }}" stroke-width="1.5"><path d="M12 5v14M5 12h14"/></svg>
                @elseif($log->action === 'update')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $actionBadge['color'] }}" stroke-width="1.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                @elseif($log->action === 'delete')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $actionBadge['color'] }}" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                @elseif($log->action === 'payment')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $actionBadge['color'] }}" stroke-width="1.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                @elseif($log->action === 'login')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $actionBadge['color'] }}" stroke-width="1.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                @else
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $actionBadge['color'] }}" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                @endif
            </div>

            {{-- Contenido --}}
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-medium" style="font-size:13px; color:#1a2e1a;">{{ $log->user_name }}</span>
                    <span class="px-2 py-0 rounded-2" style="background:{{ $actionBadge['bg'] }}; color:{{ $actionBadge['color'] }}; font-size:10px; font-weight:500;">
                        {{ $actionBadge['label'] }}
                    </span>
                    <span class="px-2 py-0 rounded-2" style="background:{{ $moduleBadge['bg'] }}; color:{{ $moduleBadge['color'] }}; font-size:10px; font-weight:500;">
                        {{ $moduleBadge['label'] }}
                    </span>
                </div>
                <p class="mb-0" style="font-size:13px; color:#555;">{{ $log->description }}</p>
            </div>

            {{-- Fecha --}}
            <div class="text-end flex-shrink-0">
                <span class="d-block text-muted" style="font-size:11px;">{{ $log->created_at->format('d/m/Y') }}</span>
                <span class="d-block text-muted" style="font-size:11px;">{{ $log->created_at->format('H:i') }}</span>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted" style="font-size:13px;">
            No hay movimientos registrados aún.
        </div>
    @endforelse
</div>

@if($logs->hasPages())
    <div class="mt-3 activity-log-pagination">{{ $logs->appends(request()->query())->links() }}</div>
@endif

<style>
    #filtroLog.show { display: block !important; }

    .activity-log-pagination nav {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .activity-log-pagination svg {
        width: 16px;
        height: 16px;
    }

    .activity-log-pagination p {
        margin-bottom: 0;
    }
</style>
<script>
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.position-relative')) {
            document.getElementById('filtroLog')?.classList.remove('show');
        }
    });
</script>

@endsection
