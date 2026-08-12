<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Superadmin') | MelPres</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/css/superadmin.css', 'resources/js/superadmin.js'])
</head>
<div id="confirm-modal" class="confirm-modal-overlay" style="display:none;">
    <div class="confirm-modal-box">
        <h3 id="confirm-modal-title">Confirmar acción</h3>
        <p id="confirm-modal-message"></p>
        <div class="confirm-modal-actions">
            <button type="button" id="confirm-modal-cancel" class="sa-button">Cancelar</button>
            <button type="button" id="confirm-modal-accept" class="sa-button sa-button--danger">Confirmar</button>
        </div>
    </div>
</div>
<body>
    <header class="sa-navbar">
        <nav class="sa-navbar-inner" aria-label="Navegación Superadmin">
            <a class="sa-brand" href="{{ route('superadmin.dashboard') }}">
                <x-application-logo class="sa-brand-logo" />
                <span>Panel de control</span>
            </a>

            <button class="sa-nav-toggle" type="button"
                    aria-expanded="false"
                    aria-controls="superadmin-navigation"
                    aria-label="Abrir menú de navegación"
                    data-superadmin-nav-toggle>
                <span aria-hidden="true"></span>
            </button>

            <div class="sa-nav-menu" id="superadmin-navigation">
                <a class="sa-nav-link {{ request()->routeIs('superadmin.dashboard') ? 'is-active' : '' }}"
                   href="{{ route('superadmin.dashboard') }}"
                   @if (request()->routeIs('superadmin.dashboard')) aria-current="page" @endif>
                    Dashboard
                </a>
                <a class="sa-nav-link {{ request()->routeIs('superadmin.companies.*') ? 'is-active' : '' }}"
                   href="{{ route('superadmin.companies.index') }}"
                   @if (request()->routeIs('superadmin.companies.*')) aria-current="page" @endif>
                    Empresas
                </a>
                <a class="sa-nav-link {{ request()->routeIs('superadmin.activity-logs.*') ? 'is-active' : '' }}"
                   href="{{ route('superadmin.activity-logs.index') }}"
                   @if (request()->routeIs('superadmin.activity-logs.*')) aria-current="page" @endif>
                    Auditoría
                </a>
                <form class="sa-logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="sa-button sa-button--secondary sa-logout" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </nav>
    </header>

    <main class="sa-main">
        @if (session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">Revisa los campos marcados antes de continuar.</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
