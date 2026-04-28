@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Panel de control</h5>
    <span class="text-muted" style="font-size:13px;">{{ now()->format('l, d \d\e F \d\e Y') }}</span>
</div>

{{-- Métricas principales --}}
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Capital en calle</span>
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:34px; height:34px; background:#e8f5e9;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
            </div>
            <h3 class="fw-medium mb-0" style="color:#1f6b21; font-size:24px;">${{ number_format($totalCapital, 2) }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cobrado hoy</span>
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:34px; height:34px; background:#e8f5e9;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </div>
            </div>
            <h3 class="fw-medium mb-0" style="color:#1a2e1a; font-size:24px;">${{ number_format($totalCobradoHoy, 2) }}</h3>
            <span class="text-muted" style="font-size:12px;">{{ $paymentsHoy->count() }} pagos registrados</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Clientes activos</span>
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:34px; height:34px; background:#e8f5e9;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                    </svg>
                </div>
            </div>
            <h3 class="fw-medium mb-0" style="color:#1a2e1a; font-size:24px;">{{ $totalcustomers }}</h3>
            <span class="text-muted" style="font-size:12px;">{{ $activeLoansCount }} préstamos activos</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="p-4 rounded-3 bg-white border" style="border-color:{{ $loansoverdues > 0 ? '#f5c6c6' : '#e8e8e8' }} !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Vencidos</span>
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:34px; height:34px; background:{{ $loansoverdues > 0 ? '#fdecea' : '#e8f5e9' }};">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $loansoverdues > 0 ? '#c0392b' : '#1f6b21' }}" stroke-width="1.5">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v4M12 16h.01"/>
                    </svg>
                </div>
            </div>
            <h3 class="fw-medium mb-0" style="color:{{ $loansoverdues > 0 ? '#c0392b' : '#1a2e1a' }}; font-size:24px;">{{ $loansoverdues }}</h3>
            <span class="text-muted" style="font-size:12px;">préstamos atrasados</span>
        </div>
    </div>

</div>

{{-- Fila 2: Interés y mora del mes --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés cobrado este mes</span>
                    <h4 class="fw-medium mb-0" style="color:#1f6b21; font-size:22px;">${{ number_format($interestDelMes, 2) }}</h4>
                </div>
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:40px; height:40px; background:#e8f5e9;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-4 rounded-3 bg-white border" style="border-color:#e8e8e8 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora cobrada este mes</span>
                    <h4 class="fw-medium mb-0" style="color:{{ $moraDelMes > 0 ? '#c0392b' : '#1a2e1a' }}; font-size:22px;">${{ number_format($moraDelMes, 2) }}</h4>
                </div>
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:40px; height:40px; background:{{ $moraDelMes > 0 ? '#fdecea' : '#f5f5f5' }};">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $moraDelMes > 0 ? '#c0392b' : '#888' }}" stroke-width="1.5">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gráfica de pagos por mes --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="bg-white border rounded-3 p-4" style="border-color:#e8e8e8 !important;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Pagos recibidos por mes</span>
                <span class="text-muted" style="font-size:12px;">Últimos 6 meses</span>
            </div>
            <canvas id="chartPagos" height="100"></canvas>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Pagos del día --}}
    <div class="col-md-6">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
                 style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Pagos de hoy</span>
                <span class="text-muted" style="font-size:12px;">{{ now()->format('d/m/Y') }}</span>
            </div>

            @forelse($paymentsHoy as $payment)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-medium" style="font-size:13px; color:#1a2e1a;">
                                {{ $payment->loan->customer?->full_name ?? 'Cliente eliminado' }}
                            </p>
                            <p class="mb-0 text-muted" style="font-size:11px;">
                                Asesor: {{ $payment->recordedBy?->name ?? '—' }}
                            </p>
                        </div>
                        <span style="font-size:14px; color:#1f6b21; font-weight:500;">
                            ${{ number_format($payment->amount_paid, 2) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    No hay pagos registrados hoy.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Préstamos vencidos --}}
    <div class="col-md-6">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Préstamos vencidos</span>
            </div>

            @forelse($overdues as $loan)
                <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('loans.show', $loan) }}"
                               style="font-size:13px; color:#1a2e1a; font-weight:500; text-decoration:none;">
                                {{ $loan->customer?->full_name ?? 'Cliente eliminado' }}
                            </a>
                            <p class="mb-0 text-muted" style="font-size:11px;">
                                Mora: ${{ number_format($loan->accumulated_penalty, 2) }}
                            </p>
                        </div>
                        <span style="font-size:13px; color:#c0392b; font-weight:500;">
                            ${{ number_format($loan->remaining_balance, 2) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    No hay préstamos vencidos.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Próximos vencimientos --}}
    <div class="col-12">
        <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
            <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important;">
                <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Vencimientos esta semana</span>
            </div>

            @forelse($proximosVencimientos as $loan)
                <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
                     style="border-color:#f8f8f8 !important;">
                    <div>
                        <a href="{{ route('loans.show', $loan) }}"
                           style="font-size:13px; color:#1a2e1a; font-weight:500; text-decoration:none;">
                            {{ $loan->customer?->full_name ?? 'Cliente eliminado' }}
                        </a>
                        <p class="mb-0 text-muted" style="font-size:11px;">
                            Vence: {{ $loan->next_payment_date->format('d/m/Y') }}
                            — {{ $loan->next_payment_date->diffForHumans() }}
                        </p>
                    </div>
                    <span style="font-size:13px; color:#1f6b21; font-weight:500;">
                        ${{ number_format($loan->remaining_balance, 2) }}
                    </span>
                </div>
            @empty
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    No hay vencimientos esta semana.
                </div>
            @endforelse
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartPagos'), {
    type: 'bar',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: 'Pagos recibidos ($)',
            data: @json($chartData),
            backgroundColor: '#1f6b21',
            borderRadius: 6,
            maxBarThickness: 60
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return '$ ' + ctx.raw.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$ ' + value.toLocaleString('en-US');
                    }
                },
                grid: { color: '#f0f0f0' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

@endsection