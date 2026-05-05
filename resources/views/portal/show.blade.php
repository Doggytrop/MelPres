<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Préstamo — MelPres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f5f5f5; min-height:100vh;">

    {{-- Header --}}
    <div style="background:#1f6b21; padding:16px 24px;">
        <div class="d-flex justify-content-between align-items-center" style="max-width:800px; margin:0 auto;">
            <a href="{{ route('portal.index') }}" class="text-white text-decoration-none d-flex align-items-center gap-2" style="font-size:14px;">
                ← Mis préstamos
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:rgba(255,255,255,0.15); border:none; color:white; border-radius:6px; padding:5px 12px; font-size:12px; cursor:pointer;">
                    Salir
                </button>
            </form>
        </div>
    </div>

    {{-- Content --}}
    <div style="max-width:800px; margin:0 auto; padding:24px 16px;">

        @php
            $typeColors = [
                'interest' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Interés'],
                'term'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Plazo'],
                'daily'    => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Diario'],
            ];
            $tc = $typeColors[$loan->type];
            $totalPagado = $loan->payments->sum('amount_paid');
        @endphp

        {{-- Info del préstamo --}}
        <div class="bg-white border rounded-3 p-4 mb-4" style="border-color:#e8e8e8 !important;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="px-2 py-1 rounded-2" style="background:{{ $tc['bg'] }}; color:{{ $tc['color'] }}; font-size:11px; font-weight:500;">
                        {{ $tc['label'] }}
                    </span>
                    <span class="fw-medium" style="color:#1a2e1a; font-size:15px;">Préstamo #{{ $loan->id }}</span>
                </div>
                @php
                    $statusBadge = match($loan->status) {
                        'active'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                        'paid'       => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Liquidado'],
                        'overdue'    => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Vencido'],
                        'refinanced' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Refinanciado'],
                    };
                @endphp
                <span class="px-2 py-1 rounded-pill" style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['color'] }}; font-size:11px; font-weight:500;">
                    {{ $statusBadge['label'] }}
                </span>
            </div>

            {{-- Métricas --}}
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <span class="d-block text-muted" style="font-size:11px;">Monto original</span>
                    <span class="fw-medium" style="font-size:16px; color:#1a2e1a;">${{ number_format($loan->original_amount, 2) }}</span>
                </div>
                <div class="col-6 col-md-3">
                    <span class="d-block text-muted" style="font-size:11px;">Saldo pendiente</span>
                    <span class="fw-medium" style="font-size:16px; color:#1f6b21;">${{ number_format($loan->remaining_balance, 2) }}</span>
                </div>
                <div class="col-6 col-md-3">
                    <span class="d-block text-muted" style="font-size:11px;">
                        {{ $loan->type === 'daily' ? 'Pago diario' : 'Pago sugerido' }}
                    </span>
                    <span class="fw-medium" style="font-size:16px; color:{{ $tc['color'] }};">
                        ${{ $loan->type === 'daily' ? number_format($loan->daily_payment, 2) : number_format($loan->suggested_payment, 2) }}
                    </span>
                </div>
                <div class="col-6 col-md-3">
                    <span class="d-block text-muted" style="font-size:11px;">Próximo pago</span>
                    <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">
                        {{ $loan->next_payment_date?->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
            </div>

            {{-- Detalles --}}
            <div class="pt-3" style="border-top:0.5px solid #f0f0f0;">
                @php
                    $details = [
                        'Interés'     => $loan->interest_rate . '% ' . ($loan->type === 'daily' ? 'total' : 'mensual'),
                        'Frecuencia'  => $loan->frequency_label,
                        'Fecha inicio' => $loan->start_date->format('d/m/Y'),
                        'Vencimiento' => $loan->due_date?->format('d/m/Y') ?? '—',
                        'Total pagado' => '$' . number_format($totalPagado, 2),
                    ];

                    if ($loan->accumulated_penalty > 0) {
                        $details['Mora acumulada'] = '$' . number_format($loan->accumulated_penalty, 2);
                    }
                @endphp

                @foreach($details as $key => $val)
                    <div class="d-flex justify-content-between py-2" style="border-bottom:0.5px solid #f5f5f5; font-size:13px;">
                        <span class="text-muted">{{ $key }}</span>
                        <span style="color:{{ $key === 'Mora acumulada' ? '#c0392b' : '#333' }}; font-weight:{{ $key === 'Total pagado' ? '500' : '400' }};">
                            {{ $val }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Historial de pagos --}}
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Historial de pagos</span>
                <span class="text-muted ms-2" style="font-size:12px;">{{ $loan->payments->count() }} pagos</span>
            </div>

            @forelse($loan->payments as $payment)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-medium" style="font-size:14px; color:#1f6b21;">
                                ${{ number_format($payment->amount_paid, 2) }}
                            </span>
                            <span class="text-muted ms-2" style="font-size:12px;">
                                {{ $payment->payment_date instanceof \Carbon\Carbon ? $payment->payment_date->format('d/m/Y') : $payment->payment_date }}
                            </span>
                        </div>
                        <div class="text-end" style="font-size:11px; color:#888;">
                            @if($payment->capital_payment > 0)
                                <span class="d-block">Capital: ${{ number_format($payment->capital_payment, 2) }}</span>
                            @endif
                            @if($payment->interest_payment > 0)
                                <span class="d-block">Interés: ${{ number_format($payment->interest_payment, 2) }}</span>
                            @endif
                            @if($payment->penalty_payment > 0)
                                <span class="d-block" style="color:#c0392b;">Mora: ${{ number_format($payment->penalty_payment, 2) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    Aún no hay pagos registrados.
                </div>
            @endforelse
        </div>

    </div>

</body>
</html>