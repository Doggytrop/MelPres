<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuenta suspendida | MelPres</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f4f6f5;
            color: #18221c;
            font-family: Arial, Helvetica, sans-serif;
        }
        main {
            width: min(100%, 560px);
            padding: 40px;
            border: 1px solid #dfe5e1;
            border-top: 4px solid #b42318;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 32px rgba(24, 34, 28, .08);
        }
        .brand-logo {
            width: 64px;
            height: 64px;
            display: block;
            margin: 0 0 28px;
            border-radius: 10px;
            object-fit: contain;
        }
        h1 {
            margin: 0 0 16px;
            font-size: 30px;
            line-height: 1.2;
        }
        p {
            margin: 0 0 12px;
            color: #526057;
            font-size: 16px;
            line-height: 1.6;
        }
        form { margin-top: 28px; }
        button {
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 6px;
            background: #1f6b21;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            font-weight: 700;
        }
        button:hover { background: #185619; }
        @media (max-width: 520px) {
            main { padding: 28px 22px; }
            h1 { font-size: 26px; }
        }
    </style>
</head>
<body>
    <main>
        <x-application-logo class="brand-logo" />
        <h1>Cuenta suspendida</h1>
        <p>El acceso de tu empresa está temporalmente suspendido.</p>
        <p>Tus datos permanecen seguros. Contacta a soporte de MelPres para revisar el estado de la cuenta.</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Salir</button>
        </form>
    </main>
</body>
</html>
