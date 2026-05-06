@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')

<div class="mb-4">
    <a href="{{ route('users.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a usuarios
    </a>
</div>

<div class="bg-white border rounded-3 p-3 p-md-4" style="max-width:540px; border-color:#e8e8e8 !important;">

    <h6 class="fw-medium mb-4" style="color:#1a2e1a;">Editar usuario — {{ $user->name }}</h6>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')

        <div class="row g-3">
            <div class="col-12">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="form-control form-control-sm @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="form-control form-control-sm @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Teléfono</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="form-control form-control-sm @error('phone') is-invalid @enderror">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Rol *</label>
                <div class="d-flex gap-3">
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3 flex-fill"
                           style="border:0.5px solid {{ old('role', $user->role) === 'admin' ? '#1565c0' : '#ddd' }}; cursor:pointer; background:{{ old('role', $user->role) === 'admin' ? '#e3f2fd' : '#fff' }};">
                        <input type="radio" name="role" value="admin" {{ old('role', $user->role) === 'admin' ? 'checked' : '' }} style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Administrador</span>
                            <span style="font-size:11px; color:#888;">Acceso completo al sistema</span>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3 flex-fill"
                           style="border:0.5px solid {{ old('role', $user->role) === 'advisor' ? '#1f6b21' : '#ddd' }}; cursor:pointer; background:{{ old('role', $user->role) === 'advisor' ? '#e8f5e9' : '#fff' }};">
                        <input type="radio" name="role" value="advisor" {{ old('role', $user->role) === 'advisor' ? 'checked' : '' }} style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Asesor</span>
                            <span style="font-size:11px; color:#888;">Solo registra pagos y consulta</span>
                        </div>
                    </label>
                </div>
                @error('role') <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nueva contraseña</label>
                <input type="password" name="password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror" placeholder="Dejar vacío para mantener">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Confirmar contraseña</label>
                <input type="password" name="password_confirmation"
                       class="form-control form-control-sm" placeholder="Repetir nueva contraseña">
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-sm"
                    style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                Actualizar usuario
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-sm"
               style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:8px 20px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection