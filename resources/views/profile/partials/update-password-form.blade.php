<form method="POST" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Contraseña actual</label>
        <input type="password" name="current_password" autocomplete="current-password"
               class="form-control form-control-sm @error('current_password', 'updatePassword') is-invalid @enderror">
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nueva contraseña</label>
        <input type="password" name="password" autocomplete="new-password"
               class="form-control form-control-sm @error('password', 'updatePassword') is-invalid @enderror">
        @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" autocomplete="new-password"
               class="form-control form-control-sm @error('password_confirmation', 'updatePassword') is-invalid @enderror">
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-sm"
                style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
            Actualizar contraseña
        </button>
        @if (session('status') === 'password-updated')
            <span class="text-muted" style="font-size:12px;">✓ Guardado</span>
        @endif
    </div>
</form>