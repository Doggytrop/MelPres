<nav class="d-flex align-items-center justify-content-between px-3 px-md-4 border-bottom bg-white"
     style="height: 60px; min-height: 60px;">

    {{-- Botón hamburguesa (solo móvil) + Título --}}
    <div class="d-flex align-items-center gap-2">

        {{-- Botón hamburguesa --}}
        <button id="btnSidebar"
                class="btn btn-sm d-lg-none p-1"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebarOffcanvas"
                style="border: 0.5px solid #e0e0e0; border-radius: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        {{-- Título --}}
        <span class="fw-medium" style="color:#1a2e1a; font-size:15px;">
            @yield('title', 'Dashboard')
        </span>
    </div>

    {{-- Usuario + Logout --}}
<div class="dropdown">

    <button class="btn p-0 d-flex align-items-center gap-2"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            style="background:none; border:none;">

        {{-- Avatar --}}
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium flex-shrink-0"
             style="width:32px; height:32px; background:#e8f5e9; color:#1f6b21; font-size:13px; cursor:pointer;">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>

        {{-- Nombre (oculto en móvil) --}}
        <span class="d-none d-sm-inline" style="font-size:14px; color:#444;">
            {{ auth()->user()->name }}
        </span>

        {{-- Chevron --}}
        <svg class="d-none d-sm-inline" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    {{-- Menú desplegable --}}
    <ul class="dropdown-menu dropdown-menu-end mt-2 rounded-3 border py-1"
        style="border-color:#e8e8e8 !important; min-width:180px; box-shadow:0 4px 16px rgba(0,0,0,.06);">

        {{-- Info usuario --}}
        <li class="px-3 py-2 border-bottom" style="border-color:#f0f0f0 !important;">
            <p class="fw-medium mb-0" style="font-size:13px; color:#1a2e1a;">{{ auth()->user()->name }}</p>
            <p class="mb-0 text-muted" style="font-size:11px;">{{ auth()->user()->email }}</p>
        </li>

        {{-- Perfil --}}
        <li>
            <a href="{{ route('profile.edit') }}"
               class="dropdown-item d-flex align-items-center gap-2 py-2"
               style="font-size:13px; color:#444;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                Mi perfil
            </a>
        </li>

        <li><hr class="dropdown-divider" style="border-color:#f0f0f0;"></li>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="btn btn-sm d-flex align-items-center gap-1"
                    style="font-size:13px; color:#888; border:0.5px solid #e0e0e0; border-radius:8px; padding:5px 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="d-none d-sm-inline">Salir</span>
            </button>
        </form>
    </div>
</nav>