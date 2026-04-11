<nav class="d-flex align-items-center justify-content-between px-4 border-bottom bg-white"
     style="height: 60px; min-height: 60px;">

    {{-- Título de página --}}
    <span class="fw-medium" style="color:#1a2e1a; font-size:15px;">
        @yield('title', 'Dashboard')
    </span>

    {{-- Usuario + Logout --}}
    <div class="d-flex align-items-center gap-3">

        {{-- Avatar + Nombre --}}
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium"
                 style="width:32px; height:32px; background:#e8f5e9; color:#1f6b21; font-size:13px;">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <span style="font-size:14px; color:#444;">{{ auth()->user()->name }}</span>
        </div>

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
                Salir
            </button>
        </form>
    </div>
</nav>