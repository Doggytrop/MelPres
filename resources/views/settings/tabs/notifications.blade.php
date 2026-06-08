@php
    $gn = $groups->get('notifications');
    $gw = $groups->get('whatsapp');
    $waEnabled = $gw?->firstWhere('key','whatsapp_enabled')?->value == '1';
@endphp

{{-- ═══ WHATSAPP: CONEXIÓN ═══ --}}
<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="1.5">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Conexión WhatsApp</span>
        <span class="ms-auto badge" style="background:{{ $waEnabled ? '#e8f5e9' : '#fce4ec' }}; color:{{ $waEnabled ? '#1f6b21' : '#c62828' }}; font-size:11px; font-weight:500;">
            {{ $waEnabled ? '● Activo' : '○ Inactivo' }}
        </span>
    </div>
    <div class="setting-card-body">
        {{-- Toggle global --}}
        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-4" style="background:#f8f9f8; border:1px solid #e8e8e8;">
            <div>
                <span class="fw-medium d-block" style="font-size:13px; color:#1a2e1a;">Activar notificaciones WhatsApp</span>
                <span class="text-muted" style="font-size:12px;">Habilita o deshabilita todos los mensajes de WhatsApp</span>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="whatsapp_enabled" value="1" id="waGlobalToggle" {{ $waEnabled ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <div id="waFields" style="{{ $waEnabled ? '' : 'opacity:0.4; pointer-events:none;' }}">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="setting-item">
                        <label class="setting-label">Token de Acceso</label>
                        <div class="input-group">
                            <input type="password" name="whatsapp_token" id="waToken"
                                   value="{{ $gw?->firstWhere('key','whatsapp_token')?->value ?? '' }}"
                                   class="form-control" placeholder="EAA...">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleTokenVisibility()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eyeIcon">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <p class="setting-description">Token de Meta Cloud API. Genera uno permanente en Meta Business.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="setting-item">
                        <label class="setting-label">Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_number_id"
                               value="{{ $gw?->firstWhere('key','whatsapp_phone_number_id')?->value ?? '' }}"
                               class="form-control" placeholder="Ej: 114441650221...">
                        <p class="setting-description">ID del número en Meta Developers → WhatsApp → Primeros pasos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ WHATSAPP: RECORDATORIO DE PAGO ═══ --}}
<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Recordatorio de Pago</span>
        <label class="toggle-switch ms-auto">
            <input type="checkbox" name="whatsapp_reminder_enabled" value="1"
                   {{ ($gw?->firstWhere('key','whatsapp_reminder_enabled')?->value ?? '1') == '1' ? 'checked' : '' }}>
            <span class="toggle-slider"></span>
        </label>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Hora de Envío</label>
                    <input type="time" name="whatsapp_reminder_time"
                           value="{{ $gw?->firstWhere('key','whatsapp_reminder_time')?->value ?? '09:00' }}"
                           class="form-control">
                    <p class="setting-description">Hora diaria en que se envían los recordatorios</p>
                </div>
            </div>
            <div class="col-12">
                <div class="setting-item">
                    <label class="setting-label">Mensaje</label>
                    <textarea name="whatsapp_reminder_message" class="form-control" rows="4">{{ $gw?->firstWhere('key','whatsapp_reminder_message')?->value ?? "Hola {nombre} 👋\n\nTe recordamos que tu pago de *\${monto}* vence *mañana*.\n\nRealiza tu pago a tiempo para evitar cargos por mora.\n\n_{negocio}_" }}</textarea>
                    @include('settings.tabs._whatsapp_vars')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ WHATSAPP: CONFIRMACIÓN DE PAGO ═══ --}}
<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Confirmación de Pago</span>
        <label class="toggle-switch ms-auto">
            <input type="checkbox" name="whatsapp_confirmation_enabled" value="1"
                   {{ ($gw?->firstWhere('key','whatsapp_confirmation_enabled')?->value ?? '1') == '1' ? 'checked' : '' }}>
            <span class="toggle-slider"></span>
        </label>
    </div>
    <div class="setting-card-body">
        <div class="setting-item">
            <label class="setting-label">Mensaje</label>
            <textarea name="whatsapp_confirmation_message" class="form-control" rows="5">{{ $gw?->firstWhere('key','whatsapp_confirmation_message')?->value ?? "✅ *Pago registrado*\n\nHola {nombre}, confirmamos tu pago:\n\n• Monto: *\${monto}*\n• Saldo restante: *\${saldo}*\n• Próximo pago: *{fecha}*\n\n_{negocio}_" }}</textarea>
            @include('settings.tabs._whatsapp_vars')
        </div>
    </div>
</div>

{{-- ═══ WHATSAPP: ALERTA DE MORA ═══ --}}
<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="1.5">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Alerta de Mora</span>
        <label class="toggle-switch ms-auto">
            <input type="checkbox" name="whatsapp_overdue_enabled" value="1"
                   {{ ($gw?->firstWhere('key','whatsapp_overdue_enabled')?->value ?? '1') == '1' ? 'checked' : '' }}>
            <span class="toggle-slider"></span>
        </label>
    </div>
    <div class="setting-card-body">
        <div class="setting-item">
            <label class="setting-label">Mensaje</label>
            <textarea name="whatsapp_overdue_message" class="form-control" rows="5">{{ $gw?->firstWhere('key','whatsapp_overdue_message')?->value ?? "⚠️ *Aviso de pago vencido*\n\nHola {nombre}, tu préstamo tiene un saldo vencido:\n\n• Mora acumulada: *\${mora}*\n• Saldo pendiente: *\${saldo}*\n\nComunícate con tu asesor para regularizar tu cuenta.\n\n_{negocio}_" }}</textarea>
            @include('settings.tabs._whatsapp_vars')
        </div>
    </div>
</div>

<script>
// Toggle global habilita/deshabilita campos
document.getElementById('waGlobalToggle').addEventListener('change', function() {
    document.getElementById('waFields').style.opacity      = this.checked ? '1'    : '0.4';
    document.getElementById('waFields').style.pointerEvents = this.checked ? 'auto' : 'none';
});

function toggleTokenVisibility() {
    const input = document.getElementById('waToken');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>