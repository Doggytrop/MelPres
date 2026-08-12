@extends('superadmin.layouts.app')

@section('title', 'Nueva empresa')

@section('content')

<div class="mb-4" style="margin-bottom: 24px;">
    <a href="{{ route('superadmin.companies.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a Empresas
    </a>
</div>
    <div class="page-heading">
        <div>
            <h1>Nueva empresa</h1>
            <p>Registra la empresa, su administrador inicial y la vigencia contratada.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('superadmin.companies.store') }}">
        @csrf
        <div class="panel">
            <h2>Empresa</h2>
            <div class="field">
                <label for="name">Nombre de la empresa</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <p class="muted">El administrador podrá completar la configuración de la empresa desde su panel.</p>
        </div>

        <div class="panel">
            <h2>Administrador inicial</h2>
            <div class="grid">
                <div class="field">
                    <label for="admin_name">Nombre del administrador</label>
                    <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
                    @error('admin_name') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="admin_email">Correo del administrador</label>
                    <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
                    @error('admin_email') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="admin_phone">Teléfono opcional</label>
                    <input id="admin_phone" name="admin_phone" value="{{ old('admin_phone') }}">
                    @error('admin_phone') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Suscripción</h2>
            <div class="field">
                <label for="subscription_years">Años de servicio contratados</label>
                <select id="subscription_years" name="subscription_years" required>
                    @foreach ([1, 2, 3, 5] as $years)
                        <option value="{{ $years }}" @selected((int) old('subscription_years', 1) === $years)>
                            {{ $years }} {{ $years === 1 ? 'año' : 'años' }}
                        </option>
                    @endforeach
                </select>
                @error('subscription_years') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="sa-button-group">
            <button class="sa-button sa-button--primary" type="submit">Crear empresa</button>
            <a class="sa-button sa-button--secondary" href="{{ route('superadmin.companies.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
