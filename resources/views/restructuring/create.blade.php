@extends('layouts.app')

@section('title', 'Reestructurar préstamo')

@section('content')

<div class="mb-4">
    <a href="{{ route('loans.show', $loan) }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver al préstamo
    </a>
</div>

{{-- Banner informativo --}}
<div class="rounded-3 p-4 mb-4 d-flex align-items-start gap-3"
     style="background:#fff3e0; border:0.5px solid #ffcc80;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="1.5" style="flex-shrink:0; margin-top:2px;">
        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>
    <div>
        <p class="fw-medium mb-1" style="color:#e65100; font-size:14px;">
            Reestructuración de préstamo — {{ $loan->customer->first_name_complete }}
        </p>
        <div class="d-flex gap-4" style="font-size:13px; color:#e65100;">
            <span>Saldo: <strong>${{ number_format($loan->remaining_balance, 2) }}</strong></span>
            <span>Mora: <strong>${{ number_format($loan->accumulated_penalty, 2) }}</strong></span>
            <span>Días de atraso: <strong>{{ $diasAtraso }}</strong></span>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('restructuring.store', $loan) }}" id="formrestructuring">
    @csrf

    <div class="row g-4">

        {{-- Selector de tipo --}}
        <div class="col-12">
            <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
                <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:13px;">Selecciona el tipo de reestructuración</p>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="d-block p-3 rounded-3 h-100" style="border:0.5px solid #ddd; cursor:pointer;" id="card_forgiveness">
                            <input type="radio" name="tipo" value="forgiveness" class="me-2" onchange="mostrarOpcion('forgiveness')">
                            <span class="fw-medium" style="font-size:13px; color:#1a2e1a;">Condonación de mora</span>
                            <p class="mb-0 mt-2" style="font-size:12px; color:#888;">
                                Se condona un porcentaje de la mora acumulada para que el cliente pueda reanudar pagos más fácilmente.
                            </p>
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="d-block p-3 rounded-3 h-100" style="border:0.5px solid #ddd; cursor:pointer;" id="card_extension">
                            <input type="radio" name="tipo" value="extension" class="me-2" onchange="mostrarOpcion('extension')">
                            <span class="fw-medium" style="font-size:13px; color:#1a2e1a;">Extensión de plazo</span>
                            <p class="mb-0 mt-2" style="font-size:12px; color:#888;">
                                Se extiende el número de periodos y se congela la mora para dar más tiempo al cliente.
                            </p>
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="d-block p-3 rounded-3 h-100" style="border:0.5px solid #ddd; cursor:pointer;" id="card_nuevo">
                            <input type="radio" name="tipo" value="new_loan" class="me-2" onchange="mostrarOpcion('new_loan')">
                            <span class="fw-medium" style="font-size:13px; color:#1a2e1a;">Nuevo préstamo</span>
                            <p class="mb-0 mt-2" style="font-size:12px; color:#888;">
                                Se cierra el préstamo actual y se crea uno nuevo con condiciones completamente diferentes.
                            </p>
                        </label>
                    </div>

                </div>
            </div>
        </div>

        {{-- Opciones condonación --}}
        <div class="col-12" id="opcion_forgiveness" style="display:none;">
            <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
                <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:13px;">Configurar condonación</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Porcentaje a condonar</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="percentage_forgiveness" id="percentage_forgiveness"
                                   class="form-control form-control-sm" min="1" max="100" placeholder="Ej: 50"
                                   oninput="calcularforgiveness()">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted" style="font-size:11px;">Del total de mora acumulada</small>
                    </div>
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora a condonar</label>
                        <input type="text" id="mora_condonada_display" class="form-control form-control-sm"
                               readonly style="background:#f8f9f8; color:#1f6b21; font-weight:500;">
                    </div>
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora restante</label>
                        <input type="text" id="mora_restante_display" class="form-control form-control-sm"
                               readonly style="background:#f8f9f8;">
                    </div>
                </div>
            </div>
        </div>

        {{-- Opciones extensión --}}
        <div class="col-12" id="opcion_extension" style="display:none;">
            <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
                <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:13px;">Configurar extensión</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Periodos actuales</label>
                        <input type="text" value="{{ $loan->number_of_periods ?? '—' }}"
                               class="form-control form-control-sm" readonly style="background:#f8f9f8;">
                    </div>
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nuevos periodos *</label>
                        <input type="number" name="periodos_nuevos" class="form-control form-control-sm"
                               min="1" placeholder="Ej: 12">
                    </div>
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nueva frecuencia</label>
                        <select name="frecuencia_nueva" class="form-control form-control-sm">
                            <option value="weekly" {{ $loan->payment_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                            <option value="biweekly" {{ $loan->payment_frequency === 'biweekly' ? 'selected' : '' }}>Quincenal</option>
                            <option value="monthly" {{ $loan->payment_frequency === 'monthly' ? 'selected' : '' }}>Mensual</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:#e8f5e9; border:0.5px solid #c8e6c9; font-size:13px;">
                            <strong style="color:#1f6b21;">Nota:</strong>
                            <span style="color:#1a2e1a;"> La mora acumulada se congela en ${{ number_format($loan->accumulated_penalty, 2) }} y el préstamo vuelve a estado activo con el nuevo calendario de pagos.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Opciones nuevo préstamo --}}
        <div class="col-12" id="opcion_new_loan" style="display:none;">
            <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
                <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:13px;">Configurar nuevo préstamo</p>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Tipo</label>
                        <select name="nuevo_tipo" class="form-control form-control-sm">
                            <option value="term" {{ $loan->type === 'term' ? 'selected' : '' }}>Plazo</option>
                            <option value="interest" {{ $loan->type === 'interest' ? 'selected' : '' }}>Interés</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nuevo monto</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="nuevo_monto"
                                   value="{{ $loan->remaining_balance }}"
                                   class="form-control form-control-sm">
                        </div>
                        <small class="text-muted" style="font-size:11px;">Por defecto: saldo actual</small>
                    </div>
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Nuevo interés mensual</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" name="nuevo_interest"
                                   value="{{ $loan->interestt_rate }}"
                                   class="form-control form-control-sm">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Frecuencia</label>
                        <select name="nuevo_frecuencia" class="form-control form-control-sm">
                            <option value="weekly" {{ $loan->payment_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                            <option value="biweekly" {{ $loan->payment_frequency === 'biweekly' ? 'selected' : '' }}>Quincenal</option>
                            <option value="monthly" {{ $loan->payment_frequency === 'monthly' ? 'selected' : '' }}>Mensual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Número de periodos</label>
                        <input type="number" name="nuevo_periodos"
                               value="{{ $loan->number_of_periods }}"
                               class="form-control form-control-sm" min="1">
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:#fdecea; border:0.5px solid #f5c6c6; font-size:13px;">
                            <strong style="color:#c0392b;">Importante:</strong>
                            <span style="color:#1a2e1a;"> El préstamo actual se marcará como <strong>refinanciado</strong> y se creará uno nuevo con las condiciones indicadas.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Motivo y observaciones --}}
        <div class="col-12">
            <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
                <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:13px;">Documentar la reestructuración</p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Motivo de la reestructuración *</label>
                        <textarea name="motivo" rows="2" class="form-control form-control-sm @error('motivo') is-invalid @enderror"
                                  placeholder="Ej: El cliente perdió su empleo, acuerdo de pago voluntario...">{{ old('motivo') }}</textarea>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Observaciones adicionales</label>
                        <textarea name="observaciones" rows="2" class="form-control form-control-sm"
                                  placeholder="Notas internas, condiciones especiales...">{{ old('observaciones') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Botones --}}
        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm"
                        style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:10px 24px;">
                    Aplicar reestructuración
                </button>
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm"
                   style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:10px 24px; text-decoration:none;">
                    Cancelar
                </a>
            </div>
        </div>

    </div>
</form>

<script>
const moraAcumulada = {{ $loan->accumulated_penalty }};

function mostrarOpcion(tipo) {
    ['forgiveness', 'extension', 'new_loan'].forEach(t => {
        document.getElementById('opcion_' + t).style.display = 'none';
        document.getElementById('card_' + t).style.borderColor = '#ddd';
        document.getElementById('card_' + t).style.background  = '#fff';
    });

    document.getElementById('opcion_' + tipo).style.display = 'block';
    document.getElementById('card_' + tipo).style.borderColor = '#1f6b21';
    document.getElementById('card_' + tipo).style.background  = '#f0faf0';
}

function calcularforgiveness() {
    const pct = parseFloat(document.getElementById('percentage_forgiveness').value) || 0;
    const condonada = Math.round(moraAcumulada * (pct / 100) * 100) / 100;
    const restante  = Math.round((moraAcumulada - condonada) * 100) / 100;

    document.getElementById('mora_condonada_display').value = '$ ' + condonada.toFixed(2);
    document.getElementById('mora_restante_display').value  = '$ ' + restante.toFixed(2);
}
</script>

@endsection