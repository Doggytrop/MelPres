<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Préstamo #{{ $loan->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #2c2c2c;
            background: #fff;
        }

        /* — Header — */
        .header {
            padding: 28px 40px;
            border-bottom: 3px solid #1f6b21;
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .header h1    { font-size: 13px; font-weight: bold; color: #1a2e1a; text-transform: uppercase; letter-spacing: 1px; }
        .header .sub  { font-size: 10px; color: #888; margin-top: 3px; }
        .folio        { font-size: 11px; color: #888; }
        .folio span   { font-size: 18px; font-weight: bold; color: #1a2e1a; display: block; }
        .badge {
            display: inline-block;
            background: #e8f5e9;
            color: #1f6b21;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 6px;
            letter-spacing: .5px;
            border: 0.5px solid #c8e6c9;
        }

        /* — Body — */
        .body { padding: 28px 40px; }

        /* — Sección — */
        .section { margin-bottom: 22px; }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1f6b21;
            font-weight: bold;
            padding-bottom: 5px;
            border-bottom: 1px solid #c8e6c9;
            margin-bottom: 10px;
        }

        /* — Info table — */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td {
            padding: 5px 0;
            border-bottom: 0.5px solid #f5f5f5;
            vertical-align: top;
        }
        .info-table td:first-child {
            color: #888;
            width: 42%;
            font-size: 11px;
        }
        .info-table td:last-child {
            font-weight: 500;
            color: #1a2e1a;
            font-size: 12px;
        }

        /* — Dos columnas — */
        .two-col { display: table; width: 100%; margin-bottom: 22px; }
        .col-left  { display: table-cell; width: 48%; vertical-align: top; padding-right: 20px; }
        .col-right { display: table-cell; width: 48%; vertical-align: top; padding-left: 20px; border-left: 0.5px solid #eee; }

        /* — Métricas — */
        .metricas { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .metrica {
            text-align: center;
            padding: 14px 8px;
            border: 0.5px solid #e8e8e8;
            background: #f8f9f8;
        }
        .metrica-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #888;
            display: block;
            margin-bottom: 5px;
        }
        .metrica-valor       { font-size: 16px; font-weight: bold; color: #1a2e1a; }
        .metrica-valor.verde { color: #1f6b21; }
        .metrica-valor.azul  { color: #1565c0; }
        .metrica-valor.rojo  { color: #c0392b; }

        /* — Tabla payments — */
        .payments-table { width: 100%; border-collapse: collapse; }
        .payments-table th {
            background: #1a4a1c;
            color: #d4f5d4;
            padding: 7px 10px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .payments-table td {
            padding: 6px 10px;
            border-bottom: 0.5px solid #f0f0f0;
            font-size: 11px;
            color: #333;
        }
        .payments-table tr:nth-child(even) td { background: #fafafa; }
        .payments-table tr:last-child td { border-bottom: none; }
        .payments-table tfoot td {
            padding: 8px 10px;
            font-weight: bold;
            font-size: 12px;
            color: #1a2e1a;
            border-top: 1.5px solid #1f6b21;
            background: #e8f5e9;
        }

        .tipo-badge {
            background: #e8f5e9;
            color: #1f6b21;
            padding: 2px 7px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
        }

        /* — Nota legal — */
        .nota {
            background: #f8f9f8;
            border: 0.5px solid #e8e8e8;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 22px;
            font-size: 11px;
            color: #666;
            line-height: 1.6;
        }

        /* — Footer — */
        .footer {
            padding: 14px 40px;
            border-top: 1px solid #e8e8e8;
            display: table;
            width: 100%;
            margin-top: 8px;
        }
        .footer-left  { display: table-cell; vertical-align: middle; }
        .footer-right { display: table-cell; vertical-align: middle; text-align: right; }
        .footer p     { font-size: 10px; color: #bbb; margin-top: 2px; }
        .footer .dev  { font-size: 10px; color: #1f6b21; font-weight: bold; }
    </style>
</head>
<body>

    {{-- ===== HEADER ===== --}}
    <div class="header">
        <div class="header-left">
            <h1>Comprobante de préstamo</h1>
            <div class="sub">Documento generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</div>
        </div>
        <div class="header-right">
            <div class="folio">
                Folio
                <span>#{{ str_pad($loan->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <span class="badge">✓ LIQUIDADO</span>
        </div>
    </div>

    {{-- ===== BODY ===== --}}
    <div class="body">

        {{-- Dos columnas: customer + préstamo --}}
        <div class="two-col">

            {{-- customer --}}
            <div class="col-left">
                <div class="section">
                    <p class="section-title">Datos del customer</p>
                    <table class="info-table">
                        <tr>
                            <td>Nombre complete</td>
                            <td>{{ $loan->customer->first_name_complete }}</td>
                        </tr>
                        <tr>
                            <td>Teléfono</td>
                            <td>{{ $loan->customer->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td>Documento</td>
                            <td>
                                @if($loan->customer->document_type)
                                    {{ strtoupper($loan->customer->document_type) }}
                                    — {{ $loan->customer->document_number ?? 'Sin número' }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Dirección</td>
                            <td>{{ $loan->customer->address ?? '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Préstamo --}}
            <div class="col-right">
                <div class="section">
                    <p class="section-title">Condiciones del préstamo</p>
                    <table class="info-table">
                        <tr>
                            <td>Tipo de préstamo</td>
                            <td>{{ ucfirst($loan->type) }}</td>
                        </tr>
                        <tr>
                            <td>Monto prstatus</td>
                            <td>${{ number_format($loan->original_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Interés monthly</td>
                            <td>{{ $loan->interestt_rate }}%</td>
                        </tr>
                        <tr>
                            <td>Frecuencia de payment</td>
                            <td>{{ ucfirst($loan->payment_frequency) }}</td>
                        </tr>
                        <tr>
                            <td>Fecha de inicio</td>
                            <td>{{ $loan->start_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td>Fecha de liquidación</td>
                            <td>{{ $loan->updated_at->format('d/m/Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        {{-- Métricas --}}
        <div class="section">
            <p class="section-title">Resumen financiero</p>
            <table class="metricas">
                <tr>
                    <td class="metrica">
                        <span class="metrica-label">Total paid</span>
                        <span class="metrica-valor azul">${{ number_format($totalpaid, 2) }}</span>
                    </td>
                    <td class="metrica">
                        <span class="metrica-label">Capital prstatus</span>
                        <span class="metrica-valor">${{ number_format($loan->original_amount, 2) }}</span>
                    </td>
                    <td class="metrica">
                        <span class="metrica-label">Interés cobrado</span>
                        <span class="metrica-valor verde">${{ number_format($totalinterest, 2) }}</span>
                    </td>
                    @if($totalMora > 0)
                        <td class="metrica">
                            <span class="metrica-label">Mora cobrada</span>
                            <span class="metrica-valor rojo">${{ number_format($totalMora, 2) }}</span>
                        </td>
                    @endif
                </tr>
            </table>
        </div>

        {{-- Historial de payments --}}
        <div class="section">
            <p class="section-title">Historial de payments ({{ $loan->payments->count() }} movimientos)</p>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Monto paid</th>
                        <th>Tipo</th>
                        <th>Capital</th>
                        <th>Interés</th>
                        @if($totalMora > 0) <th>Mora</th> @endif
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loan->payments->sortBy('payment_date') as $i => $payment)
                        <tr>
                            <td style="color:#aaa;">{{ $i + 1 }}</td>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td style="font-weight:bold;">${{ number_format($payment->amount_paid, 2) }}</td>
                            <td><span class="tipo-badge">{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</span></td>
                            <td>${{ number_format($payment->capital_payment, 2) }}</td>
                            <td>${{ number_format($payment->interestt_payment, 2) }}</td>
                            @if($totalMora > 0) <td>${{ number_format($payment->penalty_payment, 2) }}</td> @endif
                            <td style="color:#888;">{{ $payment->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td>${{ number_format($totalpaid, 2) }}</td>
                        <td></td>
                        <td>${{ number_format($totalCapital, 2) }}</td>
                        <td>${{ number_format($totalinterest, 2) }}</td>
                        @if($totalMora > 0) <td>${{ number_format($totalMora, 2) }}</td> @endif
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Nota --}}
        <div class="nota">
            Este documento es un comprobante oficial de liquidación del préstamo con folio
            <strong>#{{ str_pad($loan->id, 6, '0', STR_PAD_LEFT) }}</strong>.
            El saldo ha sido cubierto en su totalidad a la fecha indicada.
            Conserve este documento como respaldo de su transacción.
        </div>

    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        <div class="footer-left">
            <p>Documento generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</p>
            <p>Este comprobante es emitido de forma interna y no tiene validez legal.</p>
        </div>
        <div class="footer-right">
            <p>Sistema desarrollado por</p>
            <span class="dev">melSolutions</span>
        </div>
    </div>

</body>
</html>