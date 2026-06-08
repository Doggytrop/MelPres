@php $g = $groups->get('loans'); @endphp

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Configuración de Préstamos</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Monto Mínimo</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="loans_min_amount" value="{{ $g?->firstWhere('key','loans_min_amount')?->value ?? '1000' }}" class="form-control" step="100">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Monto Máximo</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="loans_max_amount" value="{{ $g?->firstWhere('key','loans_max_amount')?->value ?? '100000' }}" class="form-control" step="1000">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Días de Gracia por Defecto</label>
                    <input type="number" name="loans_grace_days_default" value="{{ $g?->firstWhere('key','loans_grace_days_default')?->value ?? '3' }}" class="form-control" min="0" max="30">
                    <p class="setting-description">Días antes de aplicar penalización</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Tasa de Interés Mínima (%)</label>
                    <input type="number" name="loans_min_interest_rate" value="{{ $g?->firstWhere('key','loans_min_interest_rate')?->value ?? '5' }}" class="form-control" step="0.1" min="0" max="100">
                </div>
            </div>
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Tasa de Interés Máxima (%)</label>
                    <input type="number" name="loans_max_interest_rate" value="{{ $g?->firstWhere('key','loans_max_interest_rate')?->value ?? '30' }}" class="form-control" step="0.1" min="0" max="100">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Penalizaciones</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Tipo de Penalización por Defecto</label>
                    <select name="loans_penalty_default_type" class="form-control">
                        <option value="">Sin penalización</option>
                        <option value="fixed" {{ ($g?->firstWhere('key','loans_penalty_default_type')?->value ?? '') === 'fixed' ? 'selected' : '' }}>Monto fijo por día</option>
                        <option value="percentage_period" {{ ($g?->firstWhere('key','loans_penalty_default_type')?->value ?? '') === 'percentage_period' ? 'selected' : '' }}>% sobre saldo restante por periodo</option>
                        <option value="percentage_balance" {{ ($g?->firstWhere('key','loans_penalty_default_type')?->value ?? '') === 'percentage_balance' ? 'selected' : '' }}>% sobre monto original por periodo</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Valor de Penalización por Defecto</label>
                    <div class="input-group">
                        <span class="input-group-text">$/%</span>
                        <input type="number" name="loans_penalty_default_value" value="{{ $g?->firstWhere('key','loans_penalty_default_value')?->value ?? '50' }}" class="form-control" step="0.01">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Frecuencias de Pago Permitidas</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-3">
            @foreach(['weekly' => ['Semanal','Pagos cada 7 días'], 'biweekly' => ['Quincenal','Pagos cada 15 días'], 'monthly' => ['Mensual','Pagos cada 30 días']] as $key => [$label, $desc])
            <div class="col-md-4">
                <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                    <label class="toggle-switch">
                        <input type="checkbox" name="loans_allow_{{ $key }}" value="1" {{ ($g?->firstWhere('key','loans_allow_'.$key)?->value ?? '1') == '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <div>
                        <span class="fw-medium d-block" style="font-size:13px;">{{ $label }}</span>
                        <span class="text-muted" style="font-size:11px;">{{ $desc }}</span>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
    </div>
</div>