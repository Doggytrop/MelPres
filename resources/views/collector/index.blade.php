<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cobros — MelPres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { box-sizing: border-box; }
        body { background: #f0f2f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; }

        .header-bar { background: linear-gradient(135deg, #1a4a1c 0%, #1f6b21 100%); padding: 20px 24px; }
        .metric-card { background: white; border-radius: 14px; padding: 18px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .loan-card { background: white; border-radius: 14px; padding: 18px; margin-bottom: 12px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all .2s; position: relative; overflow: hidden; }
        .loan-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
        .loan-card.today::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #1f6b21; border-radius: 4px 0 0 4px; }
        .loan-card.overdue::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #c0392b; border-radius: 4px 0 0 4px; }
        .loan-card.collected::before { background: #888; }
        .loan-card.collected { opacity: 0.6; }

        .pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .btn-collect { background: #1f6b21; color: white; border: none; border-radius: 10px; padding: 8px 18px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .15s; }
        .btn-collect:hover { background: #176319; transform: scale(1.02); }
        .btn-collect-danger { background: #c0392b; }
        .btn-collect-danger:hover { background: #a93226; }
        .btn-maps { background: #e3f2fd; color: #1565c0; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; cursor: pointer; transition: all .15s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-maps:hover { background: #bbdefb; color: #0d47a1; }

        .section-title { font-size: 13px; font-weight: 600; letter-spacing: 0.03em; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .collected-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 0.5px solid #f0f0f0; }
        .collected-row:last-child { border-bottom: none; }

        #map { height: 380px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }

        .tab-btn { padding: 8px 16px; border: none; background: #e8e8e8; color: #555; border-radius: 10px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .15s; }
        .tab-btn.active { background: #1f6b21; color: white; }

        @keyframes slideIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: slideIn 0.3s ease forwards; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header-bar">
        <div style="max-width:1200px; margin:0 auto;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:38px; height:38px; background:rgba(255,255,255,0.15);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div>
                        <span class="fw-medium text-white" style="font-size:16px;">Panel de Cobros</span>
                        <span class="d-block" style="font-size:12px; color:rgba(255,255,255,0.6);">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM, YYYY') }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <span class="d-block text-white" style="font-size:13px; font-weight:500;">{{ auth()->user()->name }}</span>
                        <span style="font-size:11px; color:rgba(255,255,255,0.5);">Cobrador</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:white; border-radius:8px; padding:6px 14px; font-size:12px; cursor:pointer; transition:all .15s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div style="max-width:1200px; margin:0 auto; padding:24px 16px;">

        @if(session('success'))
            <div class="rounded-3 p-3 mb-4 d-flex align-items-center gap-2 animate-in"
                 style="background:#e8f5e9; color:#1f6b21; font-size:13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Métricas --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="metric-card text-center">
                    <span class="d-block" style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.05em;">Pendientes hoy</span>
                    <span class="d-block fw-medium" style="font-size:28px; color:#1f6b21;">{{ $totalToday }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="metric-card text-center">
                    <span class="d-block" style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.05em;">Atrasados</span>
                    <span class="d-block fw-medium" style="font-size:28px; color:{{ $totalOverdue > 0 ? '#c0392b' : '#1a2e1a' }};">{{ $totalOverdue }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="metric-card text-center">
                    <span class="d-block" style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.05em;">Por cobrar</span>
                    <span class="d-block fw-medium" style="font-size:28px; color:#1a2e1a;">${{ number_format($totalPending, 2) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="metric-card text-center" style="{{ $totalCollected > 0 ? 'background:#f0faf0;' : '' }}">
                    <span class="d-block" style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.05em;">Cobrado hoy</span>
                    <span class="d-block fw-medium" style="font-size:28px; color:#1f6b21;">${{ number_format($totalCollected, 2) }}</span>
                    @if($collectCount > 0)
                        <span style="font-size:11px; color:#1f6b21;">{{ $collectCount }} cobros</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Mapa --}}
        @if($mapLoans->count() > 0)
            <div class="metric-card mb-4 p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        Mapa de cobros
                    </div>
                    <div class="d-flex align-items-center gap-3" style="font-size:11px;">
                        <span class="d-flex align-items-center gap-1">
                            <span style="width:10px; height:10px; border-radius:50%; background:#1f6b21; display:inline-block;"></span>
                            Hoy
                        </span>
                        <span class="d-flex align-items-center gap-1">
                            <span style="width:10px; height:10px; border-radius:50%; background:#c0392b; display:inline-block;"></span>
                            Atrasado
                        </span>
                    </div>
                </div>
                <div id="map"></div>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="d-flex gap-2 mb-4">
            <button class="tab-btn active" onclick="showTab('pending')">
                Pendientes ({{ $todayLoans->count() + $overdueLoans->count() }})
            </button>
            @if($collectedToday->count() > 0)
                <button class="tab-btn" onclick="showTab('collected')">
                    Cobrados hoy ({{ $collectedToday->count() }})
                </button>
            @endif
        </div>

        {{-- Tab: Pendientes --}}
        <div id="tab_pending">
            <div class="row g-4">

                {{-- Cobros de hoy --}}
                <div class="col-md-6">
                    <div class="section-title" style="color:#1f6b21;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
                        </svg>
                        Cobros de hoy ({{ $todayLoans->count() }})
                    </div>

                    @forelse($todayLoans as $loan)
                        <div class="loan-card today animate-in" style="animation-delay: {{ $loop->index * 0.05 }}s;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="fw-medium" style="font-size:15px; color:#1a2e1a;">{{ $loan->customer->full_name }}</span>
                                    <span class="d-block" style="font-size:12px; color:#888;">{{ $loan->customer->phone ?? '—' }}</span>
                                </div>
                                <span class="pill" style="background:#e8f5e9; color:#1f6b21;">{{ $loan->type_label }}</span>
                            </div>

                            @if($loan->customer->address)
                                <div class="d-flex align-items-start gap-2 mb-3" style="font-size:12px; color:#888;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.5" style="flex-shrink:0; margin-top:2px;">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span>{{ Str::limit($loan->customer->address, 60) }}</span>
                                    @if($loan->customer->latitude && $loan->customer->longitude)
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $loan->customer->latitude }},{{ $loan->customer->longitude }}"
                                           target="_blank" class="btn-maps ms-auto flex-shrink-0">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                <polyline points="15 3 21 3 21 9"/>
                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                            </svg>
                                            Maps
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <span style="font-size:11px; color:#888;">Monto a cobrar</span>
                                    <span class="d-block fw-medium" style="font-size:22px; color:#1f6b21;">
                                        ${{ number_format($loan->suggested_payment, 2) }}
                                    </span>
                                    <span style="font-size:11px; color:#aaa;">Saldo: ${{ number_format($loan->remaining_balance, 2) }}</span>
                                </div>
                                <form method="POST" action="{{ route('collector.collect', $loan) }}" class="d-flex align-items-end gap-2">
                                    @csrf
                                    <div>
                                        <input type="number" step="0.01" name="amount_paid"
                                               value="{{ $loan->suggested_payment }}"
                                               class="form-control form-control-sm" style="width:100px; border-radius:8px; font-size:13px;">
                                    </div>
                                    <input type="hidden" name="notes" value="Cobro en campo">
                                    <button type="submit" class="btn-collect"
                                            onclick="return confirm('¿Cobrar $' + this.form.amount_paid.value + ' a {{ $loan->customer->full_name }}?')">
                                        Cobrar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="metric-card text-center py-4">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5" class="mb-2">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            <p class="mb-0" style="font-size:13px; color:#888;">No hay cobros pendientes para hoy</p>
                        </div>
                    @endforelse
                </div>

                {{-- Atrasados --}}
                <div class="col-md-6">
                    <div class="section-title" style="color:#c0392b;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.5">
                            <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
                        </svg>
                        Atrasados ({{ $overdueLoans->count() }})
                    </div>

                    @forelse($overdueLoans as $loan)
                        @php
                            $daysLate = (int) \Carbon\Carbon::parse($loan->next_payment_date)->diffInDays(now());
                        @endphp
                        <div class="loan-card overdue animate-in" style="animation-delay: {{ $loop->index * 0.05 }}s;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="fw-medium" style="font-size:15px; color:#1a2e1a;">{{ $loan->customer->full_name }}</span>
                                    <span class="d-block" style="font-size:12px; color:#888;">{{ $loan->customer->phone ?? '—' }}</span>
                                </div>
                                <span class="pill" style="background:#fdecea; color:#c0392b;">{{ $daysLate }}d atraso</span>
                            </div>

                            @if($loan->customer->address)
                                <div class="d-flex align-items-start gap-2 mb-3" style="font-size:12px; color:#888;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.5" style="flex-shrink:0; margin-top:2px;">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span>{{ Str::limit($loan->customer->address, 60) }}</span>
                                    @if($loan->customer->latitude && $loan->customer->longitude)
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $loan->customer->latitude }},{{ $loan->customer->longitude }}"
                                           target="_blank" class="btn-maps ms-auto flex-shrink-0">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                <polyline points="15 3 21 3 21 9"/>
                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                            </svg>
                                            Maps
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <span style="font-size:11px; color:#888;">Monto + mora</span>
                                    <span class="d-block fw-medium" style="font-size:22px; color:#c0392b;">
                                        ${{ number_format($loan->suggested_payment + $loan->accumulated_penalty, 2) }}
                                    </span>
                                    @if($loan->accumulated_penalty > 0)
                                        <span style="font-size:11px; color:#c0392b;">Mora: ${{ number_format($loan->accumulated_penalty, 2) }}</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('collector.collect', $loan) }}" class="d-flex align-items-end gap-2">
                                    @csrf
                                    <div>
                                        <input type="number" step="0.01" name="amount_paid"
                                               value="{{ $loan->suggested_payment }}"
                                               class="form-control form-control-sm" style="width:100px; border-radius:8px; font-size:13px;">
                                    </div>
                                    <input type="hidden" name="notes" value="Cobro en campo — {{ $daysLate }}d atraso">
                                    <button type="submit" class="btn-collect btn-collect-danger"
                                            onclick="return confirm('¿Cobrar $' + this.form.amount_paid.value + ' a {{ $loan->customer->full_name }}?')">
                                        Cobrar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="metric-card text-center py-4">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5" class="mb-2">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            <p class="mb-0" style="font-size:13px; color:#888;">Sin cobros atrasados</p>
                        </div>
                    @endforelse
                </div>

            </div>
        </div>

        {{-- Tab: Cobrados hoy --}}
        <div id="tab_collected" style="display:none;">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0" style="color:#1f6b21;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                        Cobrados hoy
                    </div>
                    <span class="pill" style="background:#e8f5e9; color:#1f6b21; font-size:13px;">
                        Total: ${{ number_format($totalCollected, 2) }}
                    </span>
                </div>

                @forelse($collectedToday as $payment)
                    <div class="collected-row">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:32px; height:32px; background:#e8f5e9; flex-shrink:0;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="2">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            </div>
                            <div>
                                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">
                                    {{ $payment->loan->customer->full_name ?? 'Cliente' }}
                                </span>
                                <span class="d-block" style="font-size:12px; color:#888;">
                                    Préstamo #{{ $payment->loan_id }} · {{ $payment->loan->type_label ?? '' }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fw-medium" style="font-size:16px; color:#1f6b21;">
                                ${{ number_format($payment->amount_paid, 2) }}
                            </span>
                            <span class="d-block" style="font-size:11px; color:#888;">
                                {{ $payment->created_at->format('H:i') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4" style="font-size:13px; color:#888;">
                        No has registrado cobros hoy.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Tabs
        function showTab(tab) {
            document.getElementById('tab_pending').style.display = tab === 'pending' ? 'block' : 'none';
            document.getElementById('tab_collected').style.display = tab === 'collected' ? 'block' : 'none';

            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Mapa
        @if($mapLoans->count() > 0)
            const map = L.map('map').setView([29.0729, -110.9559], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const greenIcon = L.divIcon({
                html: `<div style="background:#1f6b21; width:28px; height:28px; border-radius:50%; border:3px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center;">
                    <span style="color:white; font-size:12px; font-weight:bold;">$</span>
                </div>`,
                iconSize: [28, 28], iconAnchor: [14, 14], className: ''
            });

            const redIcon = L.divIcon({
                html: `<div style="background:#c0392b; width:28px; height:28px; border-radius:50%; border:3px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center;">
                    <span style="color:white; font-size:14px; font-weight:bold;">!</span>
                </div>`,
                iconSize: [28, 28], iconAnchor: [14, 14], className: ''
            });

            const bounds = [];

            @foreach($mapLoans as $loan)
                @php
                    $isOverdue = $loan->next_payment_date && $loan->next_payment_date->isPast();
                    $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $loan->customer->latitude . ',' . $loan->customer->longitude;
                @endphp

                const m{{ $loan->id }} = L.marker(
                    [{{ $loan->customer->latitude }}, {{ $loan->customer->longitude }}],
                    { icon: {{ $isOverdue ? 'redIcon' : 'greenIcon' }} }
                ).addTo(map);

                m{{ $loan->id }}.bindPopup(`
                    <div style="font-family:system-ui; min-width:220px; padding:4px;">
                        <strong style="font-size:14px; color:#1a2e1a;">{{ $loan->customer->full_name }}</strong><br>
                        <span style="font-size:12px; color:#888;">{{ $loan->customer->phone ?? '' }}</span><br>
                        <span style="font-size:11px; color:#888;">{{ Str::limit($loan->customer->address ?? '', 50) }}</span>
                        <hr style="margin:8px 0; border-color:#eee;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <span style="font-size:11px; color:#888;">A cobrar</span><br>
                                <strong style="font-size:16px; color:{{ $isOverdue ? '#c0392b' : '#1f6b21' }};">
                                    ${{ number_format($loan->suggested_payment, 2) }}
                                </strong>
                            </div>
                            <a href="{{ $mapsUrl }}" target="_blank"
                               style="background:#1565c0; color:white; padding:6px 12px; border-radius:6px; font-size:11px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                Ir →
                            </a>
                        </div>
                    </div>
                `);

                bounds.push([{{ $loan->customer->latitude }}, {{ $loan->customer->longitude }}]);
            @endforeach

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [40, 40] });
            }
        @endif
    </script>

</body>
</html>