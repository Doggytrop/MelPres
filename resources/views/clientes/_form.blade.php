@php $inputClass = 'form-control form-control-sm'; @endphp
@php $labelStyle = 'font-size:11px; text-transform:uppercase; letter-spacing:.05em;'; @endphp

<div class="row g-3">

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Nombre *</label>
        <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre ?? '') }}"
               class="{{ $inputClass }} @error('nombre') is-invalid @enderror" placeholder="Ej: Juan">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Apellido *</label>
        <input type="text" name="apellido" value="{{ old('apellido', $cliente->apellido ?? '') }}"
               class="{{ $inputClass }} @error('apellido') is-invalid @enderror" placeholder="Ej: García">
        @error('apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
    <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Tipo de documento</label>
    <select name="documento_tipo" class="{{ $inputClass }}">
        <option value="">Sin documento</option>
        @foreach([
            'ine'       => 'INE',
            'pasaporte' => 'Pasaporte',
            'cedula'    => 'Cédula',
            'licencia'  => 'Licencia de conducir',
            'otro'      => 'Otro',
        ] as $value => $text)
            <option value="{{ $value }}"
                {{ old('documento_tipo', $cliente->documento_tipo ?? '') == $value ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>
</div>

    <div class="col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Número de documento</label>
        <input type="text" name="documento_numero"
            value="{{ old('documento_numero', $cliente->documento_numero ?? '') }}"
            class="{{ $inputClass }}" placeholder="Ej: GOMJ850101HDFXXX">
        <small class="text-muted" style="font-size:11px;">Opcional</small>
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}"
               class="{{ $inputClass }}" placeholder="Ej: 7777-1234">
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Dirección</label>
        <textarea name="direccion" rows="2"
                  class="{{ $inputClass }}">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Referencias personales</label>
        <textarea name="referencias" rows="2"
                  class="{{ $inputClass }}" placeholder="Nombre y contacto de referencias">{{ old('referencias', $cliente->referencias ?? '') }}</textarea>
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Estado *</label>
        <select name="estado" class="{{ $inputClass }} @error('estado') is-invalid @enderror">
            @foreach(['activo' => 'Activo', 'inactivo' => 'Inactivo', 'bloqueado' => 'Bloqueado'] as $value => $text)
                <option value="{{ $value }}" {{ old('estado', $cliente->estado ?? 'activo') === $value ? 'selected' : '' }}>
                    {{ $text }}
                </option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Notas internas</label>
        <textarea name="notas" rows="2"
                  class="{{ $inputClass }}" placeholder="Condiciones especiales, observaciones...">{{ old('notas', $cliente->notas ?? '') }}</textarea>
    </div>

</div>