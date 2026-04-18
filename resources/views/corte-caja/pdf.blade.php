<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de caja — {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size:12px; color:#2c2c2c; }

        .header { padding:24px 40px; border-bottom:3px solid #1f6b21; display:table; width:100%; }
        .header-left  { display:table-cell; vertical-align:middle; }
        .header-right { display:table-cell; vertical-align:middle; text-align:right; }
        .header h1    { font-size:13px; font-weight:bold; color:#1a2e1a; text-transform:uppercase; letter-spacing:1px; }
        .header .sub  { font-size:10px; color:#888; margin-top:3px; }
        .header .fecha { font-size:18px; font-weight:bold; color:#1a2e1a; }

        .body { padding:24px 40px; }

        .metricas { width:100%; border-collapse:collapse; margin-bottom:24px; }
        .metrica  { text-align:center; padding:12px 8px; border:0.5px solid #e8e8e8; background:#f8f9f8; }
        .metrica-label { font-size:9px; text-transform:uppercase; letter-spacing:.06em; color:#888; display:block; margin-bottom:4px; }
        .metrica-valor { font-size:16px; font-weight:bold; color:#1a2e1a; }
        .metrica-valor.verde { color:#1f6b21; }

        .section-title { font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:#1f6b21; font-weight:bold; padding-bottom:5px; border-bottom:1px solid #c8e6c9; margin-bottom:10px; margin-top:20px; }

        .pagos-table { width:100%; border-collapse:collapse; }
        .pagos-table th { background:#1a4a1c; color:#d4f5d4; padding:7px 10px; text-align:left; font-size:10px; text-transform:uppercase; }
        .pagos-table td { padding:6px 10px; border-bottom:0.5px solid #f0f0f0; font-size:11px; }
        .pagos-table tr:nth-child(even) td { background:#fafafa; }
        .pagos-table tfoot td { padding:8px 10px; font-weight:bold; font-size:12px; border-top:1.5px solid #1f6b21; background:#e8f5e9; }

        .footer { margin-top:32px; padding:14px 40px; border-top:1px solid #e8e8e8; display:table; width:100%; }
        .footer-left  { display:table-cell; vertical-align:middle; }
        .footer-right { display:table-cell; vertical-align:middle; text-align:right; }
        .footer p   { font-size:10px; color:#bbb; }
        .footer .dev { font-size:10px; color:#1f6b21; font-weight:bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>Corte de caja</h1>
            <div class="sub">Resumen de pagos del día</div>
        </div>
        <div class="header-right">
            <div class="fecha">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</div>
            <div style="font-size:11px; color:#888; margin-top:4px;">
                Generado el {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div class="body">

        {{-- Métricas --}}
        <table class="metricas">
            <tr>
                <td class="metrica">
                    <span class="metrica-label">Total cobrado</span>
                    <span class="metrica-valor verde">${{ number_format($totalCobrado, 2) }}</span>
                </td>
                <td class="metrica">
                    <span class="metrica-label">Abono capital</span>
                    <span class="metrica-valor">${{ number_format($totalCapital, 2) }}</span>
                </td>
                <td class="metrica">
                    <span class="metrica-label">Interés cobrado</span>
                    <span class="metrica-valor verde">${{ number_format($totalInteres, 2) }}</span>
                </td>
                <td class="metrica">
                    <span class="metrica-label">Mora cobrada</span>
                    <span class="metrica-valor">${{ number_format($totalMora, 2) }}</span>
                </td>
                <td class="metrica">
                    <span class="metrica-label">Total pagos</span>
                    <span class="metrica-valor">{{ $pagos->count() }}</span>
                </td>
            </tr>
        </table>

        {{-- Resumen por asesor --}}
        @if($porAsesor->count() > 1)
            <p class="section-title">Resumen por asesor</p>
            <table class="pagos-table">
                <thead>
                    <tr>
                        <th>Asesor</th>
                        <th>Pagos</th>
                        <th>Total cobrado</th>
                        <th>Interés</th>
                        <th>Mora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($porAsesor as $asesorId => $pagosPorAsesor)
                        <tr>
                            <td>{{ $pagosPorAsesor->first()->registradoPor?->name ?? 'Sin asesor' }}</td>
                            <td>{{ $pagosPorAsesor->count() }}</td>
                            <td>${{ number_format($pagosPorAsesor->sum('monto_pagado'), 2) }}</td>
                            <td>${{ number_format($pagosPorAsesor->sum('abono_interes'), 2) }}</td>
                            <td>${{ number_format($pagosPorAsesor->sum('abono_mora'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Detalle de pagos --}}
        <p class="section-title">Detalle de pagos</p>
        <table class="pagos-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Asesor</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Mora</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos as $i => $pago)
                    <tr>
                        <td style="color:#aaa;">{{ $i + 1 }}</td>
                        <td>{{ $pago->prestamo->cliente?->nombre_completo ?? 'Cliente eliminado' }}</td>
                        <td>{{ $pago->registradoPor?->name ?? '—' }}</td>
                        <td>${{ number_format($pago->abono_capital, 2) }}</td>
                        <td>${{ number_format($pago->abono_interes, 2) }}</td>
                        <td>${{ number_format($pago->abono_mora, 2) }}</td>
                        <td style="font-weight:bold;">${{ number_format($pago->monto_pagado, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTAL</td>
                    <td>${{ number_format($totalCapital, 2) }}</td>
                    <td>${{ number_format($totalInteres, 2) }}</td>
                    <td>${{ number_format($totalMora, 2) }}</td>
                    <td>${{ number_format($totalCobrado, 2) }}</td>
                </tr>
            </tfoot>
        </table>

    </div>

    <div class="footer">
        <div class="footer-left">
            <p>Documento generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</p>
        </div>
        <div class="footer-right">
            <p>Sistema desarrollado por</p>
            <span class="dev">melSolutions</span>
        </div>
    </div>

</body>
</html>