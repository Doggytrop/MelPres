@php
    $cp = $config_sistema['color_primario'] ?? 'var(--color-primary)';
    $cs = $config_sistema['color_secundario'] ?? 'var(--color-secondary)';
@endphp

<div class="d-flex flex-column h-100 p-0 overflow-hidden sidebar-shell">

    {{-- Logo --}}
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 px-4 py-4 border-bottom text-decoration-none flex-shrink-0">
        @if($config_sistema['negocio_logo'] ?? null)
            <img src="{{ asset('storage/' . $config_sistema['negocio_logo']) }}" alt="Logo"
                 style="height:34px; max-width:120px; object-fit:contain;">
        @else
            <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:34px; height:34px; background:{{ $cp }};">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.5"/>
                    <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"
                          stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
        @endif
        <span class="fw-medium" style="color:#1a2e1a; font-size:15px;">
            {{ $config_sistema['negocio_nombre'] ?? 'MelPres' }}
        </span>
    </a>

    {{-- Nav --}}
    <ul class="nav flex-column px-3 py-3 gap-1 flex-grow-1 sidebar-nav">

        @php
            $menuItems = [
                ['route' => 'dashboard',       'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>', 'label' => 'Inicio',       'match' => 'dashboard'],
                ['route' => 'customers.index', 'icon' => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>',                                                                                                                  'label' => 'Clientes',     'match' => 'customers.*'],
                ['route' => 'loans.index',     'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',                                                                                                                                'label' => 'Préstamos',    'match' => 'loans.*'],
                ['route' => 'history.index',   'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',                                                                                                                                               'label' => 'Historial',    'match' => 'history.*'],
                ['route' => 'simulator.index', 'icon' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',                                                                                                                        'label' => 'Simulador',    'match' => 'simulator.*'],
            ];
        @endphp

        @foreach($menuItems as $item)
            @php $isActive = request()->routeIs($item['match']); @endphp
            <li class="nav-item">
                <a href="{{ route($item['route']) }}"
                   class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
                   style="{{ $isActive
                       ? 'background:' . $cp . '; color:white;'
                       : 'color:#6b7280;'
                   }} font-size:14px; transition:all .15s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        {!! $item['icon'] !!}
                    </svg>
                    {{ $item['label'] }}
                    @if($isActive)
                        <div class="ms-auto rounded-circle" style="width:6px; height:6px; background:rgba(255,255,255,0.5);"></div>
                    @endif
                </a>
            </li>
        @endforeach

        {{-- Reestructuración con submenú --}}
        @php $reestActive = request()->routeIs('restructuring.*'); @endphp
        <li class="nav-item">
            <div class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
                 style="cursor:pointer; font-size:14px; color:{{ $reestActive ? $cp : '#6b7280' }}; transition:all .15s;"
                 onclick="toggleSubmenu('submenu_reest')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                </svg>
                <span class="sidebar-label">Reestructuración</span>
                <svg id="arrow_reest" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     class="ms-auto" style="transition:transform .2s;">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </div>

            <ul class="nav flex-column ps-4 gap-1 mt-1 sidebar-submenu" id="submenu_reest"
                style="{{ $reestActive ? '' : 'display:none;' }}">
                @php
                    $subItems = [
                        ['route' => 'restructuring.overdue', 'label' => 'Vencidos',   'match' => ['restructuring.overdue', 'restructuring.create']],
                        ['route' => 'restructuring.active',  'label' => 'Activos',    'match' => ['restructuring.active']],
                        ['route' => 'restructuring.history', 'label' => 'Historial',  'match' => ['restructuring.history']],
                    ];
                @endphp
                @foreach($subItems as $sub)
                    @php $subActive = request()->routeIs($sub['match']); @endphp
                    <li class="nav-item">
                        <a href="{{ route($sub['route']) }}"
                           class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
                           style="{{ $subActive
                               ? 'background:' . $cp . '; color:white;'
                               : 'color:#6b7280;'
                           }} font-size:13px; transition:all .15s;">
                            <div class="rounded-circle" style="width:5px; height:5px; background:{{ $subActive ? 'white' : '#ccc' }};"></div>
                            <span class="sidebar-label">{{ $sub['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>

        {{-- Separador --}}
        <li class="my-2" style="border-top:0.5px solid #f0f0f0;"></li>

        {{-- Corte de caja --}}
        @php $cajaActive = request()->routeIs('cash-register.*'); @endphp
        <li class="nav-item">
            <a href="{{ route('cash-register.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
               style="{{ $cajaActive
                   ? 'background:' . $cp . '; color:white;'
                   : 'color:#6b7280;'
               }} font-size:14px; transition:all .15s;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Corte de caja
            </a>
        </li>

        {{-- Asesores --}}
        @php $advActive = request()->routeIs('advisors.*'); @endphp
        <li class="nav-item">
            <a href="{{ route('advisors.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
               style="{{ $advActive
                   ? 'background:' . $cp . '; color:white;'
                   : 'color:#6b7280;'
               }} font-size:14px; transition:all .15s;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    <path d="M21 21v-2a4 4 0 0 0-3-3.85"/>
                </svg>
                Asesores
            </a>
        </li>

        {{-- Configuración --}}
        @php $settActive = request()->routeIs('settings.*'); @endphp
        <li class="nav-item">
            <a href="{{ route('settings.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
               style="{{ $settActive
                   ? 'background:' . $cp . '; color:white;'
                   : 'color:#6b7280;'
               }} font-size:14px; transition:all .15s;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>
                Configuración
            </a>
        </li>
        {{-- Usuarios --}}
        @php $usersActive = request()->routeIs('users.*'); @endphp
        <li class="nav-item">
            <a href="{{ route('users.index') }}"
            class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
            style="{{ $usersActive
                ? 'background:' . $cp . '; color:white;'
                : 'color:#6b7280;'
            }} font-size:14px; transition:all .15s;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Usuarios
                @if($usersActive)
                    <div class="ms-auto rounded-circle" style="width:6px; height:6px; background:rgba(255,255,255,0.5);"></div>
                @endif
            </a>
    </li>
    {{-- Bitácora --}}
    @php $logsActive = request()->routeIs('activity-logs.*'); @endphp
    <li class="nav-item">
        <a href="{{ route('activity-logs.index') }}"
        class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2"
        style="{{ $logsActive
            ? 'background:' . $cp . '; color:white;'
            : 'color:#6b7280;'
        }} font-size:14px; transition:all .15s;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="sidebar-label">Bitácora</span>
            @if($logsActive)
                <div class="ms-auto rounded-circle" style="width:6px; height:6px; background:rgba(255,255,255,0.5);"></div>
            @endif
        </a>
    </li>

    </ul>

    {{-- Footer --}}
    <div class="px-4 py-3 border-top flex-shrink-0">
        @auth
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium"
                     style="width:32px; height:32px; background:{{ $cs }}; color:{{ $cp }}; font-size:12px; flex-shrink:0;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <p class="mb-0 fw-medium text-truncate" style="font-size:12px; color:#1a2e1a;">{{ auth()->user()->name }}</p>
                    <p class="mb-0" style="font-size:10px; color:{{ $cp }};">
                        @if(auth()->user()->isSuperAdmin())
                            Super Admin
                        @elseif(auth()->user()->isAdmin())
                            Administrador
                        @else
                            Asesor
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Cerrar sesión"
                            style="background:none; border:none; color:#aaa; cursor:pointer; padding:4px; transition:color .15s;"
                            onmouseover="this.style.color='#c0392b'" onmouseout="this.style.color='#aaa'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        @endauth
    </div>

</div>

<style>
    #sidebarOffcanvas {
        height: 100dvh;
        overflow-x: hidden;
    }

    .sidebar-shell {
        height: 100dvh;
        max-height: 100dvh;
    }

    .sidebar-nav {
        display: block !important;
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        min-width: 0;
        width: 100%;
        max-width: 100%;
        min-height: 0;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
    }

    .sidebar-nav,
    .sidebar-nav ul {
        box-sizing: border-box;
    }

    .sidebar-nav .nav-item,
    .sidebar-nav .nav-link,
    .sidebar-submenu {
        min-width: 0;
        max-width: 100%;
    }

    .sidebar-nav > .nav-item {
        display: block;
        width: 100%;
    }

    .sidebar-nav .nav-link {
        box-sizing: border-box;
        display: flex;
        width: 100%;
        overflow: hidden;
        white-space: nowrap;
    }

    .sidebar-nav .nav-link > svg,
    .sidebar-nav .nav-link > .rounded-circle {
        flex: 0 0 auto;
    }

    .sidebar-label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sidebar-submenu {
        overflow-x: hidden;
        padding-left: 1rem !important;
        width: 100%;
    }

    .sidebar-submenu .nav-link {
        padding-left: .75rem !important;
        padding-right: .75rem !important;
    }

    .nav-link:hover:not(.text-white) {
        background: {{ $cs }} !important;
        color: {{ $cp }} !important;
    }
</style>

<script>
function toggleSubmenu(id) {
    const menu  = document.getElementById(id);
    const arrow = document.getElementById('arrow_' + id.replace('submenu_', ''));
    const visible = menu.style.display !== 'none';
    menu.style.display  = visible ? 'none' : 'block';
    arrow.style.transform = visible ? 'rotate(0deg)' : 'rotate(180deg)';
}

@if(request()->routeIs('restructuring.*'))
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('arrow_reest').style.transform = 'rotate(180deg)';
    });
@endif
</script>
