@extends('layouts.app')

@section('title', 'Configuración')

@section('content')

<style>
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
.tab-btn:hover { color: var(--color-primary); background: #f8f9f8; }
.tab-btn.active { color: var(--color-primary); border-bottom-color: var(--color-primary); background: transparent; }
.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeIn 0.3s; }
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
.setting-card { background: white; border: 1px solid #e8e8e8; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
.setting-card-header { padding: 16px 20px; background: #f8f9f8; border-bottom: 1px solid #e8e8e8; display: flex; align-items: center; gap: 10px; }
.setting-card-body { padding: 24px; }
.setting-item { margin-bottom: 24px; }
.setting-item:last-child { margin-bottom: 0; }
.setting-label { display: block; font-size: 13px; font-weight: 500; color: #1a2e1a; margin-bottom: 6px; }
.setting-description { font-size: 12px; color: #6b7280; margin-top: 4px; }
.color-picker-wrapper { display: flex; gap: 12px; align-items: center; }
.color-preview { width: 50px; height: 50px; border-radius: 8px; border: 2px solid #e8e8e8; cursor: pointer; transition: transform 0.2s; }
.color-preview:hover { transform: scale(1.05); }
input[type="color"] { width: 0; height: 0; opacity: 0; position: absolute; }
.toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 26px; }
.toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
input:checked + .toggle-slider { background-color: var(--color-primary); }
input:checked + .toggle-slider:before { transform: translateX(22px); }
.preview-box { background: #f8f9f8; border: 2px dashed #e8e8e8; border-radius: 12px; padding: 32px; text-align: center; }
.success-alert { background: var(--color-secondary); border-left: 4px solid var(--color-primary); padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
</style>

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Configuración del Sistema</h5>
    <span class="text-muted" style="font-size:13px;">Personaliza la identidad y comportamiento de tu financiera</span>
</div>

@if(session('success'))
    <div class="success-alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        <div>
            <p class="fw-medium mb-0" style="font-size:14px; color:var(--color-primary);">Configuración guardada exitosamente</p>
        </div>
    </div>
@endif

{{-- Tabs Navigation --}}
<div class="settings-tabs">
    <button type="button" class="tab-btn active" data-tab="company">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
        Empresa
    </button>
    <button type="button" class="tab-btn" data-tab="loans">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        Préstamos
    </button>
    <button type="button" class="tab-btn" data-tab="advisors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Usuarios
    </button>
    <button type="button" class="tab-btn" data-tab="collectors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Cobradores
    </button>
    <button type="button" class="tab-btn" data-tab="notifications">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notificaciones
    </button>
    <button type="button" class="tab-btn" data-tab="documents">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Documentos
    </button>
    <button type="button" class="tab-btn" data-tab="advanced">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        Avanzado
    </button>
</div>

{{-- Tabs dentro del form principal --}}
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="tab-content active" data-tab="company">
        @include('settings.tabs.company')
    </div>
    <div class="tab-content" data-tab="loans">
        @include('settings.tabs.loans')
    </div>
    <div class="tab-content" data-tab="advisors">
        @include('settings.tabs.advisors')
    </div>
    <div class="tab-content" data-tab="notifications">
        @include('settings.tabs.notifications')
    </div>
    <div class="tab-content" data-tab="documents">
        @include('settings.tabs.documents')
    </div>
    <div class="tab-content" data-tab="advanced">
        @include('settings.tabs.advanced')
    </div>

    {{-- Botón Guardar — oculto cuando está activo el tab Cobradores --}}
    <div class="mt-4 d-flex justify-content-end gap-3" id="mainSaveBtn">
        <button type="button" class="btn btn-sm" onclick="window.location.reload()"
                style="background:#e8e8e8; color:#1a2e1a; border-radius:8px; padding:10px 24px;">
            Cancelar
        </button>
        <button type="submit" class="btn btn-sm"
                style="background:var(--color-primary); color:white; border-radius:8px; padding:10px 24px;">
            Guardar Configuración
        </button>
    </div>

</form>

{{-- Tab Cobradores — fuera del form principal porque tiene sus propios forms --}}
<div class="tab-content" data-tab="collectors">
    @include('settings.tabs.collectors')
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.querySelector(`.tab-content[data-tab="${tab}"]`).classList.add('active');

        // Ocultar botón guardar principal cuando el tab es Cobradores
        document.getElementById('mainSaveBtn').style.display =
            tab === 'collectors' ? 'none' : 'flex';
    });
});

// Color primario
const primaryPreview  = document.getElementById('primaryColorPreview');
const primaryPicker   = document.getElementById('primaryColorPicker');
const primaryInput    = document.getElementById('primaryColorInput');
const previewButton   = document.getElementById('previewButton');
primaryPreview.addEventListener('click', () => primaryPicker.click());
primaryPicker.addEventListener('input', function() {
    primaryPreview.style.background = this.value;
    primaryInput.value = this.value;
    previewButton.style.background  = this.value;
});

// Color secundario
const secondaryPreview = document.getElementById('secondaryColorPreview');
const secondaryPicker  = document.getElementById('secondaryColorPicker');
const secondaryInput   = document.getElementById('secondaryColorInput');
secondaryPreview.addEventListener('click', () => secondaryPicker.click());
secondaryPicker.addEventListener('input', function() {
    secondaryPreview.style.background = this.value;
    secondaryInput.value = this.value;
});

// Preview en vivo
document.querySelector('input[name="company_name"]').addEventListener('input', function() {
    document.getElementById('previewCompanyName').textContent = this.value || 'MelPres';
});
document.querySelector('input[name="company_slogan"]').addEventListener('input', function() {
    document.getElementById('previewCompanySlogan').textContent = this.value || 'Tu socio financiero de confianza';
});
</script>

@endsection