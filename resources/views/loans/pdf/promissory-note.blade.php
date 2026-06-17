<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; margin: 40px; }
        h1 { text-align: center; font-size: 20px; text-transform: uppercase; margin-bottom: 4px; }
        h2 { text-align: center; font-size: 13px; color: #555; margin-top: 0; }
        .divider { border-top: 2px solid #222; margin: 16px 0; }
        .amount-box { text-align: center; font-size: 22px; font-weight: bold; margin: 20px 0; }
        .body-text { text-align: justify; line-height: 1.8; margin: 20px 0; }
        .sig-line { border-top: 1px solid #555; margin-top: 60px; padding-top: 6px; text-align: center; width: 60%; margin-left: auto; margin-right: auto; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #888; }
        .meta { margin: 6px 0; }
    </style>
</head>
<body>

    <h1>Pagaré</h1>
    <h2>{{ $company['name'] }}</h2>
    <div class="divider"></div>

    <div class="amount-box">
        $ {{ number_format($loan->original_amount + $loan->accrued_interest, 2) }} MXN
    </div>

    <p class="meta"><strong>No. Pagaré:</strong> #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</p>
    <p class="meta"><strong>Lugar y fecha:</strong> {{ $company['address'] ?? '_______________' }}, {{ now()->format('d/m/Y') }}</p>

    <div class="body-text">
        Yo, <strong>{{ $loan->customer->full_name }}</strong>, con documento
        <strong>{{ strtoupper($loan->customer->document_type) }}: {{ $loan->customer->document_number }}</strong>,
        con domicilio en <strong>{{ $loan->customer->address ?? '_______________' }}</strong>,
        me comprometo incondicionalmente a pagar a la orden de
        <strong>{{ $company['name'] }}</strong> la cantidad de
        <strong>${{ number_format($loan->original_amount + $loan->accrued_interest, 2) }} MXN</strong>
        ({{ \App\Helpers\NumberHelper::toWords($loan->original_amount + $loan->accrued_interest) ?? number_format($loan->original_amount + $loan->accrued_interest, 2).' pesos' }}),
        cantidad que incluye capital e intereses al <strong>{{ $loan->interest_rate }}%</strong>,
        a más tardar el día <strong>{{ $loan->due_date->format('d/m/Y') }}</strong>.
    </div>

    <p class="body-text">
        Este pagaré es mercantil, ejecutivo, incondicional y no está sujeto a condición alguna.
        En caso de falta de pago, el tenedor podrá exigir su cobro por la vía legal correspondiente,
        siendo el deudor responsable de los gastos de cobranza y honorarios de abogado.
    </p>

    <div class="sig-line">
        Firma del Deudor<br>
        <small>{{ $loan->customer->full_name }}</small><br>
        <small>{{ strtoupper($loan->customer->document_type) }}: {{ $loan->customer->document_number }}</small>
    </div>

    <div class="footer">
        {{ $company['name'] }}
        @if($company['phone']) · Tel: {{ $company['phone'] }} @endif
        @if($company['email']) · {{ $company['email'] }} @endif
    </div>

</body>
</html>