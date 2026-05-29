<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; margin: 40px; }
        h1 { text-align: center; font-size: 18px; text-transform: uppercase; margin-bottom: 4px; }
        h2 { text-align: center; font-size: 13px; color: #555; margin-top: 0; }
        .divider { border-top: 2px solid #222; margin: 16px 0; }
        .section-title { font-weight: bold; font-size: 13px; margin: 20px 0 6px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table td { padding: 5px 8px; }
        table tr:nth-child(even) { background: #f5f5f5; }
        .label { font-weight: bold; width: 40%; }
        .clauses p { margin: 8px 0; text-align: justify; }
        .signatures { margin-top: 60px; }
        .sig-box { display: inline-block; width: 45%; text-align: center; }
        .sig-line { border-top: 1px solid #555; margin-top: 50px; padding-top: 6px; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #888; }
    </style>
</head>
<body>

    <h1>{{ $company['name'] }}</h1>
    <h2>Contrato de Préstamo</h2>
    <div class="divider"></div>

    <div class="section-title">Datos del Contrato</div>
    <table>
        <tr><td class="label">No. Contrato:</td><td>#{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td class="label">Fecha de inicio:</td><td>{{ $loan->start_date->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Fecha de vencimiento:</td><td>{{ $loan->due_date->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Tipo de préstamo:</td><td>{{ $loan->type_label }}</td></tr>
        <tr><td class="label">Frecuencia de pago:</td><td>{{ $loan->frequency_label }}</td></tr>
    </table>

    <div class="section-title">Datos del Cliente</div>
    <table>
        <tr><td class="label">Nombre completo:</td><td>{{ $loan->customer->full_name }}</td></tr>
        <tr><td class="label">Documento:</td><td>{{ strtoupper($loan->customer->document_type) }}: {{ $loan->customer->document_number }}</td></tr>
        <tr><td class="label">Teléfono:</td><td>{{ $loan->customer->phone ?? '—' }}</td></tr>
        <tr><td class="label">Dirección:</td><td>{{ $loan->customer->address ?? '—' }}</td></tr>
    </table>

    <div class="section-title">Condiciones del Préstamo</div>
    <table>
        <tr><td class="label">Monto original:</td><td>${{ number_format($loan->original_amount, 2) }}</td></tr>
        <tr><td class="label">Tasa de interés:</td><td>{{ $loan->interest_rate }}%</td></tr>
        <tr><td class="label">Número de períodos:</td><td>{{ $loan->number_of_periods ?? '—' }}</td></tr>
        <tr><td class="label">Días de gracia:</td><td>{{ $loan->grace_days ?? 0 }}</td></tr>
        @if($loan->notes)
        <tr><td class="label">Notas:</td><td>{{ $loan->notes }}</td></tr>
        @endif
    </table>

    <div class="section-title">Cláusulas</div>
    <div class="clauses">
        <p><strong>PRIMERA.</strong> El prestamista otorga al cliente el monto señalado bajo las condiciones establecidas en el presente contrato.</p>
        <p><strong>SEGUNDA.</strong> El cliente se compromete a realizar los pagos en la frecuencia y montos acordados. El incumplimiento generará cargos por mora según lo pactado.</p>
        <p><strong>TERCERA.</strong> En caso de incumplimiento, {{ $company['name'] }} se reserva el derecho de aplicar las penalizaciones correspondientes y/o proceder legalmente.</p>
        <p><strong>CUARTA.</strong> Ambas partes aceptan los términos del presente contrato de manera voluntaria y en pleno uso de sus facultades.</p>
    </div>

    <div class="signatures">
        <table>
            <tr>
                <td style="width:50%; text-align:center;">
                    <div class="sig-line">Firma del Cliente<br><small>{{ $loan->customer->full_name }}</small></div>
                </td>
                <td style="width:50%; text-align:center;">
                    <div class="sig-line">Firma del Representante<br><small>{{ $company['name'] }}</small></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ $company['name'] }}
        @if($company['phone']) · Tel: {{ $company['phone'] }} @endif
        @if($company['email']) · {{ $company['email'] }} @endif
        @if($company['address']) · {{ $company['address'] }} @endif
    </div>

</body>
</html>