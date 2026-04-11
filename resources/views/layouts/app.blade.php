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
    </style>
</head>
<body>

    <div class="d-flex vh-100 overflow-hidden">

        {{-- Sidebar fijo izquierda --}}
        @include('partials.sidebar')

        {{-- Zona derecha --}}
        <div class="d-flex flex-column flex-grow-1 overflow-hidden">

            {{-- Navbar superior --}}
            @include('partials.navbar')

            {{-- Contenido de cada página --}}
            <main class="flex-grow-1 overflow-auto p-4">
                @yield('content')
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>