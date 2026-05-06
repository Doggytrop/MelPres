@php
    $inputClass = 'form-control form-control-sm';
    $labelStyle = 'font-size:11px; text-transform:uppercase; letter-spacing:.05em;';
    $isEdit = isset($customer) && $customer->exists;
@endphp

<div class="row g-3">

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Nombre *</label>
        <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name ?? '') }}"
               class="{{ $inputClass }} @error('first_name') is-invalid @enderror" placeholder="Ej: Juan"
               {{ $isEdit ? 'readonly style=background:#f0f0f0;cursor:not-allowed;' : '' }}>
        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if($isEdit) <small class="text-muted" style="font-size:11px;">No se puede modificar</small> @endif
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Apellido *</label>
        <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name ?? '') }}"
               class="{{ $inputClass }} @error('last_name') is-invalid @enderror" placeholder="Ej: García"
               {{ $isEdit ? 'readonly style=background:#f0f0f0;cursor:not-allowed;' : '' }}>
        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if($isEdit) <small class="text-muted" style="font-size:11px;">No se puede modificar</small> @endif
    </div>

    <div class="col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Tipo de documento</label>
        <select name="document_type" class="{{ $inputClass }}" {{ $isEdit ? 'disabled' : '' }}
                {{ $isEdit ? 'style=background:#f0f0f0;cursor:not-allowed;' : '' }}>
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
        @if($isEdit)
            <input type="hidden" name="document_type" value="{{ $customer->document_type }}">
            <small class="text-muted" style="font-size:11px;">No se puede modificar</small>
        @endif
    </div>

    <div class="col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Número de documento</label>
        <input type="text" name="document_number"
               value="{{ old('document_number', $customer->document_number ?? '') }}"
               class="{{ $inputClass }}" placeholder="Ej: GOMJ850101HDFXXX"
               {{ $isEdit ? 'readonly style=background:#f0f0f0;cursor:not-allowed;' : '' }}>
        @if($isEdit)
            <small class="text-muted" style="font-size:11px;">No se puede modificar</small>
        @else
            <small class="text-muted" style="font-size:11px;">Opcional</small>
        @endif
    </div>

    <div class="col-12 col-md-6">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Teléfono</label>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
               class="{{ $inputClass }}" placeholder="Ej: 6621234567">
    </div>

    {{-- Dirección con autocompletado --}}
    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Dirección</label>
        <div class="position-relative">
            <textarea name="address" rows="2" id="addressInput"
                      class="{{ $inputClass }}" placeholder="Escribe la dirección y selecciona una sugerencia..."
                      oninput="searchAddress(this.value)">{{ old('address', $customer->address ?? '') }}</textarea>
            <div id="addressSuggestions" class="position-absolute bg-white rounded-3 shadow-sm w-100"
                 style="display:none; z-index:999; border:0.5px solid #e8e8e8; max-height:200px; overflow-y:auto; top:100%;">
            </div>
        </div>
    </div>

    {{-- Mapa --}}
    <div class="col-12">
        <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Ubicación en el mapa</label>
        <div id="mapPicker" style="height:280px; border-radius:8px; border:0.5px solid #ddd; margin-bottom:8px;"></div>
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $customer->latitude ?? '') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $customer->longitude ?? '') }}">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" style="font-size:11px;">Escribe la dirección o haz clic en el mapa para marcar la ubicación</small>
            <small id="coordsDisplay" style="font-size:11px; color:#1f6b21; font-weight:500;">
                @if(old('latitude', $customer->latitude ?? ''))
                    {{ old('latitude', $customer->latitude) }}, {{ old('longitude', $customer->longitude) }}
                @endif
            </small>
        </div>
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, marker, searchTimeout;

document.addEventListener('DOMContentLoaded', function() {
    const lat = parseFloat(document.getElementById('latitude').value) || 29.0729;
    const lng = parseFloat(document.getElementById('longitude').value) || -110.9559;
    const hasCoords = document.getElementById('latitude').value !== '';

    map = L.map('mapPicker').setView([lat, lng], hasCoords ? 16 : 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    if (hasCoords) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
});

function setMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    document.getElementById('latitude').value = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);
    document.getElementById('coordsDisplay').textContent = lat.toFixed(5) + ', ' + lng.toFixed(5);
}

function onMarkerDrag(e) {
    const { lat, lng } = e.target.getLatLng();
    document.getElementById('latitude').value = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);
    document.getElementById('coordsDisplay').textContent = lat.toFixed(5) + ', ' + lng.toFixed(5);
    reverseGeocode(lat, lng);
}

function searchAddress(query) {
    clearTimeout(searchTimeout);
    const box = document.getElementById('addressSuggestions');

    if (query.length < 5) {
        box.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&limit=5&countrycodes=mx')
            .then(r => r.json())
            .then(results => {
                if (!results.length) {
                    box.style.display = 'none';
                    return;
                }

                box.innerHTML = results.map(r => `
                    <div class="px-3 py-2" style="cursor:pointer; font-size:13px; border-bottom:0.5px solid #f5f5f5;"
                         onmouseover="this.style.background='#f8f9f8'" onmouseout="this.style.background='white'"
                         onclick="selectAddress('${r.display_name.replace(/'/g, "\\'")}', ${r.lat}, ${r.lon})">
                        <div class="d-flex align-items-start gap-2">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5" style="flex-shrink:0; margin-top:3px;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span style="color:#333;">${r.display_name}</span>
                        </div>
                    </div>
                `).join('');

                box.style.display = 'block';
            })
            .catch(() => { box.style.display = 'none'; });
    }, 500);
}

function selectAddress(address, lat, lng) {
    document.getElementById('addressInput').value = address;
    document.getElementById('addressSuggestions').style.display = 'none';

    setMarker(parseFloat(lat), parseFloat(lng));
    map.setView([lat, lng], 16);
}

function reverseGeocode(lat, lng) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
        .then(r => r.json())
        .then(data => {
            if (data.display_name) {
                document.getElementById('addressInput').value = data.display_name;
            }
        })
        .catch(() => {});
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#addressInput') && !e.target.closest('#addressSuggestions')) {
        document.getElementById('addressSuggestions').style.display = 'none';
    }
});
</script>