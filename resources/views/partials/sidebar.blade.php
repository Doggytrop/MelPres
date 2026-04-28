<div class="d-flex flex-column h-100 p-0">

    {{-- Logo --}}
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 px-4 py-4 border-bottom text-decoration-none">
        <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:34px; height:34px; background:#5fcf61;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.5"/>
                <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"
                      stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <span class="fw-medium" style="color:#1a2e1a; font-size:15px;">
            {{ $config_sistema['negocio_nombre'] ?? 'MelPres' }}
        </span>
    </a>

    {{-- Nav --}}
    <ul class="nav flex-column px-3 py-3 gap-1 overflow-auto flex-grow-1">

        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                      {{ request()->routeIs('dashboard') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('dashboard') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Inicio
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('customers.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                      {{ request()->routeIs('customers.*') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('customers.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                </svg>
                Clientes
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('loans.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                      {{ request()->routeIs('loans.*') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('loans.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                </svg>
                Préstamos
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('history.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                      {{ request()->routeIs('history.index') || request()->routeIs('history.show') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('history.index') || request()->routeIs('history.show') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 3"/>
                </svg>
                Historial
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('simulator.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                      {{ request()->routeIs('simulator.*') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('simulator.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8M12 17v4"/>
                </svg>
                Simulador
            </a>
        </li>

        {{-- Restructuring submenu --}}
        <li class="nav-item">
            <div class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 text-secondary"
                 style="cursor:pointer; font-size:14px;"
                 onclick="toggleSubmenu('submenu_reest')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                </svg>
                Reestructuración
                <svg id="arrow_reest" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     class="ms-auto" style="transition:.2s;">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </div>

            <ul class="nav flex-column ps-4 gap-1" id="submenu_reest"
                style="{{ request()->routeIs('restructuring.*') ? '' : 'display:none;' }}">
                <li class="nav-item">
                    <a href="{{ route('restructuring.overdue') }}"
                       class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                              {{ request()->routeIs('restructuring.overdue') || request()->routeIs('restructuring.create') ? 'text-white' : 'text-secondary' }}"
                       style="{{ request()->routeIs('restructuring.overdue') || request()->routeIs('restructuring.create') ? 'background:#1f6b21;' : '' }} font-size:13px;">
                        Vencidos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('restructuring.active') }}"
                       class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                              {{ request()->routeIs('restructuring.active') ? 'text-white' : 'text-secondary' }}"
                       style="{{ request()->routeIs('restructuring.active') ? 'background:#1f6b21;' : '' }} font-size:13px;">
                        Activos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('restructuring.history') }}"
                       class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                              {{ request()->routeIs('restructuring.history') ? 'text-white' : 'text-secondary' }}"
                       style="{{ request()->routeIs('restructuring.history') ? 'background:#1f6b21;' : '' }} font-size:13px;">
                        Historial
                    </a>
                </li>
            </ul>
        </li>

        {{-- Cash Register --}}
        @if(\App\Models\Setting::get('modulo_corte_caja'))
            <li class="nav-item">
                <a href="{{ route('cash-register.index') }}"
                   class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                          {{ request()->routeIs('cash-register.*') ? 'text-white' : 'text-secondary' }}"
                   style="{{ request()->routeIs('cash-register.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Corte de caja
                </a>
            </li>
        @endif

        {{-- Admin only --}}
        @if(auth()->user()->isAdmin())

            @if($config_sistema['modulo_asesores'] ?? false)
                <li class="nav-item">
                    <a href="{{ route('advisors.index') }}"
                       class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                              {{ request()->routeIs('advisors.*') ? 'text-white' : 'text-secondary' }}"
                       style="{{ request()->routeIs('advisors.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            <path d="M21 21v-2a4 4 0 0 0-3-3.85"/>
                        </svg>
                        Asesores
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a href="{{ route('settings.index') }}"
                   class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2
                          {{ request()->routeIs('settings.*') ? 'text-white' : 'text-secondary' }}"
                   style="{{ request()->routeIs('settings.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                    Configuración
                </a>
            </li>

        @endif

    </ul>

    {{-- Footer --}}
    <div class="px-4 py-4 border-top">
        @auth
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium"
                     style="width:28px; height:28px; background:#e8f5e9; color:#1f6b21; font-size:11px; flex-shrink:0;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <p class="mb-0 fw-medium" style="font-size:12px; color:#1a2e1a;">{{ auth()->user()->name }}</p>
                    <p class="mb-0 text-muted" style="font-size:10px;">
                        {{ auth()->user()->isAdmin() ? 'Administrador' : 'Asesor' }}
                    </p>
                </div>
            </div>
        @endauth
    </div>

</div>

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