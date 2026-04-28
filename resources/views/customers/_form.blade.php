@php $inputClass = 'form-control form-control-sm'; @endphp
@php $labelStyle = 'font-size:11px; text-transform:uppercase; letter-spacing:.05em;'; @endphp

<div class="row g-3">

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Nombre *</label>
        <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name ?? '') }}"
               class="{{ $inputClass }} @error('first_name') is-invalid @enderror" placeholder="Ej: Juan">
        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Apellido *</label>
        <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name ?? '') }}"
               class="{{ $inputClass }} @error('last_name') is-invalid @enderror" placeholder="Ej: García">
        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Tipo de documento</label>
        <select name="document_type" class="{{ $inputClass }}">
            <option value="">Sin documento</option>
            @foreach([
                'ine'      => 'INE',
                'passport' => 'Pasaporte',
                'license'  => 'Licencia de conducir',
                'id_card'  => 'Tarjeta de identidad',
                'other'    => 'Otro',
            ] as $value => $text)
                <option value="{{ $value }}"
                    {{ old('document_type', $customer->document_type ?? '') == $value ? 'selected' : '' }}>
                    {{ $text }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Número de documento</label>
        <input type="text" name="document_number"
               value="{{ old('document_number', $customer->document_number ?? '') }}"
               class="{{ $inputClass }}" placeholder="Ej: GOMJ850101HDFXXX">
        <small class="text-muted" style="font-size:11px;">Opcional</small>
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Teléfono</label>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
               class="{{ $inputClass }}" placeholder="Ej: 7777-1234">
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Dirección</label>
        <textarea name="address" rows="2"
                  class="{{ $inputClass }}">{{ old('address', $customer->address ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Referencias</label>
        <textarea name="references" rows="2"
                  class="{{ $inputClass }}" placeholder="Nombre y contacto de referencias">{{ old('references', $customer->references ?? '') }}</textarea>
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Estado *</label>
        <select name="status" class="{{ $inputClass }} @error('status') is-invalid @enderror">
            @foreach(['active' => 'Activo', 'inactive' => 'Inactivo', 'blocked' => 'Bloqueado'] as $value => $text)
                <option value="{{ $value }}" {{ old('status', $customer->status ?? 'active') === $value ? 'selected' : '' }}>
                    {{ $text }}
                </option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Notas internas</label>
        <textarea name="notes" rows="2"
                  class="{{ $inputClass }}" placeholder="Condiciones especiales, observaciones...">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>

</div>
