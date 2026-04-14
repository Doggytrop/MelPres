<div class="d-flex flex-column h-100 p-0">

    {{-- Botón cerrar (solo visible en móvil) --}}
    <div class="d-flex d-lg-none justify-content-end px-3 pt-3">
        <button type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Cerrar"></button>
    </div>

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
    <span class="fw-medium" style="color:#1a2e1a; font-size:15px;">MelPres</span>
    </a>

    {{-- Nav --}}
    <ul class="nav flex-column px-3 py-3 gap-1">

        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('dashboard') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('clientes.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('clientes.*') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('clientes.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    <path d="M21 21v-2a4 4 0 0 0-3-3.85"/>
                </svg>
                Clientes
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('prestamos.index') }}"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('prestamos.*') ? 'text-white' : 'text-secondary' }}"
               style="{{ request()->routeIs('prestamos.*') ? 'background:#1f6b21;' : '' }} font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                </svg>
                Préstamos
            </a>
        </li>

        <li class="nav-item">
            <a href="#"
               class="nav-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 text-secondary"
               style="font-size:14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"/>
                </svg>
                Pagos
            </a>
        </li>

    </ul>
    

    {{-- Footer --}}
    <div class="mt-auto px-4 py-4 border-top">
        <span class="text-muted" style="font-size:11px;">MelPres v1.0</span>
    </div>
</div>