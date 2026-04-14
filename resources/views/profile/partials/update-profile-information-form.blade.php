<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}"
               required autofocus autocomplete="name"
               class="form-control form-control-sm @error('name') is-invalid @enderror">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Correo electrónico</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}"
               required autocomplete="username"
               class="form-control form-control-sm @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <p class="text-muted" style="font-size:12px;">
                    Tu correo no está verificado.
                    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0" style="font-size:12px; color:#1f6b21;">
                            Reenviar verificación
                        </button>
                    </form>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="text-success" style="font-size:12px;">Enlace de verificación enviado.</p>
                @endif
            </div>
        @endif
    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-sm"
                style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
            Guardar cambios
        </button>
        @if (session('status') === 'profile-updated')
            <span class="text-muted" style="font-size:12px;">✓ Guardado</span>
        @endif
    </div>
</form>