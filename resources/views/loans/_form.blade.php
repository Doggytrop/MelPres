@php $inputClass = 'form-control form-control-sm'; @endphp
@php $labelStyle = 'font-size:11px; text-transform:uppercase; letter-spacing:.05em;'; @endphp

<div class="row g-4">

    {{-- CLIENTE --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            1. Seleccionar cliente
        </p>
        <select name="customer_id" class="{{ $inputClass }} @error('customer_id') is-invalid @enderror">
            <option value="">Seleccionar cliente...</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customer_id', $loan->customer_id ?? '') == $c->id ? 'selected' : '' }}>
                    {{ $c->full_name }}
                </option>
            @endforeach
        </select>
        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- SELECTOR DE TIPO --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            2. Tipo de préstamo
        </p>
        <input type="hidden" name="type" id="loan_type" value="{{ old('type', $loan->type ?? '') }}">
        <div class="row g-3">
            {{-- Card Plazo --}}
            <div class="col-md-4">
                <div class="p-3 rounded-3 type-card" id="card_term"
                     onclick="selectType('term')"
                     style="border:1.5px solid #ddd; cursor:pointer; transition:.2s; height:100%;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px; height:28px; background:var(--color-secondary);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/>
                            </svg>
                        </div>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Plazo</span>
                        <span class="ms-auto px-2 py-1 rounded-2" style="background:var(--color-secondary); color:var(--color-primary); font-size:10px;">Fijo</span>
                    </div>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Capital + interés dividido en cuotas fijas. Semanal, quincenal o mensual.
                    </p>
                </div>
            </div>

            {{-- Card Interés --}}
            <div class="col-md-4">
                <div class="p-3 rounded-3 type-card" id="card_interest"
                     onclick="selectType('interest')"
                     style="border:1.5px solid #ddd; cursor:pointer; transition:.2s; height:100%;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px; height:28px; background:#fff3e0;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="1.5">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Interés</span>
                        <span class="ms-auto px-2 py-1 rounded-2" style="background:#fff3e0; color:#e65100; font-size:10px;">Renovable</span>
                    </div>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Solo paga interés cada periodo. El capital no disminuye hasta liquidar.
                    </p>
                </div>
            </div>

            {{-- Card Diario --}}
            <div class="col-md-4">
                <div class="p-3 rounded-3 type-card" id="card_daily"
                     onclick="selectType('daily')"
                     style="border:1.5px solid #ddd; cursor:pointer; transition:.2s; height:100%;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px; height:28px; background:#e3f2fd;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="1.5">
                                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
                            </svg>
                        </div>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Diario</span>
                        <span class="ms-auto px-2 py-1 rounded-2" style="background:#e3f2fd; color:#1565c0; font-size:10px;">Diario</span>
                    </div>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Interés total fijo. Se divide capital + interés entre los días del plazo.
                    </p>
                </div>
            </div>
        </div>
        @error('type') <div class="text-danger mt-2" style="font-size:12px;">{{ $message }}</div> @enderror
    </div>

    {{-- ============================================ --}}
    {{-- FORMULARIO PLAZO --}}
    {{-- ============================================ --}}
    <div class="col-12 form-section" id="form_term" style="display:none;">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            3. Condiciones del préstamo a plazo
        </p>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Monto *</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="original_amount" id="term_amount"
                           class="{{ $inputClass }}" placeholder="0.00">
                </div>
            </div>
            <div class="col-md-3">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Interés mensual *</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="interest_rate" id="term_rate"
                           class="{{ $inputClass }}" placeholder="0">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Frecuencia *</label>
                <select name="payment_frequency" id="term_frequency" class="{{ $inputClass }}">
                    <option value="weekly">Semanal</option>
                    <option value="biweekly">Quincenal</option>
                    <option value="monthly">Mensual</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Periodos *</label>
                <input type="number" name="number_of_periods" id="term_periods"
                       class="{{ $inputClass }}" placeholder="Ej: 4" min="1">
            </div>
        </div>

        {{-- Resumen plazo --}}
        <div class="mt-3 p-3 rounded-3" id="term_summary" style="display:none; background:var(--color-secondary); border:0.5px solid var(--color-secondary);">
            <p class="fw-medium mb-2" style="font-size:12px; color:#1a2e1a;">Resumen del préstamo</p>
            <div class="row g-2" style="font-size:13px;">
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Capital</span>
                    <span id="term_res_capital" class="fw-medium" style="color:#1a2e1a;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Interés total</span>
                    <span id="term_res_interest" class="fw-medium" style="color:#1a2e1a;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Total a cobrar</span>
                    <span id="term_res_total" class="fw-medium" style="color:var(--color-primary); font-size:15px;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Pago por periodo</span>
                    <span id="term_res_payment" class="fw-medium" style="color:#1a2e1a;">—</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- FORMULARIO INTERÉS --}}
    {{-- ============================================ --}}
    <div class="col-12 form-section" id="form_interest" style="display:none;">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            3. Condiciones del préstamo de interés
        </p>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Monto *</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="original_amount" id="interest_amount"
                           class="{{ $inputClass }}" placeholder="0.00">
                </div>
            </div>
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Interés mensual *</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="interest_rate" id="interest_rate_field"
                           class="{{ $inputClass }}" placeholder="0">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Frecuencia de cobro *</label>
                <select name="payment_frequency" id="interest_frequency" class="{{ $inputClass }}">
                    <option value="weekly">Semanal</option>
                    <option value="biweekly">Quincenal</option>
                    <option value="monthly">Mensual</option>
                </select>
            </div>
        </div>

        {{-- Resumen interés --}}
        <div class="mt-3 p-3 rounded-3" id="interest_summary" style="display:none; background:#fff3e0; border:0.5px solid #ffcc80;">
            <p class="fw-medium mb-2" style="font-size:12px; color:#e65100;">Resumen del préstamo</p>
            <div class="row g-2" style="font-size:13px;">
                <div class="col-md-4">
                    <span class="text-muted d-block" style="font-size:11px;">Capital prestado</span>
                    <span id="interest_res_capital" class="fw-medium" style="color:#1a2e1a;">—</span>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block" style="font-size:11px;">Interés por periodo</span>
                    <span id="interest_res_payment" class="fw-medium" style="color:#e65100;">—</span>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block" style="font-size:11px;">Capital</span>
                    <span class="fw-medium" style="color:#888; font-size:12px;">No disminuye hasta liquidar</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- FORMULARIO DIARIO --}}
    {{-- ============================================ --}}
    <div class="col-12 form-section" id="form_daily" style="display:none;">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            3. Condiciones del préstamo diario
        </p>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Monto *</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="original_amount" id="daily_amount"
                           class="{{ $inputClass }}" placeholder="0.00">
                </div>
            </div>
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Interés total *</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="interest_rate" id="daily_rate"
                           class="{{ $inputClass }}" placeholder="0">
                    <span class="input-group-text">%</span>
                </div>
                <small class="text-muted" style="font-size:11px;">Porcentaje total sobre el monto (no mensual)</small>
            </div>
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Días del plazo *</label>
                <input type="number" name="number_of_periods" id="daily_days"
                       class="{{ $inputClass }}" placeholder="Ej: 30" min="1">
                <small class="text-muted" style="font-size:11px;">¿En cuántos días debe pagar?</small>
            </div>
        </div>

        {{-- Resumen diario --}}
        <div class="mt-3 p-3 rounded-3" id="daily_summary" style="display:none; background:#e3f2fd; border:0.5px solid #90caf9;">
            <p class="fw-medium mb-2" style="font-size:12px; color:#1565c0;">Resumen del préstamo</p>
            <div class="row g-2" style="font-size:13px;">
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Capital</span>
                    <span id="daily_res_capital" class="fw-medium" style="color:#1a2e1a;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Interés total</span>
                    <span id="daily_res_interest" class="fw-medium" style="color:#1a2e1a;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Total a cobrar</span>
                    <span id="daily_res_total" class="fw-medium" style="color:#1565c0; font-size:15px;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Pago diario</span>
                    <span id="daily_res_payment" class="fw-medium" style="color:#1565c0; font-size:15px;">—</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- SECCIÓN COMPARTIDA: FECHA, MORA Y NOTAS --}}
    {{-- ============================================ --}}
    <div class="col-12 shared-section" id="shared_fields" style="display:none;">

        {{-- Fecha --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Fecha de inicio</label>
                <input type="date" name="start_date" id="start_date"
                       value="{{ old('start_date', date('Y-m-d')) }}"
                       class="{{ $inputClass }}" readonly style="background:#f8f9f8; color:#888;">
                <small class="text-muted" style="font-size:11px;">Se registra automáticamente hoy</small>
            </div>
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Fecha de vencimiento</label>
                <input type="date" name="due_date" id="due_date"
                       class="{{ $inputClass }}" readonly style="background:#f8f9f8; color:var(--color-primary); font-weight:500;">
                <small class="text-muted" style="font-size:11px;">Se calcula automáticamente</small>
            </div>
        </div>

        {{-- Mora --}}
        <p class="fw-medium mb-1 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            4. Configuración de mora
            <span class="ms-2 fw-normal text-muted" style="font-size:11px;">— opcional</span>
        </p>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="d-flex gap-3 flex-wrap">
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:200px;" id="card_penalty_none">
                        <input type="radio" name="penalty_type" value="" checked style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Sin mora</span>
                            <span style="font-size:11px; color:#888;">No se cobra extra por atrasos</span>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:200px;" id="card_penalty_fixed">
                        <input type="radio" name="penalty_type" value="fixed" style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Monto fijo por día</span>
                            <span style="font-size:11px; color:#888;">$X por cada día de atraso</span>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:200px;" id="card_penalty_percentage">
                        <input type="radio" name="penalty_type" value="percentage" style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Porcentaje por periodo</span>
                            <span style="font-size:11px; color:#888;">X% del saldo por periodo vencido</span>
                        </div>
                    </label>
                </div>
            </div>
            <div class="col-md-4" id="penalty_value_field" style="display:none;">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}" id="penalty_value_label">Valor</label>
                <input type="number" step="0.01" name="penalty_value" class="{{ $inputClass }}" placeholder="0.00">
                <small class="text-muted" style="font-size:11px;" id="penalty_value_hint"></small>
            </div>
            <div class="col-md-4" id="grace_days_field" style="display:none;">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Días de gracia</label>
                <input type="number" name="grace_days" value="0" class="{{ $inputClass }}" placeholder="0" min="0">
                <small class="text-muted" style="font-size:11px;">Días extra antes de cobrar mora</small>
            </div>
        </div>

        {{-- Notas --}}
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            5. Notas <span class="ms-2 fw-normal text-muted" style="font-size:11px;">— opcional</span>
        </p>
        <textarea name="notes" rows="2" class="{{ $inputClass }}"
                  placeholder="Condiciones especiales, acuerdos con el cliente...">{{ old('notes', $loan->notes ?? '') }}</textarea>
    </div>

</div>

<script>
// ===== SELECCIÓN DE TIPO =====
function selectType(type) {
    document.getElementById('loan_type').value = type;

    // Ocultar todos los formularios
    document.querySelectorAll('.form-section').forEach(el => el.style.display = 'none');
    document.getElementById('shared_fields').style.display = 'none';

    // Resetear cards
    document.querySelectorAll('.type-card').forEach(el => {
        el.style.borderColor = '#ddd';
        el.style.background = '#fff';
    });

    // Activar card seleccionada
    const colors = { term: 'var(--color-primary)', interest: '#e65100', daily: '#1565c0' };
    const bgs    = { term: '#f0faf0', interest: '#fff8f0', daily: '#f0f7ff' };
    document.getElementById('card_' + type).style.borderColor = colors[type];
    document.getElementById('card_' + type).style.background  = bgs[type];

    // Mostrar formulario correspondiente
    document.getElementById('form_' + type).style.display = 'block';
    document.getElementById('shared_fields').style.display = 'block';

    // Limpiar names duplicados — desactivar campos de las otras secciones
    disableOtherForms(type);
}

function disableOtherForms(activeType) {
    const types = ['term', 'interest', 'daily'];
    types.forEach(t => {
        const section = document.getElementById('form_' + t);
        const inputs  = section.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (t === activeType) {
                input.disabled = false;
            } else {
                input.disabled = true;
            }
        });
    });
}

// ===== CÁLCULOS EN TIEMPO REAL =====

// — Plazo —
function calcTerm() {
    const m = parseFloat(document.getElementById('term_amount').value) || 0;
    const i = parseFloat(document.getElementById('term_rate').value)   || 0;
    const p = parseInt(document.getElementById('term_periods').value)  || 0;
    const f = document.getElementById('term_frequency').value;
    const summary = document.getElementById('term_summary');

    if (m <= 0 || i <= 0 || p <= 0) { summary.style.display = 'none'; return; }

    const mesesMap    = { weekly: 4, biweekly: 2, monthly: 1 };
    const monthsCount = p / mesesMap[f];
    const totalInt    = m * (i / 100) * monthsCount;
    const total       = m + totalInt;
    const payment     = total / p;

    const freqLabel = { weekly: 'semana', biweekly: 'quincena', monthly: 'mes' };

    document.getElementById('term_res_capital').textContent  = '$ ' + m.toFixed(2);
    document.getElementById('term_res_interest').textContent = '$ ' + totalInt.toFixed(2);
    document.getElementById('term_res_total').textContent    = '$ ' + total.toFixed(2);
    document.getElementById('term_res_payment').textContent  = '$ ' + payment.toFixed(2) + ' / ' + freqLabel[f];
    summary.style.display = 'block';

    calcDueDate(f, p);
}

// — Interés —
function calcInterest() {
    const m = parseFloat(document.getElementById('interest_amount').value)      || 0;
    const i = parseFloat(document.getElementById('interest_rate_field').value) || 0;
    const summary = document.getElementById('interest_summary');

    if (m <= 0 || i <= 0) { summary.style.display = 'none'; return; }

    const monthlyInt = m * (i / 100);

    document.getElementById('interest_res_capital').textContent = '$ ' + m.toFixed(2);
    document.getElementById('interest_res_payment').textContent = '$ ' + monthlyInt.toFixed(2);
    summary.style.display = 'block';
}

// — Diario —
function calcDaily() {
    const m = parseFloat(document.getElementById('daily_amount').value) || 0;
    const i = parseFloat(document.getElementById('daily_rate').value)   || 0;
    const d = parseInt(document.getElementById('daily_days').value)     || 0;
    const summary = document.getElementById('daily_summary');

    if (m <= 0 || i <= 0 || d <= 0) { summary.style.display = 'none'; return; }

    const totalInt = m * (i / 100);
    const total    = m + totalInt;
    const daily    = total / d;

    document.getElementById('daily_res_capital').textContent  = '$ ' + m.toFixed(2);
    document.getElementById('daily_res_interest').textContent = '$ ' + totalInt.toFixed(2);
    document.getElementById('daily_res_total').textContent    = '$ ' + total.toFixed(2);
    document.getElementById('daily_res_payment').textContent  = '$ ' + daily.toFixed(2) + ' / día';
    summary.style.display = 'block';

    // Calcular fecha vencimiento
    const start = new Date(document.getElementById('start_date').value);
    const due   = new Date(start);
    due.setDate(due.getDate() + d);
    document.getElementById('due_date').value = due.toISOString().split('T')[0];
}

function calcDueDate(freq, periods) {
    const start = new Date(document.getElementById('start_date').value);
    if (!start || !periods) return;
    const due = new Date(start);

    if (freq === 'weekly')        due.setDate(due.getDate() + (periods * 7));
    else if (freq === 'biweekly') due.setDate(due.getDate() + (periods * 15));
    else if (freq === 'monthly')  due.setMonth(due.getMonth() + periods);

    document.getElementById('due_date').value = due.toISOString().split('T')[0];
}

// ===== MORA =====
function updatePenalty() {
    const checked = document.querySelector('input[name="penalty_type"]:checked').value;
    const show    = checked !== '';

    document.getElementById('penalty_value_field').style.display = show ? 'block' : 'none';
    document.getElementById('grace_days_field').style.display    = show ? 'block' : 'none';

    document.getElementById('card_penalty_none').style.borderColor       = checked === ''           ? 'var(--color-primary)' : '#ddd';
    document.getElementById('card_penalty_fixed').style.borderColor      = checked === 'fixed'      ? '#e65100' : '#ddd';
    document.getElementById('card_penalty_percentage').style.borderColor = checked === 'percentage'  ? '#e65100' : '#ddd';

    if (checked === 'fixed') {
        document.getElementById('penalty_value_label').textContent = 'Monto por día ($)';
        document.getElementById('penalty_value_hint').textContent  = 'Ej: 20 → cobra $20 por cada día de atraso';
    } else if (checked === 'percentage') {
        document.getElementById('penalty_value_label').textContent = 'Porcentaje por periodo (%)';
        document.getElementById('penalty_value_hint').textContent  = 'Ej: 10 → cobra 10% del saldo por periodo vencido';
    }
}

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', function() {
    // Plazo
    ['term_amount', 'term_rate', 'term_periods'].forEach(id => {
        document.getElementById(id).addEventListener('input', calcTerm);
    });
    document.getElementById('term_frequency').addEventListener('change', calcTerm);

    // Interés
    ['interest_amount', 'interest_rate_field'].forEach(id => {
        document.getElementById(id).addEventListener('input', calcInterest);
    });

    // Diario
    ['daily_amount', 'daily_rate', 'daily_days'].forEach(id => {
        document.getElementById(id).addEventListener('input', calcDaily);
    });

    // Mora
    document.querySelectorAll('input[name="penalty_type"]').forEach(el => {
        el.addEventListener('change', updatePenalty);
    });
    updatePenalty();

    // Si hay tipo preseleccionado (edición)
    const preselected = document.getElementById('loan_type').value;
    if (preselected) selectType(preselected);
});
</script>