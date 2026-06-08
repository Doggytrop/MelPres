@php $g = $groups->get('documents'); @endphp

<div class="setting-card">
    <div class="setting-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
        </svg>
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Configuración de PDFs</span>
    </div>
    <div class="setting-card-body">
        <div class="row g-4">
            <div class="col-12">
                <div class="setting-item">
                    <label class="setting-label">Encabezado en PDFs</label>
                    <textarea name="documents_pdf_header" class="form-control" rows="2" placeholder="Texto en la parte superior de los PDFs">{{ $g?->firstWhere('key','documents_pdf_header')?->value ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-12">
                <div class="setting-item">
                    <label class="setting-label">Pie de Página en PDFs</label>
                    <textarea name="documents_pdf_footer" class="form-control" rows="2" placeholder="Texto en la parte inferior de los PDFs">{{ $g?->firstWhere('key','documents_pdf_footer')?->value ?? 'Gracias por su confianza' }}</textarea>
                </div>
            </div>
            <div class="col-md-6">
                <label class="d-flex align-items-center gap-3 p-3 rounded-3" style="border:1px solid #e8e8e8; cursor:pointer;">
                    <label class="toggle-switch">
                        <input type="checkbox" name="documents_include_logo" value="1" {{ ($g?->firstWhere('key','documents_include_logo')?->value ?? '1') == '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="fw-medium" style="font-size:13px;">Incluir logo en contratos</span>
                </label>
            </div>
        </div>
    </div>
</div>