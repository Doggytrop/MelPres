<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cobros — MelPres</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
    @include('partials.pwa')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @php
        $colorPrimario = $config_sistema['color_primario'] ?? '#1f6b21';
        $colorSecundario = $config_sistema['color_secundario'] ?? '#e8f5e9';
        $mapsDirectionsUrl = static function ($customer): ?string {
            if ($customer->latitude === null || $customer->longitude === null
                || ! is_numeric($customer->latitude) || ! is_numeric($customer->longitude)) {
                return null;
            }

            return 'https://www.google.com/maps/dir/?api=1&destination='
                . $customer->latitude . ',' . $customer->longitude;
        };
    @endphp
    <style>
        :root {
            --color-primary: {{ $colorPrimario }};
            --color-secondary: {{ $colorSecundario }};
        }

        * { box-sizing: border-box; }
        body { background: #f0f2f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; }

        .header-bar { background:#fff; border-bottom:1px solid #e7e9e7; padding:18px 24px; }
        .collector-logo { width:38px; height:38px; display:block; flex:0 0 38px; object-fit:contain; }
        .header-title { color:#1a2e1a; font-size:16px; }
        .header-subtitle { font-size:12px; color:#6b7280; }
        .header-user-name { color:#1a2e1a; font-size:13px; font-weight:500; }
        .header-user-role { font-size:11px; color:#6b7280; }
        .btn-logout { background:#fff; border:1px solid #d8ded8; color:#1a2e1a; border-radius:8px; padding:6px 14px; font-size:12px; cursor:pointer; transition:all .15s; }
        .btn-logout:hover { border-color:var(--color-primary); color:var(--color-primary); background:var(--color-secondary); }
        .metric-card { background: white; border-radius: 14px; padding: 18px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .loan-card { background: white; border-radius: 14px; padding: 18px; margin-bottom: 12px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all .2s; position: relative; overflow: hidden; }
        .loan-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
        .loan-card.today::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--color-primary); border-radius: 4px 0 0 4px; }
        .loan-card.overdue::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #c0392b; border-radius: 4px 0 0 4px; }
        .loan-card.collected::before { background: #888; }
        .loan-card.collected { opacity: 0.6; }

        .pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .btn-collect { background: var(--color-primary); color: white; border: none; border-radius: 10px; padding: 8px 18px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .15s; }
        .btn-collect:hover { background: #176319; transform: scale(1.02); }
        .btn-collect-danger { background: #c0392b; }
        .btn-collect-danger:hover { background: #a93226; }
        .btn-maps { background: #e3f2fd; color: #1565c0; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; cursor: pointer; transition: all .15s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-maps:hover { background: #bbdefb; color: #0d47a1; }

        .collect-modal { position: fixed; inset: 0; z-index: 2000; display: none; align-items: center; justify-content: center; padding: 18px; }
        .collect-modal.is-open { display: flex; }
        .collect-modal-backdrop { position: absolute; inset: 0; background: rgba(17, 24, 17, .58); backdrop-filter: blur(2px); }
        .collect-modal-card { position: relative; width: min(100%, 420px); background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.22); overflow: hidden; animation: modalIn .16s ease-out; }
        .collect-modal-body { padding: 22px 22px 16px; display: flex; gap: 14px; }
        .collect-modal-icon { width: 44px; height: 44px; border-radius: 50%; background: var(--color-secondary); color: var(--color-primary); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .collect-modal-icon.is-danger { background: #fdecea; color: #c0392b; }
        .collect-modal-title { color: #1a2e1a; font-size: 16px; font-weight: 600; margin: 0 0 4px; }
        .collect-modal-message { color: #6b7280; font-size: 13px; line-height: 1.5; margin: 0; }
        .collect-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 0 22px 22px; }
        .collect-modal-btn { border: none; border-radius: 10px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s; }
        .collect-modal-cancel { background: #f3f4f3; color: #4b5563; }
        .collect-modal-cancel:hover { background: #e8ebe8; }
        .collect-modal-confirm { background: var(--color-primary); color: #fff; min-width: 120px; }
        .collect-modal-confirm:hover { filter: brightness(.95); }
        .collect-modal-confirm.is-danger { background: #c0392b; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

        .section-title { font-size: 13px; font-weight: 600; letter-spacing: 0.03em; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .collected-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 0.5px solid #f0f0f0; gap: 10px; }
        .collected-row:last-child { border-bottom: none; }
        .collected-payment-info { min-width: 0; }
        .btn-ticket-action {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e0e0e0;
            background: #fff; color: #555; display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .15s; flex-shrink: 0;
        }
        .btn-ticket-action:hover { background: var(--color-secondary); color: var(--color-primary); border-color: var(--color-primary); }
        .btn-ticket-whatsapp:hover { background: #e6f7ec; color: #25D366; border-color: #25D366; }

        #printableTicket { display: none; width: 58mm; padding: 4mm; color: #000; background: #fff; font-family: 'Courier New', monospace; font-size: 10px; line-height: 1.35; }
        #printableTicket .ticket-center { text-align: center; }
        #printableTicket .ticket-company { font-size: 14px; font-weight: 700; overflow-wrap: anywhere; }
        #printableTicket .ticket-divider { border-top: 1px dashed #000; margin: 3mm 0; }
        #printableTicket .ticket-line { display: flex; justify-content: space-between; gap: 3mm; margin: 1mm 0; }
        #printableTicket .ticket-line span:last-child { text-align: right; overflow-wrap: anywhere; }
        #printableTicket .ticket-total { font-size: 12px; font-weight: 700; }

        @media print {
            @page { size: 58mm auto; margin: 0; }
            body.ticket-printing { margin: 0; background: #fff; }
            body.ticket-printing > *:not(#printableTicket) { display: none !important; }
            body.ticket-printing #printableTicket { display: block !important; }
        }

        #map { height: 380px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }

        .tab-btn { padding: 8px 16px; border: none; background: #e8e8e8; color: #555; border-radius: 10px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .15s; }
        .tab-btn.active { background: var(--color-primary); color: white; }

        .pending-tools { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 18px; }
        .pending-search { position: relative; flex: 1 1 260px; min-width: 0; }
        .pending-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; pointer-events: none; }
        .pending-search input { width: 100%; min-height: 42px; border: 1px solid #d8ded8; border-radius: 10px; padding: 9px 12px 9px 36px; font-size: 13px; color: #1a2e1a; outline: none; }
        .pending-search input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-secondary) 78%, transparent); }
        .pending-filter-list { display: flex; flex: 0 1 auto; flex-wrap: wrap; gap: 6px; padding: 2px; max-width: 100%; }
        .pending-filter { white-space: nowrap; border: 1px solid #d8ded8; background: #fff; color: #555; border-radius: 9px; min-height: 38px; padding: 7px 10px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all .15s; }
        .pending-filter:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .pending-filter.active { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
        .pending-filter-empty { display: none; }
        @media (max-width: 575.98px) {
            .pending-tools { align-items: stretch; }
            .pending-search { flex-basis: 100%; }
            .pending-filter-list { width: 100%; }
        }

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
                    <x-application-logo class="collector-logo" />
                    <div>
                        <span class="header-title fw-medium">Panel de Cobros</span>
                        <span class="header-subtitle d-block">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM, YYYY') }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <span class="header-user-name d-block">{{ auth()->user()->name }}</span>
                        <span class="header-user-role">Cobrador</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">
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
                 style="background:var(--color-secondary); color:var(--color-primary); font-size:13px;">
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
                    <span class="d-block fw-medium" style="font-size:28px; color:var(--color-primary);">{{ $totalToday }}</span>
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
                    <span class="d-block fw-medium" style="font-size:28px; color:var(--color-primary);">${{ number_format($totalCollected, 2) }}</span>
                    @if($collectCount > 0)
                        <span style="font-size:11px; color:var(--color-primary);">{{ $collectCount }} cobros</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Mapa --}}
        @if($mapLoans->count() > 0)
            <div class="metric-card mb-4 p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        Mapa de cobros
                    </div>
                    <div class="d-flex align-items-center gap-3" style="font-size:11px;">
                        <span class="d-flex align-items-center gap-1">
                            <span style="width:10px; height:10px; border-radius:50%; background:var(--color-primary); display:inline-block;"></span>
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
                Pendientes ({{ $pendingLoans->count() }})
            </button>
            <button class="tab-btn" onclick="showTab('collected')">
                Cobrados hoy ({{ $collectedToday->count() }})
            </button>
        </div>

        {{-- Tab: Pendientes --}}
        <div id="tab_pending">
            <div class="section-title" style="color:var(--color-primary);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
                </svg>
                Pendientes ({{ $pendingLoans->count() }})
            </div>

            <div class="pending-tools" data-pending-tools>
                <label class="pending-search" for="pendingSearch">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>
                    </svg>
                    <input id="pendingSearch" type="search" placeholder="Buscar cliente, teléfono o dirección..." autocomplete="off">
                </label>
                <div class="pending-filter-list" role="group" aria-label="Filtrar cobros pendientes">
                    <button type="button" class="pending-filter active" data-pending-filter="all" aria-pressed="true">TODOS ({{ $pendingLoans->count() }})</button>
                    <button type="button" class="pending-filter" data-pending-filter="today" aria-pressed="false">HOY ({{ $totalToday }})</button>
                    <button type="button" class="pending-filter" data-pending-filter="overdue" aria-pressed="false">ATRASADOS ({{ $totalOverdue }})</button>
                </div>
            </div>

            <div class="row g-3">
                @forelse($pendingLoans as $loan)
                    @php
                        $state = $loan->payment_state;
                        $visual = $loan->collector_visual_status;
                        $isOverdue = $visual === 'overdue';
                        $badge = $isOverdue ? 'ATRASADO' : 'HOY';
                        $badgeStyle = match($visual) {
                            'overdue' => 'background:#fdecea; color:#c0392b;',
                            default => 'background:var(--color-secondary); color:var(--color-primary);',
                        };
                        $recommendedAmount = $state->amountToCurrent + (float) $loan->accumulated_penalty + (float) $loan->pending_interest;
                        $cardMapsUrl = $mapsDirectionsUrl($loan->customer);
                        $hasAddress = trim((string) $loan->customer->address) !== '';
                        $searchTerms = implode(' ', [
                            $loan->customer->full_name,
                            $loan->customer->phone,
                            $loan->customer->address,
                            $loan->id,
                            '#' . $loan->id,
                            'prestamo ' . $loan->id,
                        ]);
                    @endphp
                    <div class="col-12 col-md-6" data-pending-card data-pending-status="{{ $visual }}" data-pending-search="{{ $searchTerms }}">
                        <div class="loan-card {{ $isOverdue ? 'overdue' : 'today' }} animate-in" style="animation-delay: {{ $loop->index * 0.04 }}s;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="fw-medium" style="font-size:15px; color:#1a2e1a;">{{ $loan->customer->full_name }}</span>
                                    <span class="d-block" style="font-size:12px; color:#888;">Préstamo #{{ $loan->id }} · {{ $loan->type_label }}</span>
                                </div>
                                <span class="pill" style="{{ $badgeStyle }}">{{ $badge }}</span>
                            </div>

                            @if($hasAddress || $cardMapsUrl)
                                <div class="collector-customer-location d-flex align-items-start gap-2 mb-3" style="font-size:12px; color:#888;">
                                    @if($hasAddress)
                                        <div class="collector-customer-address d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.5" style="flex-shrink:0; margin-top:2px;">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span>{{ \Illuminate\Support\Str::limit($loan->customer->address, 60) }}</span>
                                        </div>
                                    @endif
                                    @if($cardMapsUrl)
                                        <a href="{{ $cardMapsUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="btn-maps collector-card-directions ms-auto flex-shrink-0">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                <polyline points="15 3 21 3 21 9"/>
                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                            </svg>
                                            Ir →
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:11px; color:#777;">
                                <span>Cuota: ${{ number_format($state->baseAmount, 2) }}</span>
                                <span>Fecha pendiente: {{ $state->oldestPendingDate ? \Carbon\Carbon::parse($state->oldestPendingDate)->format('d/m/Y') : '—' }}</span>
                            </div>

                            <div class="mb-3" style="font-size:12px;">
                                @if($state->currentPeriodBalance < $state->baseAmount)
                                    <span class="d-block" style="color:#e65100;">Pendiente de cuota: ${{ number_format($state->currentPeriodBalance, 2) }}</span>
                                @endif
                                @if($state->overduePeriods > 0)
                                    <span class="d-block" style="color:#c0392b;">{{ $state->overduePeriods }} {{ $state->overduePeriods === 1 ? 'cuota vencida' : 'cuotas vencidas' }}</span>
                                    <span class="d-block" style="color:#c0392b;">Pendiente vencido: ${{ number_format($state->overdueAmount, 2) }}</span>
                                @endif
                                <span class="d-block" style="color:#555;">Total exigible: ${{ number_format($state->dueAmount, 2) }}</span>
                                @if($state->paymentCredit > 0)
                                    <span class="d-block" style="color:#2d6a2d;">Crédito disponible: ${{ number_format($state->paymentCredit, 2) }}</span>
                                @endif
                                @if($loan->accumulated_penalty > 0)
                                    <span class="d-block" style="color:#c0392b;">Mora: ${{ number_format($loan->accumulated_penalty, 2) }}</span>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('collector.collect', $loan) }}" class="d-flex align-items-end gap-2"
                                  data-collect-confirm data-customer-name="{{ $loan->customer->full_name }}" data-confirm-tone="{{ $isOverdue ? 'danger' : 'primary' }}">
                                @csrf
                                <div class="flex-grow-1">
                                    <label class="d-block mb-1" style="font-size:11px; color:#888;">Monto recibido</label>
                                    <input type="number" step="0.01" name="amount_paid" value="{{ $recommendedAmount }}"
                                           data-select-amount-once
                                           class="form-control form-control-sm" style="min-width:0; border-radius:8px; font-size:13px;">
                                </div>
                                <input type="hidden" name="notes" value="Cobro en campo · {{ $badge }}">
                                <button type="submit" class="btn-collect {{ $isOverdue ? 'btn-collect-danger' : '' }}">Cobrar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="metric-card text-center py-4">
                            <p class="mb-0" style="font-size:13px; color:#888;">No hay cobros pendientes</p>
                        </div>
                    </div>
                @endforelse
                <div class="col-12 pending-filter-empty" id="pendingFilterEmpty" role="status">
                    <div class="metric-card text-center py-4">
                        <p class="mb-0" style="font-size:13px; color:#888;">No hay cobros que coincidan con esta búsqueda.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Cobrados hoy --}}
        <div id="tab_collected" style="display:none;">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0" style="color:var(--color-primary);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                        Cobrados hoy
                    </div>
                    <span class="pill" style="background:var(--color-secondary); color:var(--color-primary); font-size:13px;">
                        Total: ${{ number_format($totalCollected, 2) }}
                    </span>
                </div>

                @forelse($collectedToday as $payment)
                    <div class="collected-row"
                         data-ticket-empresa="{{ $config_sistema['negocio_nombre'] ?? '' }}"
                         data-ticket-customer="{{ $payment->loan->customer->full_name ?? 'Cliente' }}"
                         data-ticket-phone="{{ $payment->loan->customer->phone ?? '' }}"
                         data-ticket-loan="{{ $payment->loan_id }}"
                         data-ticket-type="{{ $payment->loan->type_label ?? '' }}"
                         data-ticket-amount="{{ number_format((float) $payment->amount_paid, 2, '.', '') }}"
                         data-ticket-balance="{{ number_format((float) ($payment->loan->remaining_balance ?? 0), 2, '.', '') }}"
                         data-ticket-periods="{{ (int) $payment->periods_covered }}"
                         data-ticket-credit="{{ number_format((float) $payment->credit_generated, 2, '.', '') }}"
                         data-ticket-date="{{ $payment->payment_date?->format('d/m/Y') }} {{ $payment->created_at->format('H:i') }}"
                         data-ticket-collector="{{ auth()->user()->name }}"
                         data-ticket-payment-id="{{ $payment->id }}">
                        <div class="collected-payment-info d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:32px; height:32px; background:var(--color-secondary); flex-shrink:0;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
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
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-end">
                                <span class="fw-medium" style="font-size:16px; color:var(--color-primary);">
                                    ${{ number_format($payment->amount_paid, 2) }}
                                </span>
                                <span class="d-block" style="font-size:11px; color:#888;">
                                    {{ $payment->created_at->format('H:i') }}
                                </span>
                            </div>

                            <button type="button" class="btn-ticket-action" onclick="printTicket(this.closest('.collected-row'))" title="Imprimir ticket" aria-label="Imprimir ticket">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <polyline points="6 9 6 2 18 2 18 9"/>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                    <rect x="6" y="14" width="12" height="8"/>
                                </svg>
                            </button>

                            <button type="button" class="btn-ticket-action btn-ticket-whatsapp" onclick="sendWhatsappTicket(this.closest('.collected-row'))" title="Enviar por WhatsApp" aria-label="Enviar por WhatsApp">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.9 11.9L4 20l4.2-1.1a7.9 7.9 0 0 0 3.85 1h.01a7.94 7.94 0 0 0 5.54-13.6zm-5.55 12.2a6.57 6.57 0 0 1-3.36-.92l-.24-.14-2.5.65.67-2.43-.16-.25a6.6 6.6 0 1 1 5.6 3.1zm3.6-4.95c-.2-.1-1.16-.57-1.34-.64s-.31-.1-.44.1-.5.63-.62.76-.23.15-.43.05a5.4 5.4 0 0 1-1.6-.98 5.9 5.9 0 0 1-1.1-1.36c-.11-.2 0-.3.09-.4.09-.1.2-.24.3-.36a1.3 1.3 0 0 0 .2-.33.36.36 0 0 0 0-.35c-.05-.1-.44-1.05-.6-1.44-.16-.38-.32-.33-.44-.33h-.37a.72.72 0 0 0-.52.24 2.2 2.2 0 0 0-.68 1.63 3.8 3.8 0 0 0 .8 2.02 8.7 8.7 0 0 0 3.34 2.95c.47.2.83.32 1.11.41.47.15.9.13 1.24.08.38-.06 1.16-.47 1.32-.93.16-.46.16-.85.11-.93s-.18-.13-.38-.23z"/>
                                </svg>
                            </button>
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

    <section id="printableTicket" aria-hidden="true">
        <div class="ticket-center">
            <div class="ticket-company" data-ticket-field="empresa"></div>
            <div>Comprobante de pago</div>
        </div>
        <div class="ticket-divider"></div>
        <div class="ticket-line"><span>Folio:</span><span data-ticket-field="payment-id"></span></div>
        <div class="ticket-line"><span>Fecha:</span><span data-ticket-field="date"></span></div>
        <div class="ticket-line"><span>Cliente:</span><span data-ticket-field="customer"></span></div>
        <div class="ticket-line"><span>Préstamo:</span><span data-ticket-field="loan"></span></div>
        <div class="ticket-line"><span>Tipo:</span><span data-ticket-field="type"></span></div>
        <div class="ticket-divider"></div>
        <div class="ticket-line ticket-total"><span>Pago:</span><span data-ticket-field="amount"></span></div>
        <div class="ticket-line"><span>Períodos cubiertos:</span><span data-ticket-field="periods"></span></div>
        <div class="ticket-line"><span>Crédito generado:</span><span data-ticket-field="credit"></span></div>
        <div class="ticket-line"><span>Saldo restante:</span><span data-ticket-field="balance"></span></div>
        <div class="ticket-divider"></div>
        <div class="ticket-line"><span>Cobrador:</span><span data-ticket-field="collector"></span></div>
        <div class="ticket-center" style="margin-top:3mm;">Gracias por su pago</div>
    </section>

    <div class="collect-modal" id="collectConfirmModal" aria-hidden="true">
        <div class="collect-modal-backdrop" data-collect-cancel></div>
        <div class="collect-modal-card" role="dialog" aria-modal="true" aria-labelledby="collectConfirmTitle">
            <div class="collect-modal-body">
                <div class="collect-modal-icon" id="collectConfirmIcon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </div>
                <div>
                    <h2 class="collect-modal-title" id="collectConfirmTitle">Confirmar cobro</h2>
                    <p class="collect-modal-message" id="collectConfirmMessage">
                        Revisa el monto antes de registrar el pago.
                    </p>
                </div>
            </div>
            <div class="collect-modal-footer">
                <button type="button" class="collect-modal-btn collect-modal-cancel" data-collect-cancel>
                    Cancelar
                </button>
                <button type="button" class="collect-modal-btn collect-modal-confirm" id="collectConfirmButton">
                    Confirmar cobro
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function ticketRowText(row) {
            const ticket = row.dataset;

            return [
                ticket.ticketEmpresa,
                'COMPROBANTE DE PAGO',
                '',
                `Folio: ${ticket.ticketPaymentId}`,
                `Fecha: ${ticket.ticketDate}`,
                `Cliente: ${ticket.ticketCustomer}`,
                `Préstamo: #${ticket.ticketLoan}`,
                `Tipo: ${ticket.ticketType}`,
                `Pago: $${ticket.ticketAmount}`,
                `Períodos cubiertos: ${ticket.ticketPeriods}`,
                `Crédito generado: $${ticket.ticketCredit}`,
                `Saldo restante: $${ticket.ticketBalance}`,
                `Cobrador: ${ticket.ticketCollector}`,
                '',
                'Gracias por su pago',
            ].join('\n');
        }

        function printTicket(row) {
            if (!row) return;

            const printableTicket = document.getElementById('printableTicket');
            const ticket = row.dataset;
            const values = {
                empresa: ticket.ticketEmpresa,
                'payment-id': ticket.ticketPaymentId,
                date: ticket.ticketDate,
                customer: ticket.ticketCustomer,
                loan: `#${ticket.ticketLoan}`,
                type: ticket.ticketType,
                amount: `$${ticket.ticketAmount}`,
                periods: ticket.ticketPeriods,
                credit: `$${ticket.ticketCredit}`,
                balance: `$${ticket.ticketBalance}`,
                collector: ticket.ticketCollector,
            };

            Object.entries(values).forEach(([field, value]) => {
                const target = printableTicket.querySelector(`[data-ticket-field="${field}"]`);
                if (target) target.textContent = value || '—';
            });

            document.body.classList.add('ticket-printing');
            printableTicket.setAttribute('aria-hidden', 'false');

            window.requestAnimationFrame(() => {
                window.print();
                document.body.classList.remove('ticket-printing');
                printableTicket.setAttribute('aria-hidden', 'true');
            });
        }

        function sendWhatsappTicket(row) {
            if (!row) return;

            let phone = (row.dataset.ticketPhone || '').replace(/\D/g, '');
            if (phone.startsWith('00')) phone = phone.slice(2);
            if (phone.length === 10) phone = `52${phone}`;

            if (!phone) {
                window.alert('El cliente no tiene un teléfono válido para WhatsApp.');
                return;
            }

            const url = `https://wa.me/${phone}?text=${encodeURIComponent(ticketRowText(row))}`;
            window.open(url, '_blank', 'noopener,noreferrer');
        }

        const collectModal = document.getElementById('collectConfirmModal');
        const collectMessage = document.getElementById('collectConfirmMessage');
        const collectButton = document.getElementById('collectConfirmButton');
        const collectIcon = document.getElementById('collectConfirmIcon');
        let collectPendingForm = null;

        function closeCollectModal() {
            collectPendingForm = null;
            collectModal.classList.remove('is-open');
            collectModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.addEventListener('focusin', function(event) {
            const input = event.target.closest('[data-select-amount-once]');
            if (!input || input.dataset.initialSelectionDone === 'true') return;

            input.dataset.initialSelectionDone = 'true';
            window.setTimeout(function() {
                try { input.select(); } catch (error) { /* Algunos móviles no seleccionan type=number. */ }
            }, 0);
        });

        document.addEventListener('submit', function(event) {
            const form = event.target.closest('form[data-collect-confirm]');
            if (!form) return;

            event.preventDefault();
            collectPendingForm = form;

            const amount = form.querySelector('[name="amount_paid"]')?.value || '0';
            const customer = form.dataset.customerName || 'este cliente';
            const tone = form.dataset.confirmTone || 'primary';

            collectMessage.textContent = `¿Registrar cobro de $${amount} a ${customer}?`;
            collectIcon.classList.toggle('is-danger', tone === 'danger');
            collectButton.classList.toggle('is-danger', tone === 'danger');
            collectModal.classList.add('is-open');
            collectModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });

        collectButton.addEventListener('click', function() {
            if (!collectPendingForm) return;
            const form = collectPendingForm;
            collectPendingForm = null;
            collectModal.classList.remove('is-open');
            document.body.style.overflow = '';
            HTMLFormElement.prototype.submit.call(form);
        });

        document.querySelectorAll('[data-collect-cancel]').forEach(function(button) {
            button.addEventListener('click', closeCollectModal);
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && collectModal.classList.contains('is-open')) {
                closeCollectModal();
            }
        });

        // Tabs
        function showTab(tab) {
            document.getElementById('tab_pending').style.display = tab === 'pending' ? 'block' : 'none';
            document.getElementById('tab_collected').style.display = tab === 'collected' ? 'block' : 'none';

            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Búsqueda y filtros de pendientes (solo sobre tarjetas ya renderizadas).
        const pendingSearch = document.getElementById('pendingSearch');
        const pendingCards = Array.from(document.querySelectorAll('[data-pending-card]'));
        const pendingFilters = Array.from(document.querySelectorAll('[data-pending-filter]'));
        const pendingFilterEmpty = document.getElementById('pendingFilterEmpty');
        let activePendingFilter = 'all';

        const normalizePendingText = value => String(value ?? '')
            .toLocaleLowerCase('es-MX')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();

        function updatePendingCards() {
            if (!pendingCards.length) return;

            const query = normalizePendingText(pendingSearch?.value);
            const terms = query.split(/\s+/).filter(Boolean);
            let visibleCount = 0;

            pendingCards.forEach(card => {
                const statusMatches = activePendingFilter === 'all'
                    || card.dataset.pendingStatus === activePendingFilter;
                const searchableText = normalizePendingText(card.dataset.pendingSearch);
                const compactSearchableText = searchableText.replace(/\s+/g, '');
                const searchMatches = terms.every(term => searchableText.includes(term)
                    || compactSearchableText.includes(term.replace(/\s+/g, '')));
                const isVisible = statusMatches && searchMatches;

                card.hidden = !isVisible;
                visibleCount += isVisible ? 1 : 0;
            });

            pendingFilterEmpty.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        pendingSearch?.addEventListener('input', updatePendingCards);
        pendingFilters.forEach(filter => {
            filter.addEventListener('click', () => {
                activePendingFilter = filter.dataset.pendingFilter;

                pendingFilters.forEach(button => {
                    const isActive = button === filter;
                    button.classList.toggle('active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                updatePendingCards();
            });
        });

        // Mapa
        @if($mapLoans->count() > 0)
            const map = L.map('map').setView([29.0729, -110.9559], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const greenIcon = L.divIcon({
                html: `<div style="background:var(--color-primary); width:28px; height:28px; border-radius:50%; border:3px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center;">
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
            const escapePopupHtml = value => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            @foreach($mapLoans as $loan)
                @php
                    $visualStatus = $loan->collector_visual_status;
                    $isOverdue = $visualStatus === 'overdue';
                    $markerIcon = $isOverdue ? 'redIcon' : 'greenIcon';
                    $statusLabel = $isOverdue ? 'ATRASADO' : 'HOY';
                    $mapsUrl = $mapsDirectionsUrl($loan->customer);
                    $popupCustomer = [
                        'name' => $loan->customer->full_name,
                        'phone' => $loan->customer->phone,
                        'address' => trim((string) $loan->customer->address) !== ''
                            ? \Illuminate\Support\Str::limit($loan->customer->address, 50)
                            : null,
                    ];
                @endphp

                const customer{{ $loan->id }} = {{ \Illuminate\Support\Js::from($popupCustomer) }};
                const customerAddress{{ $loan->id }} = customer{{ $loan->id }}.address
                    ? `<span class="collector-popup-address" style="font-size:11px; color:#888;">${escapePopupHtml(customer{{ $loan->id }}.address)}</span><br>`
                    : '';

                const m{{ $loan->id }} = L.marker(
                    [{{ $loan->customer->latitude }}, {{ $loan->customer->longitude }}],
                    { icon: {{ $markerIcon }} }
                ).addTo(map);

                m{{ $loan->id }}.bindPopup(`
                    <div style="font-family:system-ui; min-width:220px; padding:4px;">
                        <strong style="font-size:14px; color:#1a2e1a;">${escapePopupHtml(customer{{ $loan->id }}.name)}</strong><br>
                        <span style="font-size:12px; color:#888;">${escapePopupHtml(customer{{ $loan->id }}.phone)}</span><br>
                        ${customerAddress{{ $loan->id }}}
                        <span style="font-size:11px; color:{{ $isOverdue ? '#c0392b' : 'var(--color-primary)' }}; font-weight:600;">{{ $statusLabel }}</span><br>
                        <hr style="margin:8px 0; border-color:#eee;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <span style="font-size:11px; color:#888;">A cobrar</span><br>
                                <strong style="font-size:16px; color:{{ $isOverdue ? '#c0392b' : 'var(--color-primary)' }};">
                                    ${{ number_format($loan->payment_state->amountToCurrent, 2) }}
                                </strong>
                            </div>
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"
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
