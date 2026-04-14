<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'MelPres') — MelPres</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9f8; }
    .nav-link:hover { background: #f0f7f0 !important; color: #1f6b21 !important; }
    .dropdown-item:active,
    .dropdown-item:focus {
        background-color: #f0f7f0 !important;
        color: #1f6b21 !important;
    }

        /* Sidebar desktop */
        @media (min-width: 992px) {
            #sidebarOffcanvas {
                position: sticky;
                top: 0;
                height: 100vh;
                width: 240px;
                min-width: 240px;
                display: flex !important;
                flex-direction: column;
                visibility: visible !important;
                transform: none !important;
            }
            .offcanvas-backdrop { display: none !important; }
            #btnSidebar { display: none; }
        }
    </style>
</head>
<body>

    <div class="d-flex vh-100 overflow-hidden">

        {{-- Sidebar (offcanvas en móvil, fijo en desktop) --}}
        <div class="offcanvas offcanvas-start border-end bg-white"
             tabindex="-1"
             id="sidebarOffcanvas"
             style="width: 240px;">
            @include('partials.sidebar')
        </div>

        {{-- Zona derecha --}}
        <div class="d-flex flex-column flex-grow-1 overflow-hidden">

            {{-- Navbar superior --}}
            @include('partials.navbar')

            {{-- Contenido --}}
            <main class="flex-grow-1 overflow-auto p-3 p-md-4">
                @yield('content')
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>