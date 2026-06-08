@php
    $collectors = \App\Models\User::where('role', 'collector')->get();
    $freqOptions = [
        'daily'    => ['label' => 'Diario',    'desc' => 'Cada día'],
        'weekly'   => ['label' => 'Semanal',   'desc' => 'Cada 7 días'],
        'biweekly' => ['label' => 'Quincenal', 'desc' => 'Cada 15 días'],
        'monthly'  => ['label' => 'Mensual',   'desc' => 'Cada mes'],
    ];
@endphp

@if($collectors->isEmpty())
    <div class="bg-white border rounded-3 p-5 text-center" style="border-color:#e8e8e8 !important;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" class="mb-3">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <p class="text-muted mb-2" style="font-size:13px;">No hay cobradores registrados.</p>
        <a href="{{ route('users.create') }}" style="font-size:12px; color:var(--color-primary);">
            Crear un cobrador →
        </a>
    </div>
@else
    @foreach($collectors as $collector)
        @php
            $currentFreqs = $collector->collector_frequencies ?? ['daily','weekly','biweekly','monthly'];
            $overdueDays  = $collector->collector_overdue_days ?? 15;
        @endphp

        <div class="setting-card mb-3">
            <div class="setting-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-medium flex-shrink-0"
                         style="width:36px; height:36px; background:var(--color-secondary); color:var(--color-primary); font-size:14px;">
                        {{ strtoupper(substr($collector->name, 0, 1)) }}
                    </div>
                    <div>
                        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">{{ $collector->name }}</span>
                        <span class="d-block text-muted" style="font-size:11px;">{{ $collector->phone ?? $collector->email }}</span>
                    </div>
                </div>
                <span class="badge" style="background:var(--color-secondary); color:var(--color-primary); font-size:11px;">
                    Cobrador
                </span>
            </div>
            <div class="setting-card-body">
                <form method="POST" action="{{ route('settings.collectors.update', $collector) }}">
                    @csrf

                    <p class="setting-label mb-2">Cobros asignados</p>
                    <p class="setting-description mb-3">Frecuencias de préstamos que este cobrador verá en su panel</p>

                    <div class="row g-2 mb-4">
                        @foreach($freqOptions as $value => $opt)
                            @php $checked = in_array($value, $currentFreqs); @endphp
                            <div class="col-6 col-md-3">
                                <label class="d-flex align-items-center gap-2 p-3 rounded-3"
                                       style="border:1px solid {{ $checked ? 'var(--color-primary)' : '#e8e8e8' }};
                                              background:{{ $checked ? 'var(--color-secondary)' : '#fff' }};
                                              cursor:pointer; transition:.2s;"
                                       id="lbl_{{ $collector->id }}_{{ $value }}">
                                    <input type="checkbox"
                                           name="collector_frequencies[]"
                                           value="{{ $value }}"
                                           {{ $checked ? 'checked' : '' }}
                                           onchange="toggleFreq(this, {{ $collector->id }}, '{{ $value }}')"
                                           style="display:none;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:28px; height:28px; transition:.2s;
                                                background:{{ $checked ? 'var(--color-primary)' : '#e8e8e8' }};"
                                         id="ico_{{ $collector->id }}_{{ $value }}">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                             stroke="{{ $checked ? '#fff' : '#888' }}" stroke-width="2.5"
                                             id="svg_{{ $collector->id }}_{{ $value }}">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="fw-medium d-block" style="font-size:12px; color:#1a2e1a;">{{ $opt['label'] }}</span>
                                        <span style="font-size:10px; color:#888;">{{ $opt['desc'] }}</span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-5">
                            <p class="setting-label mb-1">Días de atraso máximo</p>
                            <p class="setting-description mb-2">0 = solo cobros del día · máximo 90 días</p>
                            <div class="input-group input-group-sm">
                                <input type="number"
                                       name="collector_overdue_days"
                                       value="{{ $overdueDays }}"
                                       class="form-control"
                                       min="0" max="90"
                                       oninput="updateSummary({{ $collector->id }}, this.value)">
                                <span class="input-group-text">días</span>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="p-3 rounded-3" style="background:#f8f9f8; border:0.5px solid #e8e8e8;">
                                <p class="mb-0 text-muted" style="font-size:12px;" id="summary_{{ $collector->id }}">
                                    {{ $overdueDays == 0
                                        ? '📅 Solo verá cobros del día de hoy.'
                                        : '📅 Verá cobros del día + atrasados hasta ' . $overdueDays . ' días atrás.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm"
                                style="background:var(--color-primary); color:white; border-radius:8px; padding:8px 20px; font-size:13px;">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endif

<script>
function toggleFreq(checkbox, collectorId, value) {
    const checked = checkbox.checked;
    document.getElementById('lbl_' + collectorId + '_' + value).style.borderColor = checked ? 'var(--color-primary)' : '#e8e8e8';
    document.getElementById('lbl_' + collectorId + '_' + value).style.background  = checked ? 'var(--color-secondary)' : '#fff';
    document.getElementById('ico_' + collectorId + '_' + value).style.background  = checked ? 'var(--color-primary)' : '#e8e8e8';
    document.getElementById('svg_' + collectorId + '_' + value).setAttribute('stroke', checked ? '#fff' : '#888');
}

function updateSummary(collectorId, val) {
    const days = parseInt(val) || 0;
    document.getElementById('summary_' + collectorId).textContent = days === 0
        ? '📅 Solo verá cobros del día de hoy.'
        : '📅 Verá cobros del día + atrasados hasta ' + days + ' días atrás.';
}
</script>