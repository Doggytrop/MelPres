@php $inputClass = 'form-control form-control-sm'; @endphp
@php $labelStyle = 'font-size:11px; text-transform:uppercase; letter-spacing:.05em;'; @endphp

<div class="row g-3">

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Nombre completo *</label>
        <input type="text" name="name"
               value="{{ old('name', $asesor->name ?? '') }}"
               class="{{ $inputClass }} @error('name') is-invalid @enderror"
               placeholder="Ej: María González">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Correo electrónico *</label>
        <input type="email" name="email"
               value="{{ old('email', $asesor->email ?? '') }}"
               class="{{ $inputClass }} @error('email') is-invalid @enderror"
               placeholder="correo@ejemplo.com">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">
            Contraseña {{ isset($asesor) ? '(dejar vacío para no cambiar)' : '*' }}
        </label>
        <input type="password" name="password"
               class="{{ $inputClass }} @error('password') is-invalid @enderror"
               placeholder="Mínimo 8 caracteres">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Confirmar contraseña</label>
        <input type="password" name="password_confirmation"
               class="{{ $inputClass }}"
               placeholder="Repetir contraseña">
    </div>

</div>