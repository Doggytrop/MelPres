@extends('layouts.app')

@section('title', 'Configuración')

@section('content')

<style>
/* Tabs Styling */
.settings-tabs {
    display: flex;
    border-bottom: 2px solid #e8e8e8;
    gap: 0;
    overflow-x: auto;
    margin-bottom: 24px;
}

.tab-btn {
    padding: 14px 20px;
    border: none;
    background: transparent;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn:hover {
    color: #1f6b21;
    background: #f8f9f8;
}

.tab-btn.active {
    color: #1f6b21;
    border-bottom-color: #1f6b21;
    background: transparent;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Settings Card */
.setting-card {
    background: white;
    border: 1px solid #e8e8e8;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
}

.setting-card-header {
    padding: 16px 20px;
    background: #f8f9f8;
    border-bottom: 1px solid #e8e8e8;
    display: flex;
    align-items: center;
    gap: 10px;
}

.setting-card-body {
    padding: 24px;
}

.setting-item {
    margin-bottom: 24px;
}

.setting-item:last-child {
    margin-bottom: 0;
}

.setting-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #1a2e1a;
    margin-bottom: 6px;
}

.setting-description {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

/* Color Picker */
.color-picker-wrapper {
    display: flex;
    gap: 12px;
    align-items: center;
}

.color-preview {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    border: 2px solid #e8e8e8;
    cursor: pointer;
    transition: transform 0.2s;
}

.color-preview:hover {
    transform: scale(1.05);
}

input[type="color"] {
    width: 0;
    height: 0;
    opacity: 0;
    position: absolute;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #1f6b21;
}

input:checked + .toggle-slider:before {
    transform: translateX(22px);
}

/* Preview Section */
.preview-box {
    background: #f8f9f8;
    border: 2px dashed #e8e8e8;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
}

.preview-logo {
    max-width: 200px;
    max-height: 80px;
    margin: 0 auto 16px;
}

/* Success Alert */
.success-alert {
    background: #e8f5e9;
    border-left: 4px solid #1f6b21;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}
</style>

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Configuración del Sistema</h5>
    <span class="text-muted" style="font-size:13px;">Personaliza la identidad y comportamiento de tu financiera</span>
</div>

@if(session('success'))
    <div class="success-alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="2">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        <div>
            <p class="fw-medium mb-0" style="font-size:14px; color:#1f6b21;">Configuración guardada exitosamente</p>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf

    {{-- Tabs Navigation --}}
    <div class="settings-tabs">
        <button type="button" class="tab-btn active" data-tab="company">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 9h18"/>
            </svg>
            Empresa
        </button>
        <button type="button" class="tab-btn" data-tab="loans">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <path d="M2 10h20"/>
            </svg>
            Préstamos
        </button>
        <button type="button" class="tab-btn" data-tab="advisors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Usuarios
        </button>
        <button type="button" class="tab-btn" data-tab="notifications">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            Notificaciones
        </button>
        <button type="button" class="tab-btn" data-tab="documents">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            Documentos
        </button>
        <button type="button" class="tab-btn" data-tab="advanced">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            Avanzado
        </button>
    </div>

    {{-- TAB 1: EMPRESA --}}
    <div class="tab-content active" data-tab="company">
        
        {{-- Identidad Visual --}}
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Identidad Visual</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Nombre de la Empresa</label>
                            <input type="text" name="company_name" value="{{ $groups->get('company')->firstWhere('key', 'company_name')->value ?? 'MelPres' }}" 
                                   class="form-control" placeholder="Ej: MiFinanciera">
                            <p class="setting-description">Este nombre aparecerá en todos los documentos y el sistema</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Slogan</label>
                            <input type="text" name="company_slogan" value="{{ $groups->get('company')->firstWhere('key', 'company_slogan')->value ?? '' }}"
                                   class="form-control" placeholder="Ej: Tu socio financiero de confianza">
                            <p class="setting-description">Frase corta que describe tu empresa</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Color Primario</label>
                            <div class="color-picker-wrapper">
                                <div class="color-preview" id="primaryColorPreview" style="background: {{ $groups->get('company')->firstWhere('key', 'company_primary_color')->value ?? '#1f6b21' }}"></div>
                                <input type="color" id="primaryColorPicker" name="company_primary_color" value="{{ $groups->get('company')->firstWhere('key', 'company_primary_color')->value ?? '#1f6b21' }}">
                                <input type="text" id="primaryColorInput" value="{{ $groups->get('company')->firstWhere('key', 'company_primary_color')->value ?? '#1f6b21' }}" 
                                       class="form-control" style="width:120px;" readonly>
                            </div>
                            <p class="setting-description">Color principal de botones y elementos destacados</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Color Secundario</label>
                            <div class="color-picker-wrapper">
                                <div class="color-preview" id="secondaryColorPreview" style="background: {{ $groups->get('company')->firstWhere('key', 'company_secondary_color')->value ?? '#e8f5e9' }}"></div>
                                <input type="color" id="secondaryColorPicker" name="company_secondary_color" value="{{ $groups->get('company')->firstWhere('key', 'company_secondary_color')->value ?? '#e8f5e9' }}">
                                <input type="text" id="secondaryColorInput" value="{{ $groups->get('company')->firstWhere('key', 'company_secondary_color')->value ?? '#e8f5e9' }}" 
                                       class="form-control" style="width:120px;" readonly>
                            </div>
                            <p class="setting-description">Color para fondos y elementos secundarios</p>
                        </div>
                    </div>
                </div>

                {{-- Vista Previa --}}
                <div class="preview-box mt-4">
                    <h6 class="fw-medium mb-2" id="previewCompanyName" style="font-size:24px;">MelPres</h6>
                    <p class="text-muted mb-0" id="previewCompanySlogan" style="font-size:14px;">Tu socio financiero de confianza</p>
                    <button type="button" id="previewButton" class="btn btn-sm mt-3" style="background:#1f6b21; color:white; padding:8px 20px; border-radius:6px;">
                        Vista Previa
                    </button>
                </div>
            </div>
        </div>

        {{-- Información de Contacto --}}
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Información de Contacto</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Teléfono</label>
                            <input type="tel" name="company_phone" value="{{ $groups->get('company')->firstWhere('key', 'company_phone')->value ?? '' }}"
                                   class="form-control" placeholder="+52 123 456 7890">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Email</label>
                            <input type="email" name="company_email" value="{{ $groups->get('company')->firstWhere('key', 'company_email')->value ?? '' }}"
                                   class="form-control" placeholder="contacto@empresa.com">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">WhatsApp Business</label>
                            <input type="tel" name="company_whatsapp" value="{{ $groups->get('company')->firstWhere('key', 'company_whatsapp')->value ?? '' }}"
                                   class="form-control" placeholder="+52 123 456 7890">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="setting-item">
                            <label class="setting-label">Dirección</label>
                            <textarea name="company_address" class="form-control" rows="2" placeholder="Calle, Número, Colonia, Ciudad, Estado">{{ $groups->get('company')->firstWhere('key', 'company_address')->value ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TAB 2: PRÉSTAMOS --}}
    <div class="tab-content" data-tab="loans">
        
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
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
                                <input type="number" name="loans_min_amount" value="{{ $groups->get('loans')->firstWhere('key', 'loans_min_amount')->value ?? '1000' }}"
                                       class="form-control" step="100">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Monto Máximo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="loans_max_amount" value="{{ $groups->get('loans')->firstWhere('key', 'loans_max_amount')->value ?? '100000' }}"
                                       class="form-control" step="1000">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Días de Gracia</label>
                            <input type="number" name="loans_grace_days_default" value="{{ $groups->get('loans')->firstWhere('key', 'loans_grace_days_default')->value ?? '3' }}"
                                   class="form-control" min="0" max="30">
                            <p class="setting-description">Días antes de aplicar penalización</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Tasa de Interés Mínima (%)</label>
                            <input type="number" name="loans_min_interest_rate" value="{{ $groups->get('loans')->firstWhere('key', 'loans_min_interest_rate')->value ?? '5' }}"
                                   class="form-control" step="0.1" min="0" max="100">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Tasa de Interés Máxima (%)</label>
                            <input type="number" name="loans_max_interest_rate" value="{{ $groups->get('loans')->firstWhere('key', 'loans_max_interest_rate')->value ?? '30' }}"
                                   class="form-control" step="0.1" min="0" max="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
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
                                <option value="fixed" {{ ($groups->get('loans')->firstWhere('key', 'loans_penalty_default_type')->value ?? '') === 'fixed' ? 'selected' : '' }}>Monto fijo por día</option>
                                <option value="percentage" {{ ($groups->get('loans')->firstWhere('key', 'loans_penalty_default_type')->value ?? '') === 'percentage' ? 'selected' : '' }}>Porcentaje por periodo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Valor de Penalización</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="loans_penalty_default_value" value="{{ $groups->get('loans')->firstWhere('key', 'loans_penalty_default_value')->value ?? '50' }}"
                                       class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Frecuencias de Pago Permitidas</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="loans_allow_weekly" value="1" {{ ($groups->get('loans')->firstWhere('key', 'loans_allow_weekly')->value ?? '1') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <span class="fw-medium d-block" style="font-size:13px;">Semanal</span>
                                <span class="text-muted" style="font-size:11px;">Pagos cada 7 días</span>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="loans_allow_biweekly" value="1" {{ ($groups->get('loans')->firstWhere('key', 'loans_allow_biweekly')->value ?? '1') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <span class="fw-medium d-block" style="font-size:13px;">Quincenal</span>
                                <span class="text-muted" style="font-size:11px;">Pagos cada 15 días</span>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="loans_allow_monthly" value="1" {{ ($groups->get('loans')->firstWhere('key', 'loans_allow_monthly')->value ?? '1') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <span class="fw-medium d-block" style="font-size:13px;">Mensual</span>
                                <span class="text-muted" style="font-size:11px;">Pagos cada 30 días</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TAB 3: USUARIOS Y PERMISOS --}}
    <div class="tab-content" data-tab="advisors">
        
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Permisos de Asesores</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-3">
                    @foreach(['advisors_can_view_all_customers' => 'Ver clientes de otros asesores', 'advisors_can_edit_all_loans' => 'Editar préstamos de otros asesores', 'advisors_can_delete_payments' => 'Eliminar pagos registrados', 'advisors_require_approval_restructure' => 'Requieren aprobación para reestructurar'] as $key => $label)
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="{{ $key }}" value="1" {{ ($groups->get('advisors')->firstWhere('key', $key)->value ?? '0') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="fw-medium" style="font-size:13px;">{{ $label }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- TAB 4: NOTIFICACIONES --}}
    <div class="tab-content" data-tab="notifications">
        
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Notificaciones WhatsApp</span>
            </div>
            <div class="setting-card-body">
                <div class="alert alert-info border-0 mb-4" style="background:#e3f2fd; color:#1976d2;">
                    <small>Estas configuraciones se activarán cuando integres WhatsApp API</small>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Recordatorio de Pago (días antes)</label>
                            <input type="number" name="notifications_payment_reminder_days" value="{{ $groups->get('notifications')->firstWhere('key', 'notifications_payment_reminder_days')->value ?? '3' }}"
                                   class="form-control" min="0" max="30">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Aviso de Mora (días después)</label>
                            <input type="number" name="notifications_overdue_alert_days" value="{{ $groups->get('notifications')->firstWhere('key', 'notifications_overdue_alert_days')->value ?? '1' }}"
                                   class="form-control" min="0" max="30">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="notifications_payment_confirmation" value="1" {{ ($groups->get('notifications')->firstWhere('key', 'notifications_payment_confirmation')->value ?? '1') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="fw-medium" style="font-size:13px;">Confirmación de pago recibido</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="notifications_welcome_customer" value="1" {{ ($groups->get('notifications')->firstWhere('key', 'notifications_welcome_customer')->value ?? '1') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="fw-medium" style="font-size:13px;">Bienvenida a nuevo cliente</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TAB 5: DOCUMENTOS --}}
    <div class="tab-content" data-tab="documents">
        
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Configuración de PDFs</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="setting-item">
                            <label class="setting-label">Encabezado en PDFs</label>
                            <textarea name="documents_pdf_header" class="form-control" rows="2" placeholder="Texto que aparecerá en la parte superior de los PDFs">{{ $groups->get('documents')->firstWhere('key', 'documents_pdf_header')->value ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="setting-item">
                            <label class="setting-label">Pie de Página en PDFs</label>
                            <textarea name="documents_pdf_footer" class="form-control" rows="2" placeholder="Texto que aparecerá en la parte inferior de los PDFs">{{ $groups->get('documents')->firstWhere('key', 'documents_pdf_footer')->value ?? 'Gracias por su confianza' }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="documents_include_logo" value="1" {{ ($groups->get('documents')->firstWhere('key', 'documents_include_logo')->value ?? '1') == '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="fw-medium" style="font-size:13px;">Incluir logo en contratos</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TAB 6: AVANZADO --}}
    <div class="tab-content" data-tab="advanced">
        
        <div class="setting-card">
            <div class="setting-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Seguridad</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="setting-item">
                            <label class="setting-label">Tiempo de Sesión (minutos)</label>
                            <input type="number" name="advanced_session_timeout" value="{{ $groups->get('advanced')->firstWhere('key', 'advanced_session_timeout')->value ?? '120' }}"
                                   class="form-control" min="5" max="480">
                            <p class="setting-description">La sesión expirará después de este tiempo</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="advanced_enable_audit_log" value="1" {{ ($groups->get('advanced')->firstWhere('key', 'advanced_enable_audit_log')->value ?? '1') == '1' ? 'checked' : '' }}>
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
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Regional</span>
            </div>
            <div class="setting-card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Símbolo de Moneda</label>
                            <input type="text" name="advanced_currency_symbol" value="{{ $groups->get('advanced')->firstWhere('key', 'advanced_currency_symbol')->value ?? '$' }}"
                                   class="form-control" maxlength="3">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Código de Moneda</label>
                            <input type="text" name="advanced_currency_code" value="{{ $groups->get('advanced')->firstWhere('key', 'advanced_currency_code')->value ?? 'MXN' }}"
                                   class="form-control" maxlength="3">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="setting-item">
                            <label class="setting-label">Zona Horaria</label>
                            <select name="advanced_timezone" class="form-control">
                                <option value="America/Mexico_City" {{ ($groups->get('advanced')->firstWhere('key', 'advanced_timezone')->value ?? 'America/Mexico_City') === 'America/Mexico_City' ? 'selected' : '' }}>Ciudad de México</option>
                                <option value="America/Cancun" {{ ($groups->get('advanced')->firstWhere('key', 'advanced_timezone')->value ?? '') === 'America/Cancun' ? 'selected' : '' }}>Cancún</option>
                                <option value="America/Tijuana" {{ ($groups->get('advanced')->firstWhere('key', 'advanced_timezone')->value ?? '') === 'America/Tijuana' ? 'selected' : '' }}>Tijuana</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Botón Guardar --}}
    <div class="mt-4 d-flex justify-content-end gap-3">
        <button type="button" class="btn btn-sm" onclick="window.location.reload()" 
                style="background:#e8e8e8; color:#1a2e1a; border-radius:8px; padding:10px 24px;">
            Cancelar
        </button>
        <button type="submit" class="btn btn-sm"
                style="background:#1f6b21; color:white; border-radius:8px; padding:10px 24px;">
            Guardar Configuración
        </button>
    </div>

</form>

<script>
// Tabs functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;
        
        // Remove active from all
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active to clicked
        this.classList.add('active');
        document.querySelector(`.tab-content[data-tab="${tab}"]`).classList.add('active');
    });
});

// Color picker for primary color
const primaryPreview = document.getElementById('primaryColorPreview');
const primaryPicker = document.getElementById('primaryColorPicker');
const primaryInput = document.getElementById('primaryColorInput');
const previewButton = document.getElementById('previewButton');

primaryPreview.addEventListener('click', () => primaryPicker.click());

primaryPicker.addEventListener('input', function() {
    const color = this.value;
    primaryPreview.style.background = color;
    primaryInput.value = color;
    previewButton.style.background = color;
});

// Color picker for secondary color
const secondaryPreview = document.getElementById('secondaryColorPreview');
const secondaryPicker = document.getElementById('secondaryColorPicker');
const secondaryInput = document.getElementById('secondaryColorInput');

secondaryPreview.addEventListener('click', () => secondaryPicker.click());

secondaryPicker.addEventListener('input', function() {
    const color = this.value;
    secondaryPreview.style.background = color;
    secondaryInput.value = color;
});

// Live preview for company name and slogan
document.querySelector('input[name="company_name"]').addEventListener('input', function() {
    document.getElementById('previewCompanyName').textContent = this.value || 'MelPres';
});

document.querySelector('input[name="company_slogan"]').addEventListener('input', function() {
    document.getElementById('previewCompanySlogan').textContent = this.value || 'Tu socio financiero de confianza';
});
</script>

@endsection