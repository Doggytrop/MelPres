@extends('layouts.app')

@section('title', 'Configuración')

@section('content')

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Configuración del sistema</h5>
    <span class="text-muted" style="font-size:13px;">Personaliza el comportamiento del sistema</span>
</div>

@if(session('success'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:#e8f5e9; border-color:#c8e6c9 !important; color:#1f6b21; font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('configuracion.update') }}">
    @csrf

    <div class="row g-4">

        {{-- General --}}
        <div class="col-12">
            <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2"
                     style="border-color:#f0f0f0 !important; background:#f8f9f8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                    <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">General</span>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        @foreach($grupos->get('general', []) as $config)
                            <div class="col-md-4">
                                <label class="d-block mb-1 text-muted"
                                       style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                                    {{ $config->descripcion }}
                                </label>
                                <input type="text" name="{{ $config->clave }}"
                                       value="{{ $config->valor }}"
                                       class="form-control form-control-sm">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Préstamos --}}
        <div class="col-md-6">
            <div class="bg-white border rounded-3 overflow-hidden h-100" style="border-color:#e8e8e8 !important;">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2"
                     style="border-color:#f0f0f0 !important; background:#f8f9f8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamos</span>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        @foreach($grupos->get('prestamos', []) as $config)
                            <div class="col-12">
                                <label class="d-block mb-1 text-muted"
                                       style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                                    {{ $config->descripcion }}
                                </label>
                                @if($config->clave === 'prestamos_mora_defecto_tipo')
                                    <select name="{{ $config->clave }}" class="form-control form-control-sm">
                                        <option value="">Sin mora por defecto</option>
                                        <option value="fija" {{ $config->valor === 'fija' ? 'selected' : '' }}>Monto fijo por día</option>
                                        <option value="porcentaje" {{ $config->valor === 'porcentaje' ? 'selected' : '' }}>Porcentaje por periodo</option>
                                    </select>
                                @else
                                    <input type="{{ $config->tipo === 'integer' ? 'number' : 'text' }}"
                                           name="{{ $config->clave }}"
                                           value="{{ $config->valor }}"
                                           class="form-control form-control-sm">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Asesores --}}
        <div class="col-md-6">
            <div class="bg-white border rounded-3 overflow-hidden h-100" style="border-color:#e8e8e8 !important;">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2"
                     style="border-color:#f0f0f0 !important; background:#f8f9f8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        <path d="M21 21v-2a4 4 0 0 0-3-3.85"/>
                    </svg>
                    <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Asesores</span>
                </div>
                <div class="p-4">
                    <div class="d-flex flex-column gap-3">
                        @foreach($grupos->get('asesores', []) as $config)
                            <label class="d-flex align-items-start gap-3 p-3 rounded-3"
                                   style="border:0.5px solid #eee; cursor:pointer;">
                                <input type="checkbox"
                                       name="{{ $config->clave }}"
                                       value="1"
                                       {{ $config->valor == '1' ? 'checked' : '' }}
                                       style="margin-top:2px; accent-color:#1f6b21;">
                                <div>
                                    <span class="d-block fw-medium" style="font-size:13px; color:#1a2e1a;">
                                        {{ $config->descripcion }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Corte de caja --}}
        <div class="col-12">
            <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2"
                     style="border-color:#f0f0f0 !important; background:#f8f9f8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Corte de caja</span>
                </div>
                <div class="p-4">
                    <div class="d-flex gap-3 flex-wrap">
                        @foreach($grupos->get('caja', []) as $config)
                            <label class="d-flex align-items-start gap-3 p-3 rounded-3"
                                   style="border:0.5px solid #eee; cursor:pointer; min-width:280px;">
                                <input type="checkbox"
                                       name="{{ $config->clave }}"
                                       value="1"
                                       {{ $config->valor == '1' ? 'checked' : '' }}
                                       style="margin-top:2px; accent-color:#1f6b21;">
                                <div>
                                    <span class="d-block fw-medium" style="font-size:13px; color:#1a2e1a;">
                                        {{ $config->descripcion }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
            {{-- Simulador --}}
        <div class="col-12">
            <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2"
                    style="border-color:#f0f0f0 !important; background:#f8f9f8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <path d="M8 21h8M12 17v4"/>
                    </svg>
                    <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Simulador de préstamos</span>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        @foreach($grupos->get('simulador', []) as $config)
                            <div class="col-md-4">
                                <label class="d-block mb-1 text-muted"
                                    style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                                    {{ $config->descripcion }}
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="{{ $config->clave }}"
                                        value="{{ $config->valor }}"
                                        class="form-control form-control-sm"
                                        min="1" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    {{-- Guardar --}}
    <div class="mt-4">
        <button type="submit" class="btn btn-sm"
                style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 24px;">
            Guardar configuración
        </button>
    </div>

</form>

@endsection