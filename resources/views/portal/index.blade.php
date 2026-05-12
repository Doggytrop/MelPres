<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Portal — MelPres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f5f5f5; min-height:100vh;">

    {{-- Header --}}
    <div style="background:var(--color-primary); padding:16px 24px;">
        <div class="d-flex justify-content-between align-items-center" style="max-width:800px; margin:0 auto;">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:32px; height:32px; background:rgba(255,255,255,0.2);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"/>
                    </svg>
                </div>
                <span class="fw-medium text-white" style="font-size:15px;">MelPres</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white" style="font-size:13px;">{{ $customer->full_name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:rgba(255,255,255,0.15); border:none; color:white; border-radius:6px; padding:5px 12px; font-size:12px; cursor:pointer;">
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div style="max-width:800px; margin:0 auto; padding:24px 16px;">

        {{-- Saludo --}}
        <div class="mb-4">
            <h5 class="fw-medium mb-1" style="color:#1a2e1a;">Hola, {{ $customer->first_name }}</h5>
            <p class="text-muted mb-0" style="font-size:13px;">Aquí puedes consultar tus préstamos y pagos</p>
        </div>

        {{-- Préstamos activos --}}
        <div class="mb-4">
            <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:14px;">Préstamos activos</p>

            @forelse($activeLoans as $loan)
                @php
                    $typeColors = [
                        'interest' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Interés'],
                        'term'     => ['bg' => 'var(--color-secondary)', 'color' => 'var(--color-primary)', 'label' => 'Plazo'],
                        'daily'    => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Diario'],
                    ];
                    $tc = $typeColors[$loan->type];
                    $totalPagado = $loan->payments->sum('amount_paid');

                    if ($loan->type === 'interest') {
                        $total = $loan->original_amount;
                        $progreso = $total > 0 ? min(100, round(($loan->payments->sum('capital_payment') / $total) * 100)) : 0;
                    } else {
                        $total = $loan->original_amount + $loan->accrued_interest;
                        $progreso = $total > 0 ? min(100, round(($totalPagado / $total) * 100)) : 0;
                    }
                @endphp

                <a href="{{ route('portal.show', $loan) }}" class="text-decoration-none">
                    <div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important; transition:.2s;"
                         onmouseover="this.style.borderColor='{{ $tc['color'] }}'" onmouseout="this.style.borderColor='#e8e8e8'">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="px-2 py-1 rounded-2" style="background:{{ $tc['bg'] }}; color:{{ $tc['color'] }}; font-size:11px; font-weight:500;">
                                    {{ $tc['label'] }}
                                </span>
                                <span class="text-muted" style="font-size:12px;">Préstamo #{{ $loan->id }}</span>
                            </div>
                            @if($loan->status === 'overdue')
                                <span class="px-2 py-1 rounded-2" style="background:#fdecea; color:#c0392b; font-size:11px; font-weight:500;">
                                    Vencido
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-2" style="background:var(--color-secondary); color:var(--color-primary); font-size:11px; font-weight:500;">
                                    Activo
                                </span>
                            @endif
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <span class="d-block text-muted" style="font-size:11px;">Saldo pendiente</span>
                                <span class="fw-medium" style="font-size:18px; color:var(--color-primary);">${{ number_format($loan->remaining_balance, 2) }}</span>
                            </div>
                            <div class="col-4">
                                <span class="d-block text-muted" style="font-size:11px;">
                                    {{ $loan->type === 'daily' ? 'Pago diario' : 'Pago sugerido' }}
                                </span>
                                <span class="fw-medium" style="font-size:18px; color:{{ $tc['color'] }};">
                                    ${{ $loan->type === 'daily' ? number_format($loan->daily_payment, 2) : number_format($loan->suggested_payment, 2) }}
                                </span>
                            </div>
                            <div class="col-4">
                                <span class="d-block text-muted" style="font-size:11px;">Próximo pago</span>
                                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">
                                    {{ $loan->next_payment_date?->format('d/m/Y') ?? '—' }}
                                </span>
                            </div>
                        </div>

                        {{-- Barra de progreso --}}
                        <div class="d-flex justify-content-between mb-1" style="font-size:11px;">
                            <span class="text-muted">Progreso</span>
                            <span style="color:{{ $tc['color'] }};">{{ $progreso }}%</span>
                        </div>
                        <div class="rounded-pill overflow-hidden" style="height:6px; background:#e8e8e8;">
                            <div class="rounded-pill" style="height:6px; width:{{ $progreso }}%; background:{{ $tc['color'] }};"></div>
                        </div>

                        @if($loan->accumulated_penalty > 0)
                            <div class="mt-2 d-flex align-items-center gap-1" style="font-size:12px; color:#c0392b;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
                                </svg>
                                Mora acumulada: ${{ number_format($loan->accumulated_penalty, 2) }}
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <div class="bg-white border rounded-3 p-4 text-center" style="border-color:#e8e8e8 !important;">
                    <p class="text-muted mb-0" style="font-size:13px;">No tienes préstamos activos.</p>
                </div>
            @endforelse
        </div>

        {{-- Historial de préstamos pagados --}}
        @if($paidLoans->count() > 0)
            <div class="mb-4">
                <p class="fw-medium mb-3" style="color:#1a2e1a; font-size:14px;">Préstamos liquidados</p>

                @foreach($paidLoans as $loan)
                    <a href="{{ route('portal.show', $loan) }}" class="text-decoration-none">
                        <div class="bg-white border rounded-3 p-3 mb-2 d-flex justify-content-between align-items-center"
                             style="border-color:#e8e8e8 !important;">
                            <div>
                                <span class="fw-medium" style="font-size:13px; color:#1a2e1a;">
                                    Préstamo #{{ $loan->id }}
                                </span>
                                <span class="text-muted ms-2" style="font-size:12px;">
                                    ${{ number_format($loan->original_amount, 2) }}
                                </span>
                            </div>
                            <span class="px-2 py-1 rounded-2" style="background:#e3f2fd; color:#1565c0; font-size:11px; font-weight:500;">
                                Liquidado
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>

</body>
</html>