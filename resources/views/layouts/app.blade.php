<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'MelPres') — {{ $config_sistema['negocio_nombre'] ?? 'MelPres' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    @php
        $colorPrimario   = $config_sistema['color_primario'] ?? '#1f6b21';
        $colorSecundario = $config_sistema['color_secundario'] ?? '#e8f5e9';
    @endphp

    <style>
        :root {
            --color-primary: {{ $colorPrimario }};
            --color-secondary: {{ $colorSecundario }};
        }

        body { background: #f8f9f8; }

        .nav-link:hover {
            background: {{ $colorSecundario }} !important;
            color: {{ $colorPrimario }} !important;
        }

        .nav-link.text-white {
            background: {{ $colorPrimario }} !important;
        }

        .dropdown-item:active,
        .dropdown-item:focus {
            background-color: {{ $colorSecundario }} !important;
            color: {{ $colorPrimario }} !important;
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

        /* Botones dinámicos */
        .btn-primary-dynamic {
            background: {{ $colorPrimario }} !important;
            color: white !important;
            border: none;
        }

        .btn-primary-dynamic:hover {
            filter: brightness(1.1);
        }

        /* Links dinámicos */
        a.text-primary-dynamic {
            color: {{ $colorPrimario }} !important;
        }

        /* Badges dinámicos */
        .badge-primary-dynamic {
            background: {{ $colorSecundario }} !important;
            color: {{ $colorPrimario }} !important;
        }

        /* Progress bars */
        .progress-dynamic {
            background: {{ $colorPrimario }} !important;
        }
    </style>
</head>
<body>

    <div class="d-flex vh-100 overflow-hidden">

        {{-- Sidebar --}}
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