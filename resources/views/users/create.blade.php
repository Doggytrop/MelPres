@extends('layouts.app')

@section('title', 'Nuevo usuario')

@section('content')

<div class="mb-4">
    <a href="{{ route('users.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a usuarios
    </a>
</div>

<div class="bg-white border rounded-3 p-3 p-md-4" style="max-width:540px; border-color:#e8e8e8 !important;">

    <h6 class="fw-medium mb-4" style="color:#1a2e1a;">Nuevo usuario</h6>

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="Ej: Juan Pérez">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="correo@ejemplo.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Teléfono</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="form-control form-control-sm @error('phone') is-invalid @enderror" placeholder="6621234567">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Rol *</label>
                <div class="d-flex gap-3">
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3 flex-fill"
                           style="border:0.5px solid {{ old('role') === 'admin' ? '#1565c0' : '#ddd' }}; cursor:pointer; background:{{ old('role') === 'admin' ? '#e3f2fd' : '#fff' }};">
                        <input type="radio" name="role" value="admin" {{ old('role') === 'admin' ? 'checked' : '' }} style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Administrador</span>
                            <span style="font-size:11px; color:#888;">Acceso completo al sistema, puede gestionar asesores y configuración</span>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3 flex-fill"
                           style="border:0.5px solid {{ old('role') === 'advisor' ? '#1f6b21' : '#ddd' }}; cursor:pointer; background:{{ old('role') === 'advisor' ? '#e8f5e9' : '#fff' }};">
                        <input type="radio" name="role" value="advisor" {{ old('role', 'advisor') === 'advisor' ? 'checked' : '' }} style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Asesor</span>
                            <span style="font-size:11px; color:#888;">Solo registra pagos y consulta información de clientes</span>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3 flex-fill"
                            style="border:0.5px solid {{ old('role') === 'collector' ? '#e65100' : '#ddd' }}; cursor:pointer; background:{{ old('role') === 'collector' ? '#fff3e0' : '#fff' }};">
                    <input type="radio" name="role" value="collector" {{ old('role') === 'collector' ? 'checked' : '' }} style="margin-top:2px;">
                    <div>
                        <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Cobrador</span>
                        <span style="font-size:11px; color:#888;">Panel de cobros con mapa, registra cobros en campo</span>
                    </div>
                </label>
                </div>
                @error('role') <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Contraseña *</label>
                <input type="password" name="password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror" placeholder="Mínimo 8 caracteres">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Confirmar contraseña *</label>
                <input type="password" name="password_confirmation"
                       class="form-control form-control-sm" placeholder="Repetir contraseña">
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-sm"
                    style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                Crear usuario
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-sm"
               style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:8px 20px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection