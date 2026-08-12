<form method="POST" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Contraseña actual</label>
        <div class="position-relative">
            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                   class="form-control form-control-sm @error('current_password', 'updatePassword') is-invalid @enderror" style="padding-right:36px;">
            <button type="button" onclick="togglePassword('current_password', this)"
                    style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ca3af; padding:0; display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nueva contraseña</label>
        <div class="position-relative">
            <input type="password" id="new_password" name="password" autocomplete="new-password"
                   class="form-control form-control-sm @error('password', 'updatePassword') is-invalid @enderror" style="padding-right:36px;">
            <button type="button" onclick="togglePassword('new_password', this)"
                    style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ca3af; padding:0; display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
        @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Confirmar contraseña</label>
        <div class="position-relative">
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                   class="form-control form-control-sm @error('password_confirmation', 'updatePassword') is-invalid @enderror" style="padding-right:36px;">
            <button type="button" onclick="togglePassword('password_confirmation', this)"
                    style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ca3af; padding:0; display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-sm"
                style="background:var(--color-primary); color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
            Actualizar contraseña
        </button>
        @if (session('status') === 'password-updated')
            <span class="text-muted" style="font-size:12px;">✓ Guardado</span>
        @endif
    </div>
</form>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.style.color = input.type === 'text' ? 'var(--color-primary)' : '#9ca3af';
}
</script>