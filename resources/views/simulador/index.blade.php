@extends('layouts.app')

@section('title', 'Simulador de préstamos')

@section('content')

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Simulador de préstamos</h5>
    <span class="text-muted" style="font-size:13px;">Calcula si un préstamo es viable antes de otorgarlo</span>
</div>

<div class="row g-4">

    {{-- Formulario --}}
    <div class="col-md-5">
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">

            <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
                1. Condiciones del préstamo
            </p>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Tipo de préstamo</label>
                    <select id="tipo" class="form-control form-control-sm">
                        <option value="plazo">Plazo (fijo)</option>
                        <option value="interes">Interés (renovable)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Monto</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" id="monto" class="form-control form-control-sm" placeholder="0.00" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés mensual</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="interes" class="form-control form-control-sm" placeholder="0" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Frecuencia de pago</label>
                    <select id="frecuencia" class="form-control form-control-sm">
                        <option value="semanal">Semanal</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="mensual">Mensual</option>
                    </select>
                </div>
                <div class="col-md-6" id="campo_periodos">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Número de periodos</label>
                    <input type="number" id="periodos" class="form-control form-control-sm" placeholder="Ej: 4" min="1">
                </div>
            </div>

            <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
                2. Capacidad de pago del cliente
                <span class="fw-normal text-muted" style="font-size:11px;">— opcional</span>
            </p>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente (para incluir préstamos activos)</label>
                    <select id="cliente_id" class="form-control form-control-sm">
                        <option value="">Sin cliente seleccionado</option>
                        @foreach(\App\Models\Cliente::where('estado', 'activo')->orderBy('nombre')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Ingreso del cliente</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" id="ingreso" class="form-control form-control-sm" placeholder="0.00" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Frecuencia del ingreso</label>
                    <select id="freq_ingreso" class="form-control form-control-sm">
                        <option value="semanal">Semanal</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="mensual">Mensual</option>
                    </select>
                </div>
            </div>

            <button onclick="calcular()"
                    class="btn btn-sm w-100"
                    style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:10px;">
                Calcular
            </button>
        </div>
    </div>

    {{-- Resultados --}}
    <div class="col-md-7">

        {{-- Placeholder --}}
        <div id="placeholder" class="bg-white border rounded-3 p-5 text-center" style="border-color:#e8e8e8 !important;">
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                 style="width:56px; height:56px; background:#e8f5e9;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8M12 17v4"/>
                </svg>
            </div>
            <p class="fw-medium mb-1" style="color:#1a2e1a;">Ingresa los datos del préstamo</p>
            <p class="text-muted mb-0" style="font-size:13px;">El resultado aparecerá aquí</p>
        </div>

        {{-- Resultados --}}
        <div id="resultados" style="display:none;">

            {{-- Evaluación --}}
            <div id="evaluacion_box" class="rounded-3 p-4 mb-3 d-flex align-items-start gap-3" style="display:none !important;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" id="eval_icon">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
                <div>
                    <p class="fw-medium mb-1" id="eval_titulo" style="font-size:14px;"></p>
                    <p class="mb-0" id="eval_mensaje" style="font-size:13px;"></p>
                </div>
            </div>

            {{-- Métricas --}}
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
                        <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Pago sugerido</span>
                        <span class="fw-medium" style="font-size:20px; color:#1f6b21;" id="res_pago_sugerido">—</span>
                        <span class="text-muted d-block" style="font-size:11px;" id="res_frecuencia">—</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
                        <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés mensual</span>
                        <span class="fw-medium" style="font-size:20px; color:#1a2e1a;" id="res_interes_mensual">—</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
                        <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total a pagar</span>
                        <span class="fw-medium" style="font-size:20px; color:#1a2e1a;" id="res_total">—</span>
                    </div>
                </div>
            </div>

            {{-- Desglose financiero --}}
            <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
                <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Desglose financiero</p>
                <div id="desglose"></div>
            </div>

            {{-- Capacidad de pago --}}
            <div class="bg-white border rounded-3 p-4" id="capacidad_box" style="border-color:#e8e8e8 !important; display:none;">
                <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Análisis de capacidad de pago</p>
                <div id="capacidad_detalle"></div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                        <span class="text-muted">Compromiso del ingreso</span>
                        <span id="capacidad_porcentaje" class="fw-medium"></span>
                    </div>
                    <div class="rounded-pill overflow-hidden" style="height:8px; background:#e8e8e8;">
                        <div id="capacidad_barra" class="rounded-pill" style="height:8px; transition:width .3s;"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function calcular() {
    const monto    = document.getElementById('monto').value;
    const interes  = document.getElementById('interes').value;
    const tipo     = document.getElementById('tipo').value;
    const freq     = document.getElementById('frecuencia').value;
    const periodos = document.getElementById('periodos').value;
    const ingreso  = document.getElementById('ingreso').value;
    const freqIng  = document.getElementById('freq_ingreso').value;
    const clienteId = document.getElementById('cliente_id').value;

    if (!monto || !interes) {
        alert('Ingresa el monto y el interés para calcular.');
        return;
    }

    fetch('{{ route("simulador.calcular") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            monto,
            interes_rate:      interes,
            tipo,
            frecuencia:        freq,
            numero_periodos:   periodos,
            ingreso_cliente:   ingreso,
            frecuencia_ingreso: freqIng,
            cliente_id:        clienteId,
        }),
    })
    .then(r => r.json())
    .then(data => mostrarResultados(data));
}

function mostrarResultados(data) {
    document.getElementById('placeholder').style.display  = 'none';
    document.getElementById('resultados').style.display   = 'block';

    const freqLabel = { semanal: 'por semana', quincenal: 'por quincena', mensual: 'por mes' };

    document.getElementById('res_pago_sugerido').textContent  = '$ ' + formatNum(data.pago_sugerido);
    document.getElementById('res_frecuencia').textContent     = freqLabel[data.frecuencia] || '';
    document.getElementById('res_interes_mensual').textContent = '$ ' + formatNum(data.interes_mensual);
    document.getElementById('res_total').textContent          = data.total_pagar
        ? '$ ' + formatNum(data.total_pagar)
        : 'Renovable';

    // Desglose
    let desglose = '';
    if (data.tipo === 'plazo') {
        const interesTotal = data.interes_total;
        desglose += fila('Capital prestado',   '$ ' + formatNum(data.monto));
        desglose += fila('Interés total',      '$ ' + formatNum(interesTotal));
        desglose += fila('Total a pagar',      '$ ' + formatNum(data.total_pagar), true);
        desglose += fila('Periodos',           data.periodos + ' pagos');
        desglose += fila('Pago por periodo',   '$ ' + formatNum(data.pago_sugerido));
    } else {
        desglose += fila('Capital prestado',   '$ ' + formatNum(data.monto));
        desglose += fila('Interés mensual',    '$ ' + formatNum(data.interes_mensual));
        desglose += fila('Capital',            'No disminuye automáticamente');
    }
    document.getElementById('desglose').innerHTML = desglose;

    // Evaluación
    if (data.evaluacion) {
        const ev  = data.evaluacion;
        const box = document.getElementById('evaluacion_box');
        box.style.display    = 'flex';
        box.style.background = ev.bg;
        box.style.border     = '0.5px solid ' + ev.color;

        document.getElementById('eval_titulo').textContent  = ev.titulo;
        document.getElementById('eval_titulo').style.color = ev.color;
        document.getElementById('eval_mensaje').textContent = ev.mensaje;
        document.getElementById('eval_mensaje').style.color = ev.color;
        document.getElementById('eval_icon').style.stroke   = ev.color;

        // Capacidad
        const capBox = document.getElementById('capacidad_box');
        capBox.style.display = 'block';

        let capDetalle = '';
        capDetalle += fila('Ingreso mensual',          '$ ' + formatNum(ev.ingreso_mensual));
        capDetalle += fila('Pago mensual nuevo',       '$ ' + formatNum(ev.pago_mensual));
        if (ev.compromiso_actual > 0) {
            capDetalle += fila('Compromisos actuales', '$ ' + formatNum(ev.compromiso_actual));
        }
        capDetalle += fila('Total compromisos',        '$ ' + formatNum(ev.total_compromiso), true);
        document.getElementById('capacidad_detalle').innerHTML = capDetalle;

        const pct = Math.min(ev.porcentaje, 100);
        document.getElementById('capacidad_porcentaje').textContent = ev.porcentaje + '%';
        document.getElementById('capacidad_porcentaje').style.color = ev.color;
        document.getElementById('capacidad_barra').style.width      = pct + '%';
        document.getElementById('capacidad_barra').style.background = ev.color;
    }
}

function fila(label, valor, negrita = false) {
    return `<div class="d-flex justify-content-between py-2" style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
        <span class="text-muted">${label}</span>
        <span style="color:#1a2e1a; font-weight:${negrita ? '500' : '400'};">${valor}</span>
    </div>`;
}

function formatNum(n) {
    return parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Mostrar/ocultar periodos según tipo
document.getElementById('tipo').addEventListener('change', function() {
    document.getElementById('campo_periodos').style.display = this.value === 'plazo' ? 'block' : 'none';
});
</script>

@endsection