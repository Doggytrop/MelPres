@php $g = $groups->get('advanced'); @endphp

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Seguridad</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Tiempo de Sesión (minutos)</label>
                    <input type="number" name="advanced_session_timeout" value="{{ $g?->firstWhere('key','advanced_session_timeout')?->value ?? '120' }}" class="form-control" min="5" max="480">
                    <p class="setting-description">La sesión expirará después de este tiempo</p>
                </div>
            </div>
            <div class="col-md-6">
                <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                    <label class="toggle-switch">
                        <input type="checkbox" name="advanced_enable_audit_log" value="1" {{ ($g?->firstWhere('key','advanced_enable_audit_log')?->value ?? '1') == '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="fw-medium" style="font-size:13px;">Bitácora de cambios importantes</span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Regional</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Símbolo de Moneda</label>
                    <input type="text" name="advanced_currency_symbol" value="{{ $g?->firstWhere('key','advanced_currency_symbol')?->value ?? '$' }}" class="form-control" maxlength="3">
                </div>
            </div>
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Código de Moneda</label>
                    <input type="text" name="advanced_currency_code" value="{{ $g?->firstWhere('key','advanced_currency_code')?->value ?? 'MXN' }}" class="form-control" maxlength="3">
                </div>
            </div>
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Zona Horaria</label>
                    <select name="advanced_timezone" class="form-control">
                        <option value="America/Mexico_City" {{ ($g?->firstWhere('key','advanced_timezone')?->value ?? 'America/Mexico_City') === 'America/Mexico_City' ? 'selected' : '' }}>Ciudad de México</option>
                        <option value="America/Cancun"      {{ ($g?->firstWhere('key','advanced_timezone')?->value ?? '') === 'America/Cancun'      ? 'selected' : '' }}>Cancún</option>
                        <option value="America/Tijuana"     {{ ($g?->firstWhere('key','advanced_timezone')?->value ?? '') === 'America/Tijuana'     ? 'selected' : '' }}>Tijuana</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>