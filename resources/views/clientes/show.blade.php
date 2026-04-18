@extends('layouts.app')

@section('title', 'Detalle Cliente')

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ route('clientes.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a clientes
    </a>
    <a href="{{ route('clientes.edit', $cliente) }}"
       style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:5px 12px;">
        Editar cliente
    </a>
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

@if(session('error'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:#fdecea; border-color:#f5c6c6 !important; color:#c0392b; font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
        </svg>
        {{ session('error') }}
    </div>
@endif

<div class="row g-4">

    {{-- Panel izquierdo --}}
    <div class="col-md-4">

        {{-- Card perfil --}}
        <div class="bg-white border rounded-3 p-4 mb-3 text-center" style="border-color:#e8e8e8 !important;">

            {{-- Foto de perfil --}}
            @if($cliente->foto_url)
                <img src="{{ $cliente->foto_url }}" alt="Foto"
                     class="rounded-circle mb-3"
                     style="width:80px; height:80px; object-fit:cover; border:2px solid #e8f5e9;">
            @else
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium mx-auto mb-3"
                     style="width:80px; height:80px; background:#e8f5e9; color:#1f6b21; font-size:28px;">
                    {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                </div>
            @endif

            <p class="fw-medium mb-0" style="color:#1a2e1a; font-size:16px;">{{ $cliente->nombre_completo }}</p>
            <p class="text-muted mb-3" style="font-size:12px;">ID #{{ $cliente->id }}</p>

            {{-- Badge estado --}}
            @php
                $badge = match($cliente->estado) {
                    'activo'    => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                    'inactivo'  => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => 'Inactivo'],
                    'bloqueado' => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Bloqueado'],
                };
            @endphp
            <span class="px-3 py-1 rounded-pill"
                  style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:11px; font-weight:500;">
                {{ $badge['label'] }}
            </span>
            {{-- Score de crédito --}}
            <div class="mt-3 pt-3" style="border-top:0.5px solid #f0f0f0;">
                <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Score de crédito</p>

                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="fw-medium" style="font-size:28px; color:{{ $scoreData['color'] }};">
                        {{ $cliente->score ?? 100 }}
                    </span>
                    <span class="px-2 py-1 rounded-2 fw-medium"
                        style="background:{{ $scoreData['bg'] }}; color:{{ $scoreData['color'] }}; font-size:12px;">
                        {{ $scoreData['label'] }}
                    </span>
                </div>

                {{-- Barra de progreso --}}
                <div class="rounded-pill overflow-hidden mb-1" style="height:6px; background:#e8e8e8;">
                    <div class="rounded-pill"
                        style="height:6px; width:{{ min($cliente->score ?? 100, 100) }}%;
                                background:{{ $scoreData['color'] }}; transition:width .3s;">
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <span class="text-muted" style="font-size:10px;">0</span>
                    <span class="text-muted" style="font-size:10px;">
                        Actualizado: {{ $cliente->score_actualizado_at ? $cliente->score_actualizado_at->format('d/m/Y') : 'Nunca' }}
                    </span>
                    <span class="text-muted" style="font-size:10px;">100</span>
                </div>
            </div>
        </div>

        {{-- Card info --}}
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Información</p>

            @php
                $rows = [
                    'Documento'   => $cliente->documento_tipo
                                        ? strtoupper($cliente->documento_tipo) . ' — ' . ($cliente->documento_numero ?? 'Sin número')
                                        : '—',
                    'Teléfono'    => $cliente->telefono ?? '—',
                    'Dirección'   => $cliente->direccion ?? '—',
                    'Referencias' => $cliente->referencias ?? '—',
                    'Notas'       => $cliente->notas ?? '—',
                ];
            @endphp

            @foreach($rows as $key => $val)
                <div class="mb-3">
                    <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">{{ $key }}</span>
                    <span style="font-size:13px; color:#333;">{{ $val }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Panel derecho --}}
    <div class="col-md-8">

        {{-- Préstamos activos --}}
        <div class="bg-white border rounded-3 overflow-hidden mb-4" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
                 style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamos activos</span>
                <a href="{{ route('prestamos.create', ['cliente_id' => $cliente->id]) }}"
                   style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                    + Nuevo préstamo
                </a>
            </div>

            @forelse($cliente->prestamosActivos as $prestamo)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important; font-size:14px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('prestamos.show', $prestamo) }}"
                               style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                #{{ $prestamo->id }} — {{ ucfirst($prestamo->tipo) }}
                            </a>
                            <span class="text-muted ms-2" style="font-size:12px;">
                                {{ ucfirst($prestamo->frecuencia_pago) }} · {{ $prestamo->interes_rate }}% mensual
                            </span>
                        </div>
                        <span style="color:#1f6b21; font-weight:500;">
                            ${{ number_format($prestamo->saldo_restante, 2) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    Este cliente no tiene préstamos activos.
                </div>
            @endforelse
        </div>

        {{-- Documentos --}}
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Documentos</span>
            </div>

            {{-- Subir documento --}}
            <div class="px-4 py-3 border-bottom" style="background:#fafafa; border-color:#f0f0f0 !important;">
                <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Subir documento</p>
                <form method="POST" action="{{ route('clientes.documentos.store', $cliente) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="d-block mb-1 text-muted" style="font-size:11px;">Tipo *</label>
                            <select name="tipo" class="form-control form-control-sm">
                                @foreach([
                                    'foto_perfil'           => 'Foto de perfil',
                                    'ine_frente'            => 'INE (frente)',
                                    'ine_reverso'           => 'INE (reverso)',
                                    'comprobante_domicilio' => 'Comprobante de domicilio',
                                    'nomina'                => 'Nómina',
                                    'otro'                  => 'Otro',
                                ] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="d-block mb-1 text-muted" style="font-size:11px;">Archivo * (JPG, PNG, PDF — máx 10MB)</label>
                            <input type="file" name="archivo" accept=".jpg,.jpeg,.png,.pdf,.webp"
                                   class="form-control form-control-sm @error('archivo') is-invalid @enderror">
                            @error('archivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="d-block mb-1 text-muted" style="font-size:11px;">Notas</label>
                            <input type="text" name="notas" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm w-100"
                                    style="background:#1f6b21; color:white; border-radius:8px; font-size:13px;">
                                Subir
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Lista de documentos --}}
            @forelse($cliente->documentos as $doc)
                <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                     style="border-color:#f8f8f8 !important;">
                    <div class="d-flex align-items-center gap-3">

                        {{-- Ícono según tipo --}}
                        <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px; height:36px; background:{{ $doc->esImagen() ? '#e8f5e9' : '#e3f2fd' }};">
                            @if($doc->esImagen())
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="m21 15-5-5L5 21"/>
                                </svg>
                            @else
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            @endif
                        </div>

                        <div>
                            <p class="mb-0 fw-medium" style="font-size:13px; color:#1a2e1a;">{{ $doc->tipo_label }}</p>
                            <p class="mb-0 text-muted" style="font-size:11px;">
                                {{ $doc->nombre_original }} · {{ $doc->tamanio_formateado }}
                            </p>
                            @if($doc->notas)
                                <p class="mb-0 text-muted" style="font-size:11px;">{{ $doc->notas }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        {{-- Ver/Descargar --}}
                        <a href="{{ asset('storage/' . $doc->ruta) }}" target="_blank"
                           style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                            Ver
                        </a>

                        {{-- Eliminar --}}
                        <form method="POST"
                              action="{{ route('clientes.documentos.destroy', [$cliente, $doc]) }}"
                              onsubmit="return confirm('¿Eliminar este documento?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="font-size:12px; color:#c0392b; background:none; border:0.5px solid #f5c6c6; border-radius:6px; padding:4px 10px; cursor:pointer;">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    No hay documentos subidos aún.
                </div>
            @endforelse

        </div>
    </div>
</div>

@endsection