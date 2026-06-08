@php $g = $groups->get('advisors'); @endphp

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Permisos de Asesores</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-3">
            @foreach([
                'advisors_can_view_all_customers'      => 'Ver clientes de otros asesores',
                'advisors_can_edit_all_loans'          => 'Editar préstamos de otros asesores',
                'advisors_can_delete_payments'         => 'Eliminar pagos registrados',
                'advisors_require_approval_restructure'=> 'Requieren aprobación para reestructurar',
            ] as $key => $label)
            <div class="col-md-6">
                <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                    <label class="toggle-switch">
                        <input type="checkbox" name="{{ $key }}" value="1" {{ ($g?->firstWhere('key',$key)?->value ?? '0') == '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="fw-medium" style="font-size:13px;">{{ $label }}</span>
                </label>
            </div>
            @endforeach
        </div>
    </div>
</div>