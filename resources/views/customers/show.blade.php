@extends('layouts.app')

@section('title', 'Detalle cliente')

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ route('customers.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a clientes
    </a>
    <a href="{{ route('customers.edit', $customer) }}"
       style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:5px 12px;">
        Editar cliente
    </a>
</div>

@if(session('success'))
    @if(session('credentials'))
        <div class="alert border rounded-3 mb-4 p-4" style="background:#e3f2fd; border-color:#90caf9 !important;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="1.5">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span class="fw-medium" style="color:#1565c0; font-size:14px;">Credenciales del cliente</span>
            </div>
            <p class="mb-1" style="font-size:13px; color:#333;">Acceso para que el cliente consulte sus préstamos:</p>
            <div class="p-3 rounded-2 mt-2" style="background:#fff; border:0.5px solid #90caf9;">
                <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                    <span class="text-muted">Usuario (teléfono):</span>
                    <span class="fw-medium" style="color:#1a2e1a;">{{ session('credentials')['phone'] }}</span>
                </div>
                <div class="d-flex justify-content-between" style="font-size:13px;">
                    <span class="text-muted">Contraseña:</span>
                    <span class="fw-medium" style="color:#1565c0; font-family:monospace; font-size:15px;">
                        {{ session('credentials')['password'] }}
                    </span>
                </div>
            </div>
            <p class="mt-2 mb-0" style="font-size:11px; color:#c0392b;">
                ⚠ Esta contraseña solo se muestra una vez. Anótala y entrégala al cliente.
            </p>
        </div>
    @endif
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:var(--color-secondary); border-color:var(--color-secondary) !important; color:var(--color-primary); font-size:13px;">
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
            @if($customer->photo_url)
                <img src="{{ $customer->photo_url }}" alt="Foto"
                     class="rounded-circle mb-3"
                     style="width:80px; height:80px; object-fit:cover; border:2px solid var(--color-secondary);">
            @else
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium mx-auto mb-3"
                     style="width:80px; height:80px; background:var(--color-secondary); color:var(--color-primary); font-size:28px;">
                    {{ strtoupper(substr($customer->first_name, 0, 1)) }}
                </div>
            @endif

            <p class="fw-medium mb-0" style="color:#1a2e1a; font-size:16px;">{{ $customer->full_name }}</p>
            <p class="text-muted mb-3" style="font-size:12px;">ID #{{ $customer->id }}</p>

            @php
                $badge = match($customer->status) {
                    'active'   => ['bg' => 'var(--color-secondary)', 'color' => 'var(--color-primary)', 'label' => 'Activo'],
                    'inactive' => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => 'Inactivo'],
                    'blocked'  => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Bloqueado'],
                };
            @endphp
            <span class="px-3 py-1 rounded-pill"
                  style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:11px; font-weight:500;">
                {{ $badge['label'] }}
            </span>

            {{-- Score --}}
            <div class="mt-3 pt-3" style="border-top:0.5px solid #f0f0f0;">
                <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Score de crédito</p>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="fw-medium" style="font-size:28px; color:{{ $scoreData['color'] }};">
                        {{ $customer->score ?? 100 }}
                    </span>
                    <span class="px-2 py-1 rounded-2 fw-medium"
                          style="background:{{ $scoreData['bg'] }}; color:{{ $scoreData['color'] }}; font-size:12px;">
                        {{ $scoreData['label'] }}
                    </span>
                </div>
                <div class="rounded-pill overflow-hidden mb-1" style="height:6px; background:#e8e8e8;">
                    <div class="rounded-pill"
                         style="height:6px; width:{{ min($customer->score ?? 100, 100) }}%;
                                background:{{ $scoreData['color'] }}; transition:width .3s;">
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted" style="font-size:10px;">0</span>
                    <span class="text-muted" style="font-size:10px;">
                        Actualizado: {{ $customer->score_updated_at ? $customer->score_updated_at->format('d/m/Y') : 'Nunca' }}
                    </span>
                    <span class="text-muted" style="font-size:10px;">100</span>
                </div>
            </div>
        </div>

        {{-- Card info --}}
        <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Información</p>
            @php
                $rows = [
                    'Documento'   => $customer->document_type
                                        ? strtoupper($customer->document_type) . ' — ' . ($customer->document_number ?? 'Sin número')
                                        : '—',
                    'Teléfono'    => $customer->phone ?? '—',
                    'Dirección'   => $customer->address ?? '—',
                    'Referencias' => $customer->references ?? '—',
                    'Notas'       => $customer->notes ?? '—',
                ];
            @endphp
            @foreach($rows as $key => $val)
                <div class="mb-3">
                    <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">{{ $key }}</span>
                    <span style="font-size:13px; color:#333;">{{ $val }}</span>
                </div>
            @endforeach
        </div>

        {{-- Card acceso al sistema --}}
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
            <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acceso al sistema</p>

            @if($customer->user)
                <div class="mb-2">
                    <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Usuario</span>
                    <span style="font-size:13px; color:#333; font-family:monospace;">{{ $customer->phone }}</span>
                </div>
                <div class="mb-3">
                    <span class="d-block text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Contraseña</span>
                    <span style="font-size:13px; color:#888; font-family:monospace;">
                        {{ strtoupper(substr(str_replace(' ', '', $customer->first_name), 0, 3)) }}•••
                        <span class="ms-1 text-muted" style="font-size:10px;">(3 dígitos variables)</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('customers.reset-password', $customer) }}"
                      data-confirm-submit
                      data-confirm-title="Resetear contraseña"
                      data-confirm-message="¿Generar una nueva contraseña? La actual dejará de funcionar."
                      data-confirm-button="Sí, resetear"
                      data-confirm-tone="primary">
                    @csrf
                    <button type="submit" class="btn btn-sm w-100"
                            style="background:#e3f2fd; color:#1565c0; border:0.5px solid #90caf9; border-radius:8px; font-size:12px; padding:6px;">
                        🔑 Resetear contraseña
                    </button>
                </form>
            @else
                <p class="text-muted mb-0" style="font-size:12px;">Este cliente no tiene acceso registrado.</p>
            @endif
        </div>

    </div>

    {{-- Panel derecho --}}
    <div class="col-md-8">

        {{-- Préstamos activos --}}
        <div class="bg-white border rounded-3 overflow-hidden mb-4" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
                 style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamos activos</span>
                <a href="{{ route('loans.create', ['customer_id' => $customer->id]) }}"
                   style="font-size:12px; color:var(--color-primary); text-decoration:none; border:0.5px solid var(--color-secondary); border-radius:6px; padding:4px 10px;">
                    + Nuevo préstamo
                </a>
            </div>

            @forelse($customer->activeLoans as $loan)
                @php
                    $typeBadge = match($loan->type) {
                        'interest' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Interés'],
                        'term'     => ['bg' => 'var(--color-secondary)', 'color' => 'var(--color-primary)', 'label' => 'Plazo'],
                        'daily'    => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Diario'],
                    };
                @endphp
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important; font-size:14px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('loans.show', $loan) }}"
                               style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                #{{ $loan->id }}
                            </a>
                            <span class="px-2 py-1 rounded-2"
                                  style="background:{{ $typeBadge['bg'] }}; color:{{ $typeBadge['color'] }}; font-size:10px; font-weight:500;">
                                {{ $typeBadge['label'] }}
                            </span>
                            <span class="text-muted" style="font-size:12px;">
                                {{ $loan->frequency_label }} · {{ $loan->interest_rate }}%
                                {{ $loan->type === 'daily' ? 'total' : 'mensual' }}
                            </span>
                        </div>
                        <span style="color:var(--color-primary); font-weight:500;">
                            ${{ number_format($loan->remaining_balance, 2) }}
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

            <div class="px-4 py-3 border-bottom" style="background:#fafafa; border-color:#f0f0f0 !important;">
                <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Subir documento</p>
                <form method="POST" action="{{ route('customers.documents.store', $customer) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="d-block mb-1 text-muted" style="font-size:11px;">Tipo *</label>
                            <select name="type" class="form-control form-control-sm">
                                @foreach([
                                    'profile_photo' => 'Foto de perfil',
                                    'id_front'      => 'INE (frente)',
                                    'id_back'       => 'INE (reverso)',
                                    'address_proof' => 'Comprobante de domicilio',
                                    'payroll'       => 'Nómina',
                                    'other'         => 'Otro',
                                ] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="d-block mb-1 text-muted" style="font-size:11px;">Archivo * (JPG, PNG, PDF — máx 10MB)</label>
                            <input type="file" name="file" accept=".jpg,.jpeg,.png,.pdf,.webp"
                                   class="form-control form-control-sm @error('file') is-invalid @enderror">
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="d-block mb-1 text-muted" style="font-size:11px;">Notas</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm w-100"
                                    style="background:var(--color-primary); color:white; border-radius:8px; font-size:13px;">
                                Subir
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @forelse($customer->documents as $doc)
                <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                     style="border-color:#f8f8f8 !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px; height:36px; background:{{ $doc->isImage() ? 'var(--color-secondary)' : '#e3f2fd' }};">
                            @if($doc->isImage())
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
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
                            <p class="mb-0 fw-medium" style="font-size:13px; color:#1a2e1a;">{{ $doc->type_label }}</p>
                            <p class="mb-0 text-muted" style="font-size:11px;">
                                {{ $doc->original_name }} · {{ $doc->formatted_size }}
                            </p>
                            @if($doc->notes)
                                <p class="mb-0 text-muted" style="font-size:11px;">{{ $doc->notes }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ asset('storage/' . $doc->path) }}" target="_blank"
                           style="font-size:12px; color:var(--color-primary); text-decoration:none; border:0.5px solid var(--color-secondary); border-radius:6px; padding:4px 10px;">
                            Ver
                        </a>
                        <form method="POST"
                              action="{{ route('customers.documents.destroy', [$customer, $doc]) }}"
                              data-confirm-submit data-confirm-title="Eliminar documento" data-confirm-message="¿Seguro que quieres eliminar este documento? Esta acción no se puede deshacer.">
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
