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
                <select name="cliente_id" class="{{ $inputClass }} @error('cliente_id') is-invalid @enderror">
                    <option value="">Seleccionar cliente...</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}"
                            {{ old('cliente_id', $prestamo->cliente_id ?? '') == $c->id ? 'selected' : '' }}>
                            {{ $c->nombre_completo }}
                        </option>
                    @endforeach
                </select>
                @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: TIPO DE PRÉSTAMO --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            2. Tipo de préstamo
        </p>

        {{-- Cards de selección de tipo --}}
        <div class="row g-3 mb-3">

            <div class="col-md-6">
                <label for="tipo_interes" class="d-block p-3 rounded-3 cursor-pointer"
                       style="border:0.5px solid #ddd; cursor:pointer;" id="card_interes">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="radio" name="tipo" value="interes" id="tipo_interes"
                               {{ old('tipo', $prestamo->tipo ?? '') == 'interes' ? 'checked' : '' }}>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamo de interés</span>
                        <span class="ms-auto px-2 py-1 rounded-2"
                              style="background:#fff3e0; color:#e65100; font-size:10px;">Renovable</span>
                    </div>
                    <p class="mb-1" style="font-size:12px; color:#555;">
                        El cliente paga <strong>solo el interés</strong> cada periodo. El capital prestado no disminuye.
                    </p>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Ejemplo: Prestás $1,000 al 10% mensual → el cliente paga $100 cada mes indefinidamente hasta que decida liquidar el capital.
                    </p>
                </label>
            </div>

            <div class="col-md-6">
                <label for="tipo_plazo" class="d-block p-3 rounded-3 cursor-pointer"
                       style="border:0.5px solid #ddd; cursor:pointer;" id="card_plazo">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="radio" name="tipo" value="plazo" id="tipo_plazo"
                               {{ old('tipo', $prestamo->tipo ?? '') == 'plazo' ? 'checked' : '' }}>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamo a plazo</span>
                        <span class="ms-auto px-2 py-1 rounded-2"
                              style="background:#e8f5e9; color:#1f6b21; font-size:10px;">Fijo</span>
                    </div>
                    <p class="mb-1" style="font-size:12px; color:#555;">
                        El cliente paga <strong>capital + interés</strong> en cuotas durante un tiempo definido.
                    </p>
                    <p class="mb-0" style="font-size:12px; color:#888;">
                        Ejemplo: Prestás $1,000 al 10% por 3 meses → el cliente debe $1,300 en total dividido en pagos.
                    </p>
                </label>
            </div>

        </div>
        @error('tipo') <div class="text-danger" style="font-size:12px;">{{ $message }}</div> @enderror
    </div>

    {{-- SECCIÓN 3: CONDICIONES --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            3. Condiciones del préstamo
        </p>
        <div class="row g-3">

            {{-- Monto --}}
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Monto a prestar *</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="font-size:13px;">$</span>
                    <input type="number" step="0.01" name="monto_original" id="monto_original"
                           value="{{ old('monto_original', $prestamo->monto_original ?? '') }}"
                           class="{{ $inputClass }} @error('monto_original') is-invalid @enderror"
                           placeholder="0.00">
                </div>
                @error('monto_original') <div class="text-danger" style="font-size:12px;">{{ $message }}</div> @enderror
            </div>

            {{-- Interés --}}
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Interés mensual *</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="interes_rate" id="interes_rate"
                           value="{{ old('interes_rate', $prestamo->interes_rate ?? '') }}"
                           class="{{ $inputClass }} @error('interes_rate') is-invalid @enderror"
                           placeholder="0">
                    <span class="input-group-text" style="font-size:13px;">%</span>
                </div>
                <small class="text-muted" style="font-size:11px;">
                    Porcentaje que cobra cada mes sobre el capital
                </small>
                @error('interes_rate') <div class="text-danger" style="font-size:12px;">{{ $message }}</div> @enderror
            </div>

            {{-- Frecuencia --}}
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Frecuencia de pago *</label>
                <select name="frecuencia_pago" id="frecuencia_pago"
                        class="{{ $inputClass }} @error('frecuencia_pago') is-invalid @enderror">
                    @foreach(['semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual'] as $value => $text)
                        <option value="{{ $value }}"
                            {{ old('frecuencia_pago', $prestamo->frecuencia_pago ?? '') == $value ? 'selected' : '' }}>
                            {{ $text }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted" style="font-size:11px;">
                    Cada cuánto tiempo debe pagar el cliente
                </small>
                @error('frecuencia_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Número de periodos --}}
            <div class="col-md-4" id="campo_periodos">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Número de periodos *</label>
                <input type="number" name="numero_periodos" id="numero_periodos"
                       value="{{ old('numero_periodos', $prestamo->numero_periodos ?? '') }}"
                       class="{{ $inputClass }}" placeholder="Ej: 4" min="1">
                <small class="text-muted" style="font-size:11px;" id="texto_periodos">
                    ¿Cuántos pagos hará el cliente en total?
                </small>
            </div>

            {{-- Fecha inicio (automática) --}}
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Fecha de inicio</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio"
                       value="{{ old('fecha_inicio', isset($prestamo->fecha_inicio) ? $prestamo->fecha_inicio->format('Y-m-d') : date('Y-m-d')) }}"
                       class="{{ $inputClass }}" readonly
                       style="background:#f8f9f8; color:#888;">
                <small class="text-muted" style="font-size:11px;">Se registra automáticamente hoy</small>
            </div>

            {{-- Fecha vencimiento (automática) --}}
            <div class="col-md-4">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Fecha de vencimiento</label>
                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
                       value="{{ old('fecha_vencimiento', isset($prestamo->fecha_vencimiento) ? $prestamo->fecha_vencimiento->format('Y-m-d') : '') }}"
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
                    <span id="res_interes" style="color:#1a2e1a; font-weight:500;">—</span>
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

    {{-- SECCIÓN 4: MORA --}}
    <div class="col-12">
        <p class="fw-medium mb-1 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            4. Configuración de mora
            <span class="ms-2 fw-normal text-muted" style="font-size:11px;">— opcional</span>
        </p>
        <p class="text-muted mb-3" style="font-size:12px;">
            La mora es un cobro extra cuando el cliente se atrasa. Si no configuras mora, no se cobrará nada por atrasos.
        </p>

        <div class="row g-3">

            {{-- Tipo de mora --}}
            <div class="col-12">
                <label class="d-block mb-2 text-muted" style="{{ $labelStyle }}">Tipo de mora</label>
                <div class="d-flex gap-3 flex-wrap">

                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:220px;" id="card_mora_ninguna">
                        <input type="radio" name="mora_tipo" value="" id="mora_ninguna"
                               {{ old('mora_tipo', $prestamo->mora_tipo ?? '') == '' ? 'checked' : '' }}
                               style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Sin mora</span>
                            <span style="font-size:11px; color:#888;">No se cobra nada por atrasos</span>
                        </div>
                    </label>

                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:220px;" id="card_mora_fija">
                        <input type="radio" name="mora_tipo" value="fija" id="mora_fija"
                               {{ old('mora_tipo', $prestamo->mora_tipo ?? '') == 'fija' ? 'checked' : '' }}
                               style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Monto fijo por día</span>
                            <span style="font-size:11px; color:#888;">
                                Cobra $X por cada día de atraso hasta el siguiente periodo
                            </span>
                        </div>
                    </label>

                    <label class="d-flex align-items-start gap-2 p-3 rounded-3"
                           style="border:0.5px solid #ddd; cursor:pointer; min-width:220px;" id="card_mora_porcentaje">
                        <input type="radio" name="mora_tipo" value="porcentaje" id="mora_porcentaje"
                               {{ old('mora_tipo', $prestamo->mora_tipo ?? '') == 'porcentaje' ? 'checked' : '' }}
                               style="margin-top:2px;">
                        <div>
                            <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Porcentaje por periodo</span>
                            <span style="font-size:11px; color:#888;">
                                Cobra X% del saldo total por cada periodo vencido completo
                            </span>
                        </div>
                    </label>

                </div>
            </div>

            {{-- Campos de mora (se muestran según selección) --}}
            <div class="col-md-4" id="campo_mora_valor" style="display:none;">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}" id="label_mora_valor">Valor</label>
                <input type="number" step="0.01" name="mora_valor"
                       value="{{ old('mora_valor', $prestamo->mora_valor ?? '') }}"
                       class="{{ $inputClass }}" placeholder="0.00" id="mora_valor">
                <small class="text-muted" style="font-size:11px;" id="hint_mora_valor"></small>
            </div>

            <div class="col-md-4" id="campo_dias_gracia" style="display:none;">
                <label class="d-block mb-1 text-muted" style="{{ $labelStyle }}">Días de gracia</label>
                <input type="number" name="dias_gracia"
                       value="{{ old('dias_gracia', $prestamo->dias_gracia ?? 0) }}"
                       class="{{ $inputClass }}" placeholder="0" min="0" id="dias_gracia">
                <small class="text-muted" style="font-size:11px;">
                    Días extra que se le dan al cliente antes de cobrar mora. Pon 0 si no hay gracia.
                </small>
            </div>

        </div>
    </div>

    {{-- SECCIÓN 5: OBSERVACIONES --}}
    <div class="col-12">
        <p class="fw-medium mb-3 pb-2" style="color:#1a2e1a; font-size:13px; border-bottom:0.5px solid #eee;">
            5. Observaciones
            <span class="ms-2 fw-normal text-muted" style="font-size:11px;">— opcional</span>
        </p>
        <textarea name="observaciones" rows="2" class="{{ $inputClass }}"
                  placeholder="Condiciones especiales, acuerdos con el cliente, notas importantes...">{{ old('observaciones', $prestamo->observaciones ?? '') }}</textarea>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // — Referencias —
    const monto        = document.getElementById('monto_original');
    const interes      = document.getElementById('interes_rate');
    const frecuencia   = document.getElementById('frecuencia_pago');
    const periodos     = document.getElementById('numero_periodos');
    const fechaInicio  = document.getElementById('fecha_inicio');
    const fechaFin     = document.getElementById('fecha_vencimiento');
    const resumenBox   = document.getElementById('resumen_box');
    const tipoInteres  = document.getElementById('tipo_interes');
    const tipoPlazo    = document.getElementById('tipo_plazo');

    // — Mora —
    const moraNinguna    = document.getElementById('mora_ninguna');
    const moraFija       = document.getElementById('mora_fija');
    const moraPorcentaje = document.getElementById('mora_porcentaje');
    const campoMoraValor = document.getElementById('campo_mora_valor');
    const campoDiasGracia = document.getElementById('campo_dias_gracia');
    const labelMoraValor = document.getElementById('label_mora_valor');
    const hintMoraValor  = document.getElementById('hint_mora_valor');

    // — Highlight cards tipo —
    function actualizarCardsTipo() {
        document.getElementById('card_interes').style.borderColor = tipoInteres.checked ? '#1f6b21' : '#ddd';
        document.getElementById('card_interes').style.background  = tipoInteres.checked ? '#f0faf0' : '#fff';
        document.getElementById('card_plazo').style.borderColor   = tipoPlazo.checked   ? '#1f6b21' : '#ddd';
        document.getElementById('card_plazo').style.background    = tipoPlazo.checked   ? '#f0faf0' : '#fff';
        calcular();
    }

    tipoInteres.addEventListener('change', actualizarCardsTipo);
    tipoPlazo.addEventListener('change', actualizarCardsTipo);
    actualizarCardsTipo();

    // — Calcular fecha fin —
    function calcularFechaFin() {
        const inicio = new Date(fechaInicio.value);
        const p      = parseInt(periodos.value) || 0;
        const freq   = frecuencia.value;

        if (!p || !freq || !fechaInicio.value) {
            fechaFin.value = '';
            return;
        }

        let fin = new Date(inicio);

        if (freq === 'semanal')        fin.setDate(fin.getDate() + (p * 7));
        else if (freq === 'quincenal') fin.setDate(fin.getDate() + (p * 15));
        else if (freq === 'mensual')   fin.setMonth(fin.getMonth() + p);

        fechaFin.value = fin.toISOString().split('T')[0];

        // Texto dinámico del hint
        const textos = { semanal: 'semanas', quincenal: 'quincenas', mensual: 'meses' };
        document.getElementById('texto_periodos').textContent =
            `El préstamo durará ${p} ${textos[freq] || 'periodos'}`;
    }

    // — Calcular resumen —
    function calcular() {
        const m = parseFloat(monto.value)    || 0;
        const i = parseFloat(interes.value)  || 0;
        const p = parseInt(periodos.value)   || 0;
        const esPlazo = tipoPlazo.checked;

        calcularFechaFin();

        if (m <= 0 || i <= 0) { resumenBox.style.display = 'none'; return; }

        resumenBox.style.display = 'block';

        if (esPlazo && p > 0) {
            const interesTotal = m * (i * p / 100);
            const total        = m + interesTotal;
            const cuota        = total / p;

            document.getElementById('res_capital').textContent  = '$ ' + m.toFixed(2);
            document.getElementById('res_interes').textContent  = '$ ' + interesTotal.toFixed(2);
            document.getElementById('res_total').textContent    = '$ ' + total.toFixed(2);
            document.getElementById('res_cuota').textContent    = '$ ' + cuota.toFixed(2) + ' / periodo';
        } else {
            const interesMensual = m * (i / 100);
            document.getElementById('res_capital').textContent  = '$ ' + m.toFixed(2);
            document.getElementById('res_interes').textContent  = '$ ' + interesMensual.toFixed(2) + ' / periodo';
            document.getElementById('res_total').textContent    = 'Capital no disminuye';
            document.getElementById('res_cuota').textContent    = '$ ' + interesMensual.toFixed(2) + ' / periodo';
        }
    }

    monto.addEventListener('input', calcular);
    interes.addEventListener('input', calcular);
    frecuencia.addEventListener('change', calcular);
    periodos.addEventListener('input', calcular);
    calcular();

    // — Mora —
    function actualizarMora() {
        const esFija       = moraFija.checked;
        const esPorcentaje = moraPorcentaje.checked;
        const hayMora      = esFija || esPorcentaje;

        campoMoraValor.style.display  = hayMora ? 'block' : 'none';
        campoDiasGracia.style.display = hayMora ? 'block' : 'none';

        // Cards mora highlight
        document.getElementById('card_mora_ninguna').style.borderColor    = moraNinguna.checked    ? '#1f6b21' : '#ddd';
        document.getElementById('card_mora_fija').style.borderColor       = esFija                 ? '#e65100' : '#ddd';
        document.getElementById('card_mora_porcentaje').style.borderColor = esPorcentaje           ? '#e65100' : '#ddd';

        if (esFija) {
            labelMoraValor.textContent = 'Monto por día ($)';
            hintMoraValor.textContent  = 'Ej: 20 → cobra $20 por cada día de atraso';
        } else if (esPorcentaje) {
            labelMoraValor.textContent = 'Porcentaje por periodo (%)';
            hintMoraValor.textContent  = 'Ej: 10 → cobra 10% del saldo por cada periodo vencido';
        }
    }

    moraNinguna.addEventListener('change', actualizarMora);
    moraFija.addEventListener('change', actualizarMora);
    moraPorcentaje.addEventListener('change', actualizarMora);
    actualizarMora();

});
</script>