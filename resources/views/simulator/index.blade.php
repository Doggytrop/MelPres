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
                        <option value="term">Plazo fijo</option>
                        <option value="interest">Interés renovable</option>
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
                        <input type="number" id="interest" class="form-control form-control-sm" placeholder="0" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Frecuencia de pago</label>
                    <select id="frecuencia" class="form-control form-control-sm">
                        <option value="weekly">Semanal</option>
                        <option value="biweekly">Quincenal</option>
                        <option value="monthly">Mensual</option>
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
                    <select id="customer_id" class="form-control form-control-sm">
                        <option value="">Sin cliente seleccionado</option>
                        @foreach(\App\Models\Customer::where('status', 'active')->orderBy('first_name')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->full_name }}</option>
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
                        <option value="weekly">Semanal</option>
                        <option value="biweekly">Quincenal</option>
                        <option value="monthly">Mensual</option>
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

        <div id="resultados" style="display:none;">

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

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
                        <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Pago sugerido</span>
                        <span class="fw-medium" style="font-size:20px; color:#1f6b21;" id="res_payment_sugerido">—</span>
                        <span class="text-muted d-block" style="font-size:11px;" id="res_frecuencia">—</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
                        <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés mensual</span>
                        <span class="fw-medium" style="font-size:20px; color:#1a2e1a;" id="res_interest_monthly">—</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
                        <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total a pagar</span>
                        <span class="fw-medium" style="font-size:20px; color:#1a2e1a;" id="res_total">—</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;">
                <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Desglose financiero</p>
                <div id="desglose"></div>
            </div>

            <div class="bg-white border rounded-3 p-4" id="capacidad_box" style="border-color:#e8e8e8 !important; display:none;">
                <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Análisis de capacidad de pago</p>
                <div id="capacidad_detalle"></div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                        <span class="text-muted">Compromiso del ingreso</span>
                        <span id="capacidad_percentage" class="fw-medium"></span>
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
    const monto      = document.getElementById('monto').value;
    const interest   = document.getElementById('interest').value;
    const tipo       = document.getElementById('tipo').value;
    const freq       = document.getElementById('frecuencia').value;
    const periodos   = document.getElementById('periodos').value;
    const ingreso    = document.getElementById('ingreso').value;
    const freqIng    = document.getElementById('freq_ingreso').value;
    const customerId = document.getElementById('customer_id').value;

    if (!monto || !interest) {
        alert('Por favor ingresa el monto y la tasa de interés.');
        return;
    }

    fetch('{{ route("simulator.calculate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            monto,
            interest_rate:     interest,
            type:              tipo,
            frequency:         freq,
            number_of_periods: periodos,
            customer_income:   ingreso,
            income_frequency:  freqIng,
            customer_id:       customerId,
        }),
    })
    .then(r => r.json())
    .then(data => mostrarResultados(data));
}

function mostrarResultados(data) {
    document.getElementById('placeholder').style.display = 'none';
    document.getElementById('resultados').style.display  = 'block';

    const freqLabel = { weekly: 'por semana', biweekly: 'por quincena', monthly: 'por mes' };

    document.getElementById('res_payment_sugerido').textContent = '$ ' + formatNum(data.suggested_payment);
    document.getElementById('res_frecuencia').textContent       = freqLabel[data.frequency] || '';
    document.getElementById('res_interest_monthly').textContent = '$ ' + formatNum(data.monthly_interest);
    document.getElementById('res_total').textContent            = data.total_amount
        ? '$ ' + formatNum(data.total_amount)
        : 'Renovable';

    let desglose = '';
    if (data.type === 'term') {
        desglose += fila('Capital prestado',   '$ ' + formatNum(data.amount));
        desglose += fila('Interés total',      '$ ' + formatNum(data.total_interest));
        desglose += fila('Total a pagar',      '$ ' + formatNum(data.total_amount), true);
        desglose += fila('Periodos',           data.periods + ' pagos');
        desglose += fila('Pago por periodo',   '$ ' + formatNum(data.suggested_payment));
    } else {
        desglose += fila('Capital prestado',   '$ ' + formatNum(data.amount));
        desglose += fila('Interés mensual',    '$ ' + formatNum(data.monthly_interest));
        desglose += fila('Capital',            'No disminuye automáticamente');
    }
    document.getElementById('desglose').innerHTML = desglose;

    if (data.evaluation) {
        const ev  = data.evaluation;
        const box = document.getElementById('evaluacion_box');
        box.style.display    = 'flex';
        box.style.background = ev.bg;
        box.style.border     = '0.5px solid ' + ev.color;

        document.getElementById('eval_titulo').textContent   = ev.title;
        document.getElementById('eval_titulo').style.color   = ev.color;
        document.getElementById('eval_mensaje').textContent  = ev.message;
        document.getElementById('eval_mensaje').style.color  = ev.color;
        document.getElementById('eval_icon').style.stroke    = ev.color;

        const capBox = document.getElementById('capacidad_box');
        capBox.style.display = 'block';

        let capDetalle = '';
        capDetalle += fila('Ingreso mensual',       '$ ' + formatNum(ev.monthly_income));
        capDetalle += fila('Pago mensual nuevo',    '$ ' + formatNum(ev.monthly_payment));
        if (ev.current_commitment > 0) {
            capDetalle += fila('Compromisos actuales', '$ ' + formatNum(ev.current_commitment));
        }
        capDetalle += fila('Total compromisos', '$ ' + formatNum(ev.total_commitment), true);
        document.getElementById('capacidad_detalle').innerHTML = capDetalle;

        const pct = Math.min(ev.percentage, 100);
        document.getElementById('capacidad_percentage').textContent = ev.percentage + '%';
        document.getElementById('capacidad_percentage').style.color = ev.color;
        document.getElementById('capacidad_barra').style.width      = pct + '%';
        document.getElementById('capacidad_barra').style.background = ev.color;
    }
}

function fila(label, valor, negrita = false) {
    return `<div class="d-flex justify-content-between py-2" style="border-border:0.5px solid #f5f5f5; font-size:13px;">
        <span class="text-muted">${label}</span>
        <span style="color:#1a2e1a; font-weight:${negrita ? '500' : '400'};">${valor}</span>
    </div>`;
}

function formatNum(n) {
    return parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.getElementById('tipo').addEventListener('change', function() {
    document.getElementById('campo_periodos').style.display = this.value === 'term' ? 'block' : 'none';
});
</script>

@endsection