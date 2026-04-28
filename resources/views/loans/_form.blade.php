@php $inputClass = 'form-control form-control-sm'; @endphp
@php $labelStyle = 'font-size:11px; text-transform:uppercase; letter-spacing:.05em;'; @endphp

<div class="row g-4">

    {{-- SECCIÓN 1: CLIENTE --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            1. Información del cliente
        </p>
        <div class="row g-3">
            <div class="col-12">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Cliente *</label>
                <select name="customer_id" class="{{ $inputClass }} @error('customer_id') is-invalid @enderror">
                    <option value="">Seleccionar cliente...</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}"
                            {{ old('customer_id', $loan->customer_id ?? '') == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: TIPO DE PRÉSTAMO --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            2. Tipo de préstamo
        </p>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="type_interest" class="d-block p-3 rounded-3"
                       style="border:0.5px solid #ddd; cursor:pointer;" id="card_interest">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="radio" name="type" value="interest" id="type_interest"
                               {{ old('type', $loan->type ?? '') == 'interest' ? 'checked' : '' }}>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamo a interés</span>
                        <span class="ms-auto px-2 py-1 rounded-2"
                              style="background:#fff3e0; color:#e65100; font-size:10px;">Renovable</span>
                    </div>
                    <p class="mb-1" style="font-size:12px; color:#555;">
                        El cliente paga <strong>solo interés</strong> cada periodo. El capital no disminuye.
                    </p>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Ejemplo: Prestas $1,000 al 10% mensual → el cliente paga $100 cada mes hasta liquidar el capital.
                    </p>
                </label>
            </div>

            <div class="col-md-6">
                <label for="type_term" class="d-block p-3 rounded-3"
                       style="border:0.5px solid #ddd; cursor:pointer;" id="card_term">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="radio" name="type" value="term" id="type_term"
                               {{ old('type', $loan->type ?? '') == 'term' ? 'checked' : '' }}>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamo a plazo fijo</span>
                        <span class="ms-auto px-2 py-1 rounded-2"
                              style="background:#e8f5e9; color:#1f6b21; font-size:10px;">Fijo</span>
                    </div>
                    <p class="mb-1" style="font-size:12px; color:#555;">
                        El cliente paga <strong>capital + interés</strong> en cuotas durante un periodo definido.
                    </p>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Ejemplo: Prestas $1,000 al 10% por 3 meses → el cliente debe $1,300 total dividido en pagos.
                    </p>
                </label>
            </div>
        </div>
        @error('type') <div class="text-danger" style="font-size:12px;">{{ $message }}</div> @enderror
    </div>

    {{-- SECCIÓN 3: CONDICIONES --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            3. Condiciones del préstamo
        </p>
        <div class="row g-3">

            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Monto *</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="font-size:13px;">$</span>
                    <input type="number" step="0.01" name="original_amount" id="original_amount"
                           value="{{ old('original_amount', $loan->original_amount ?? '') }}"
                           class="{{ $inputClass }} @error('original_amount') is-invalid @enderror"
                           placeholder="0.00">
                </div>
                @error('original_amount') <div class="text-danger" style="font-size:12px;">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Interés mensual *</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="interest_rate" id="interest_rate"
                           value="{{ old('interest_rate', $loan->interest_rate ?? '') }}"
                           class="{{ $inputClass }} @error('interest_rate') is-invalid @enderror"
                           placeholder="0">
                    <span class="input-group-text" style="font-size:13px;">%</span>
                </div>
                <small class="text-muted" style="font-size:11px;">
                    Porcentaje que se cobra cada mes sobre el capital
                </small>
                @error('interest_rate') <div class="text-danger" style="font-size:12px;">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Frecuencia de pago *</label>
                <select name="payment_frequency" id="payment_frequency"
                        class="{{ $inputClass }} @error('payment_frequency') is-invalid @enderror">
                    @foreach(['weekly' => 'Semanal', 'biweekly' => 'Quincenal', 'monthly' => 'Mensual'] as $value => $text)
                        <option value="{{ $value }}"
                            {{ old('payment_frequency', $loan->payment_frequency ?? '') == $value ? 'selected' : '' }}>
                            {{ $text }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted" style="font-size:11px;">Cada cuánto pagará el cliente</small>
                @error('payment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4" id="campo_periodos">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Número de periodos *</label>
                <input type="number" name="number_of_periods" id="number_of_periods"
                       value="{{ old('number_of_periods', $loan->number_of_periods ?? '') }}"
                       class="{{ $inputClass }}" placeholder="Ej: 4" min="1">
                <small class="text-muted" style="font-size:11px;" id="texto_periodos">
                    ¿Cuántos pagos hará el cliente en total?
                </small>
            </div>

            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Fecha de inicio</label>
                <input type="date" name="start_date" id="start_date"
                       value="{{ old('start_date', isset($loan->start_date) ? $loan->start_date->format('Y-m-d') : date('Y-m-d')) }}"
                       class="{{ $inputClass }}" readonly
                       style="background:#f8f9f8; color:#888;">
                <small class="text-muted" style="font-size:11px;">Se establece automáticamente a hoy</small>
            </div>

            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Fecha de vencimiento</label>
                <input type="date" name="due_date" id="due_date"
                       value="{{ old('due_date', isset($loan->due_date) ? $loan->due_date->format('Y-m-d') : '') }}"
                       class="{{ $inputClass }}" readonly
                       style="background:#f8f9f8; color:#1f6b21; font-weight:500;">
                <small class="text-muted" style="font-size:11px;">Se calcula según periodos y frecuencia</small>
            </div>

        </div>
    </div>

    {{-- RESUMEN EN TIEMPO REAL --}}
    <div class="col-12" id="resumen_box" style="display:none;">
        <div class="p-3 rounded-3" style="background:#e8f5e9; border:0.5px solid #c8e6c9;">
            <p class="fw-medium mb-2" style="font-size:12px; color:#1a2e1a;">Resumen del préstamo</p>
            <div class="row g-2" style="font-size:13px;">
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Capital</span>
                    <span id="res_capital" style="color:#1a2e1a; font-weight:500;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Interés total</span>
                    <span id="res_interest" style="color:#1a2e1a; font-weight:500;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Total a cobrar</span>
                    <span id="res_total" style="color:#1f6b21; font-weight:500; font-size:15px;">—</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block" style="font-size:11px;">Pago sugerido</span>
                    <span id="res_cuota" style="color:#1a2e1a; font-weight:500;">—</span>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 4: PENALIZACIÓN --}}
    <div class="col-12">
        <p class="fw-medium mb-1 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            4. Configuración de penalización
            <span class="ms-2 fw-normal text-muted" style="font-size:11px;">— opcional</span>
        </p>
        <p class="text-muted mb-3" style="font-size:12px;">
            La penalización es un cargo extra cuando el cliente se atrasa. Si no se configura, no se aplicará cargo adicional.
        </p>

        <div class="row g-3">
            <div class="col-12">
                <label class="d-block mb-2 text-muted" style="{{ $labelStyle }}">Tipo de penalización</label>
                <div class="d-flex gap-3 flex-wrap">

                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:220px;" id="card_mora_ninguna">
                        <input type="radio" name="penalty_type" value="" id="mora_ninguna"
                               {{ old('penalty_type', $loan->penalty_type ?? '') == '' ? 'checked' : '' }}
                               style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Sin penalización</span>
                            <span style="font-size:11px; color:#888;">No se cobra extra por atraso</span>
                        </div>
                    </label>

                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:220px;" id="card_mora_fixed">
                        <input type="radio" name="penalty_type" value="fixed" id="mora_fixed"
                               {{ old('penalty_type', $loan->penalty_type ?? '') == 'fixed' ? 'checked' : '' }}
                               style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Monto fijo por día</span>
                            <span style="font-size:11px; color:#888;">
                                Cobrar $X por cada día de atraso hasta el siguiente periodo
                            </span>
                        </div>
                    </label>

                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:220px;" id="card_mora_percentage">
                        <input type="radio" name="penalty_type" value="percentage" id="mora_percentage"
                               {{ old('penalty_type', $loan->penalty_type ?? '') == 'percentage' ? 'checked' : '' }}
                               style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Porcentaje por periodo</span>
                            <span style="font-size:11px; color:#888;">
                                Cobrar X% del saldo total por cada periodo vencido
                            </span>
                        </div>
                    </label>

                </div>
            </div>

            <div class="col-md-4" id="campo_penalty_value" style="display:none;">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}" id="label_penalty_value">Valor</label>
                <input type="number" step="0.01" name="penalty_value"
                       value="{{ old('penalty_value', $loan->penalty_value ?? '') }}"
                       class="{{ $inputClass }}" placeholder="0.00" id="penalty_value">
                <small class="text-muted" style="font-size:11px;" id="hint_penalty_value"></small>
            </div>

            <div class="col-md-4" id="campo_grace_days" style="display:none;">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Días de gracia</label>
                <input type="number" name="grace_days"
                       value="{{ old('grace_days', $loan->grace_days ?? 0) }}"
                       class="{{ $inputClass }}" placeholder="0" min="0" id="grace_days">
                <small class="text-muted" style="font-size:11px;">
                    Días extra antes de aplicar penalización. Pon 0 para sin días de gracia.
                </small>
            </div>

        </div>
    </div>

    {{-- SECCIÓN 5: NOTAS --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            5. Notas
            <span class="ms-2 fw-normal text-muted" style="font-size:11px;">— opcional</span>
        </p>
        <textarea name="notes" rows="2" class="{{ $inputClass }}"
                  placeholder="Condiciones especiales, acuerdos con el cliente, notas importantes...">{{ old('notes', $loan->notes ?? '') }}</textarea>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const monto      = document.getElementById('original_amount');
    const interest   = document.getElementById('interest_rate');
    const frecuencia = document.getElementById('payment_frequency');
    const periodos   = document.getElementById('number_of_periods');
    const fechaInicio = document.getElementById('start_date');
    const fechaFin   = document.getElementById('due_date');
    const resumenBox = document.getElementById('resumen_box');
    const typeInterest = document.getElementById('type_interest');
    const typeTerm     = document.getElementById('type_term');

    const moraNinguna    = document.getElementById('mora_ninguna');
    const moraFixed      = document.getElementById('mora_fixed');
    const moraPercentage = document.getElementById('mora_percentage');
    const campoMoraValor  = document.getElementById('campo_penalty_value');
    const campoDiasGracia = document.getElementById('campo_grace_days');
    const labelMoraValor  = document.getElementById('label_penalty_value');
    const hintMoraValor   = document.getElementById('hint_penalty_value');

    function actualizarCardsTipo() {
        document.getElementById('card_interest').style.borderColor = typeInterest.checked ? '#1f6b21' : '#ddd';
        document.getElementById('card_interest').style.background  = typeInterest.checked ? '#f0faf0' : '#fff';
        document.getElementById('card_term').style.borderColor     = typeTerm.checked     ? '#1f6b21' : '#ddd';
        document.getElementById('card_term').style.background      = typeTerm.checked     ? '#f0faf0' : '#fff';
        calcular();
    }

    typeInterest.addEventListener('change', actualizarCardsTipo);
    typeTerm.addEventListener('change', actualizarCardsTipo);
    actualizarCardsTipo();

    function calcularFechaFin() {
        const inicio = new Date(fechaInicio.value);
        const p      = parseInt(periodos.value) || 0;
        const freq   = frecuencia.value;

        if (!p || !freq || !fechaInicio.value) { fechaFin.value = ''; return; }

        let fin = new Date(inicio);
        if (freq === 'weekly')        fin.setDate(fin.getDate() + (p * 7));
        else if (freq === 'biweekly') fin.setDate(fin.getDate() + (p * 15));
        else if (freq === 'monthly')  fin.setMonth(fin.getMonth() + p);

        fechaFin.value = fin.toISOString().split('T')[0];

        const textos = { weekly: 'semanas', biweekly: 'quincenas', monthly: 'meses' };
        document.getElementById('texto_periodos').textContent =
            `El préstamo durará ${p} ${textos[freq] || 'periodos'}`;
    }

    function calcular() {
        const m      = parseFloat(monto.value)    || 0;
        const i      = parseFloat(interest.value) || 0;
        const p      = parseInt(periodos.value)   || 0;
        const isTerm = document.querySelector('input[name="type"]:checked')?.value === 'term';

        calcularFechaFin();

        if (m <= 0 || i <= 0) { resumenBox.style.display = 'none'; return; }
        resumenBox.style.display = 'block';

        if (isTerm && p > 0) {
            const freq         = frecuencia.value;
            const mesesMap     = { weekly: 4, biweekly: 2, monthly: 1 };
            const monthsCount  = p / mesesMap[freq];
            const interestTotal = m * (i / 100) * monthsCount;
            const total         = m + interestTotal;
            const installment   = total / p;

            document.getElementById('res_capital').textContent  = '$ ' + m.toFixed(2);
            document.getElementById('res_interest').textContent = '$ ' + interestTotal.toFixed(2);
            document.getElementById('res_total').textContent    = '$ ' + total.toFixed(2);
            document.getElementById('res_cuota').textContent    = '$ ' + installment.toFixed(2) + ' / periodo';
        } else {
            const monthlyInterest = m * (i / 100);
            document.getElementById('res_capital').textContent  = '$ ' + m.toFixed(2);
            document.getElementById('res_interest').textContent = '$ ' + monthlyInterest.toFixed(2) + ' / periodo';
            document.getElementById('res_total').textContent    = 'El capital no disminuye';
            document.getElementById('res_cuota').textContent    = '$ ' + monthlyInterest.toFixed(2) + ' / periodo';
        }
    }

    monto.addEventListener('input', calcular);
    interest.addEventListener('input', calcular);
    frecuencia.addEventListener('change', calcular);
    periodos.addEventListener('input', calcular);
    calcular();

    function actualizarMora() {
        const isFixed      = moraFixed.checked;
        const isPercentage = moraPercentage.checked;
        const hasPenalty   = isFixed || isPercentage;

        campoMoraValor.style.display  = hasPenalty ? 'block' : 'none';
        campoDiasGracia.style.display = hasPenalty ? 'block' : 'none';

        document.getElementById('card_mora_ninguna').style.borderColor   = moraNinguna.checked ? '#1f6b21' : '#ddd';
        document.getElementById('card_mora_fixed').style.borderColor     = isFixed             ? '#e65100' : '#ddd';
        document.getElementById('card_mora_percentage').style.borderColor = isPercentage       ? '#e65100' : '#ddd';

        if (isFixed) {
            labelMoraValor.textContent = 'Monto por día ($)';
            hintMoraValor.textContent  = 'Ej: 20 → cobra $20 por cada día de atraso';
        } else if (isPercentage) {
            labelMoraValor.textContent = 'Porcentaje por periodo (%)';
            hintMoraValor.textContent  = 'Ej: 10 → cobra 10% del saldo por cada periodo vencido';
        }
    }

    moraNinguna.addEventListener('change', actualizarMora);
    moraFixed.addEventListener('change', actualizarMora);
    moraPercentage.addEventListener('change', actualizarMora);
    actualizarMora();

});
</script>