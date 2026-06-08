@php $g = $groups->get('company'); @endphp

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Identidad Visual</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Nombre de la Empresa</label>
                    <input type="text" name="company_name" value="{{ $g?->firstWhere('key','company_name')?->value ?? 'MelPres' }}" class="form-control" placeholder="Ej: MiFinanciera">
                    <p class="setting-description">Aparece en todos los documentos y el sistema</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Eslogan</label>
                    <input type="text" name="company_slogan" value="{{ $g?->firstWhere('key','company_slogan')?->value ?? '' }}" class="form-control" placeholder="Ej: Tu socio financiero de confianza">
                    <p class="setting-description">Frase corta que describe tu empresa</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Color Primario</label>
                    <div class="color-picker-wrapper">
                        <div class="color-preview" id="primaryColorPreview" style="background:{{ $g?->firstWhere('key','company_primary_color')?->value ?? '#1f6b21' }}"></div>
                        <input type="color" id="primaryColorPicker" name="company_primary_color" value="{{ $g?->firstWhere('key','company_primary_color')?->value ?? '#1f6b21' }}">
                        <input type="text" id="primaryColorInput" value="{{ $g?->firstWhere('key','company_primary_color')?->value ?? '#1f6b21' }}" class="form-control" style="width:120px;" readonly>
                    </div>
                    <p class="setting-description">Color principal de botones y elementos destacados</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="setting-item">
                    <label class="setting-label">Color Secundario</label>
                    <div class="color-picker-wrapper">
                        <div class="color-preview" id="secondaryColorPreview" style="background:{{ $g?->firstWhere('key','company_secondary_color')?->value ?? '#e8f5e9' }}"></div>
                        <input type="color" id="secondaryColorPicker" name="company_secondary_color" value="{{ $g?->firstWhere('key','company_secondary_color')?->value ?? '#e8f5e9' }}">
                        <input type="text" id="secondaryColorInput" value="{{ $g?->firstWhere('key','company_secondary_color')?->value ?? '#e8f5e9' }}" class="form-control" style="width:120px;" readonly>
                    </div>
                    <p class="setting-description">Color para fondos y elementos secundarios</p>
                </div>
            </div>
            <div class="col-12">
                <div class="setting-item">
                    <label class="setting-label">Logo de la Empresa</label>
                    @php $currentLogo = $g?->firstWhere('key','company_logo')?->value; @endphp
                    @if($currentLogo)
                        <div class="mb-3 p-3 rounded-3 d-inline-block" style="background:#f8f9f8; border:0.5px solid #e8e8e8;">
                            <img src="{{ asset('storage/'.$currentLogo) }}" alt="Logo actual" style="max-width:180px; max-height:60px; object-fit:contain;">
                            <span class="d-block text-muted mt-1" style="font-size:11px;">Logo actual</span>
                        </div>
                    @endif
                    <input type="file" name="company_logo_file" accept="image/*" class="form-control">
                    <p class="setting-description">PNG, JPG o SVG. Máximo 2MB.</p>
                </div>
            </div>
        </div>

        <div class="preview-box mt-4">
            @if($currentLogo ?? false)
                <img src="{{ asset('storage/'.$currentLogo) }}" alt="Logo" style="max-width:200px; max-height:80px; object-fit:contain; margin-bottom:16px;">
            @endif
            <h6 class="fw-medium mb-2" id="previewCompanyName" style="font-size:24px;">
                {{ $g?->firstWhere('key','company_name')?->value ?? 'MelPres' }}
            </h6>
            <p class="text-muted mb-0" id="previewCompanySlogan" style="font-size:14px;">
                {{ $g?->firstWhere('key','company_slogan')?->value ?? 'Tu socio financiero de confianza' }}
            </p>
            <button type="button" id="previewButton" class="btn btn-sm mt-3"
                    style="background:{{ $g?->firstWhere('key','company_primary_color')?->value ?? '#1f6b21' }}; color:white; padding:8px 20px; border-radius:6px;">
                Vista Previa
            </button>
        </div>
    </div>
</div>

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Información de Contacto</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Teléfono</label>
                    <input type="tel" name="company_phone" value="{{ $g?->firstWhere('key','company_phone')?->value ?? '' }}" class="form-control" placeholder="+52 123 456 7890">
                </div>
            </div>
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">Email</label>
                    <input type="email" name="company_email" value="{{ $g?->firstWhere('key','company_email')?->value ?? '' }}" class="form-control" placeholder="contacto@empresa.com">
                </div>
            </div>
            <div class="col-md-4">
                <div class="setting-item">
                    <label class="setting-label">WhatsApp Business</label>
                    <input type="tel" name="company_whatsapp" value="{{ $g?->firstWhere('key','company_whatsapp')?->value ?? '' }}" class="form-control" placeholder="+52 123 456 7890">
                </div>
            </div>
            <div class="col-12">
                <div class="setting-item">
                    <label class="setting-label">Dirección</label>
                    <textarea name="company_address" class="form-control" rows="2" placeholder="Calle, Número, Colonia, Ciudad, Estado">{{ $g?->firstWhere('key','company_address')?->value ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>