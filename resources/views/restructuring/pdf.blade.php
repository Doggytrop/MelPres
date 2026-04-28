<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acuerdo de Reestructuración #{{ $restructuring->id }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size:12px; color:#2c2c2c; line-height:1.7; }

        .header { padding:28px 40px; border-bottom:3px solid #1f6b21; display:table; width:100%; }
        .header-left  { display:table-cell; vertical-align:middle; }
        .header-right { display:table-cell; vertical-align:middle; text-align:right; }
        .header h1    { font-size:14px; font-weight:bold; color:#1a2e1a; text-transform:uppercase; letter-spacing:1px; }
        .header .sub  { font-size:10px; color:#888; margin-top:3px; }
        .folio        { font-size:16px; font-weight:bold; color:#1a2e1a; }
        .fecha        { font-size:11px; color:#888; margin-top:4px; }

        .body { padding:28px 40px; }

        .section { margin-bottom:20px; }
        .section-title {
            font-size:11px;
            font-weight:bold;
            color:#1f6b21;
            text-transform:uppercase;
            letter-spacing:.08em;
            padding-bottom:5px;
            border-bottom:1px solid #c8e6c9;
            margin-bottom:10px;
        }

        p { margin-bottom:8px; font-size:12px; color:#333; text-align:justify; }

        .lista { margin:8px 0 8px 16px; }
        .lista li { margin-bottom:5px; font-size:12px; color:#333; }

        .clausula { margin-bottom:12px; }
        .clausula-num { font-weight:bold; color:#1a2e1a; }

        .condiciones-box {
            background:#f8f9f8;
            border:0.5px solid #e8e8e8;
            border-left:3px solid #1f6b21;
            padding:12px 16px;
            margin:12px 0;
            border-radius:0 4px 4px 0;
        }
        .condiciones-box p { margin-bottom:5px; }

        .motivo-box {
            background:#f8f9f8;
            border-left:3px solid #1f6b21;
            padding:12px 16px;
            margin:8px 0;
            font-size:12px;
            color:#555;
            font-style:italic;
        }

        .pena-box {
            background:#fff3e0;
            border:0.5px solid #ffcc80;
            padding:12px 16px;
            margin:12px 0;
            border-radius:4px;
            font-size:12px;
        }

        .divider { border:none; border-top:0.5px solid #e0e0e0; margin:20px 0; }

        .firmas { display:table; width:100%; margin-top:50px; }
        .firma-col { display:table-cell; width:45%; text-align:center; padding:0 10px; }
        .firma-sep { display:table-cell; width:10%; }
        .firma-linea { border-top:1px solid #333; padding-top:8px; margin-top:70px; }
        .firma-nombre { font-size:12px; font-weight:bold; color:#1a2e1a; }
        .firma-cargo  { font-size:10px; color:#888; margin-top:3px; }
        .firma-doc    { font-size:10px; color:#888; margin-top:2px; }

        .footer {
            margin-top:32px;
            padding:12px 40px;
            border-top:1px solid #e8e8e8;
            display:table;
            width:100%;
        }
        .footer-left  { display:table-cell; vertical-align:middle; }
        .footer-right { display:table-cell; vertical-align:middle; text-align:right; }
        .footer p   { font-size:10px; color:#bbb; margin:0; }
        .footer .dev { font-size:10px; color:#1f6b21; font-weight:bold; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <h1>Acuerdo de reestructuración de crédito</h1>
            <div class="sub">Documento administrativo — requiere firma de ambas partes</div>
        </div>
        <div class="header-right">
            <div class="folio">Folio #{{ str_pad($restructuring->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="fecha">Fecha de emisión: {{ $restructuring->created_at->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="body">

        {{-- I. Declaraciones --}}
        <div class="section">
            <p class="section-title">I. Declaraciones</p>
            <p>
                En la fecha señalada, comparecen por una parte <strong>EL ACREEDOR</strong>,
                en adelante denominado <em>"El Prestamista"</em>, y por la otra
                <strong>{{ $restructuring->originalLoan->cliente->first_name_complete }}</strong>,
                en adelante denominado <em>"El Cliente"</em>, quienes manifiestan lo siguiente:
            </p>
            <ul class="lista">
                <li>Que existe un crédito previamente celebrado identificado con el folio <strong>#{{ $restructuring->original_loan_id }}</strong>.</li>
                <li>Que ambas partes cuentan con capacidad legal para obligarse en los términos del presente acuerdo.</li>
                <li>Que es su voluntad reestructurar las condiciones del crédito original.</li>
            </ul>
        </div>

        <hr class="divider">

        {{-- II. Datos del cliente --}}
        <div class="section">
            <p class="section-title">II. Datos del cliente</p>
            <ul class="lista">
                <li><strong>Nombre:</strong> {{ $restructuring->originalLoan->cliente->first_name_complete }}</li>
                <li><strong>Teléfono:</strong> {{ $restructuring->originalLoan->cliente->phone ?? 'No especificado' }}</li>
                <li>
                    <strong>Documento:</strong>
                    @if($restructuring->originalLoan->cliente->document_type)
                        {{ strtoupper($restructuring->originalLoan->cliente->document_type) }}
                        — {{ $restructuring->originalLoan->cliente->document_number ?? 'Sin número' }}
                    @else
                        No especificado
                    @endif
                </li>
                <li><strong>Domicilio:</strong> {{ $restructuring->originalLoan->cliente->address ?? 'No especificado' }}</li>
            </ul>
        </div>

        <hr class="divider">

        {{-- III. Datos del crédito original --}}
        <div class="section">
            <p class="section-title">III. Datos del crédito original</p>
            <ul class="lista">
                <li><strong>Monto original:</strong> ${{ number_format($restructuring->originalLoan->original_amount, 2) }}</li>
                <li><strong>Saldo al momento de reestructurar:</strong> ${{ number_format($restructuring->balance_at_restructuring, 2) }}</li>
                <li><strong>Mora acumulada:</strong> ${{ number_format($restructuring->original_penalty, 2) }}</li>
                <li><strong>Tasa de interés:</strong> {{ $restructuring->originalLoan->interestt_rate }}% monthly</li>
                <li><strong>Frecuencia de payment:</strong> {{ ucfirst($restructuring->originalLoan->payment_frequency) }}</li>
            </ul>
        </div>

        <hr class="divider">

        {{-- IV. Condiciones --}}
        <div class="section">
            <p class="section-title">IV. Condiciones de la reestructuración</p>

            @if($restructuring->type === 'forgiveness')
                <p>Se acuerda una <strong>condonación partial de mora</strong>, bajo los siguientes términos:</p>
                <div class="condiciones-box">
                    <p><strong>Mora original:</strong> ${{ number_format($restructuring->original_penalty, 2) }}</p>
                    <p><strong>Monto condonado:</strong> ${{ number_format($restructuring->forgiven_penalty, 2) }}</p>
                    <p><strong>Mora restante a cubrir:</strong> ${{ number_format($restructuring->remaining_penalty, 2) }}</p>
                    <p><strong>Saldo de capital pendiente:</strong> ${{ number_format($restructuring->balance_at_restructuring, 2) }}</p>
                </div>
                <p>El cliente se obliga a cubrir el saldo restante conforme a las nuevas condiciones pactadas.</p>

            @elseif($restructuring->type === 'extension')
                <p>Se acuerda una <strong>extensión del term del crédito</strong>, en los siguientes términos:</p>
                <div class="condiciones-box">
                    <p><strong>Periodos anteriores:</strong> {{ $restructuring->previous_periods }}</p>
                    <p><strong>Nuevos periodos:</strong> {{ $restructuring->new_periods }}</p>
                    <p><strong>Frecuencia de payment:</strong> {{ ucfirst($restructuring->originalLoan->payment_frequency) }}</p>
                    <p><strong>Saldo pendiente:</strong> ${{ number_format($restructuring->balance_at_restructuring, 2) }}</p>
                    <p><strong>Mora congelada:</strong> ${{ number_format($restructuring->original_penalty, 2) }}</p>
                </div>
                <p>La mora acumulada se mantiene congelada y el crédito continuará vigente bajo el nuevo calendario acordado.</p>

            @elseif($restructuring->type === 'new_loan')
                <p>Se acuerda la <strong>sustitución del crédito original por uno nuevo</strong>, quedando el anterior liquidado administrativamente.</p>
                @if($restructuring->newLoan)
                    <div class="condiciones-box">
                        <p><strong>Préstamo original cerrado:</strong> Folio #{{ $restructuring->original_loan_id }}</p>
                        <p><strong>Nuevo préstamo folio:</strong> #{{ $restructuring->new_loan_id }}</p>
                        <p><strong>Nuevo monto:</strong> ${{ number_format($restructuring->newLoan->original_amount, 2) }}</p>
                        <p><strong>Tasa de interés:</strong> {{ $restructuring->newLoan->interestt_rate }}% monthly</p>
                        <p><strong>Frecuencia:</strong> {{ ucfirst($restructuring->newLoan->payment_frequency) }}</p>
                        <p><strong>Periodos:</strong> {{ $restructuring->newLoan->number_of_periods }}</p>
                    </div>
                @endif
            @endif
        </div>

        <hr class="divider">

        {{-- V. Motivo --}}
        <div class="section">
            <p class="section-title">V. Motivo de la reestructuración</p>
            <div class="motivo-box">{{ $restructuring->reason }}</div>
        </div>

        @if($restructuring->notes)
            <hr class="divider">
            <div class="section">
                <p class="section-title">VI. Observaciones adicionales</p>
                <div class="motivo-box">{{ $restructuring->notes }}</div>
            </div>
        @endif

        <hr class="divider">

        {{-- VII. Cláusulas --}}
        <div class="section">
            <p class="section-title">{{ $restructuring->notes ? 'VII' : 'VI' }}. Cláusulas</p>

            <div class="clausula">
                <p><span class="clausula-num">1. Obligación de payment.</span>
                El cliente se compromete a cumplir puntualmente con los payments conforme al calendario establecido.</p>
            </div>

            <div class="clausula">
                <p><span class="clausula-num">2. interestes moratorios.</span>
                En caso de incumplimiento, se generarán interestes moratorios conforme a las políticas internas del Prestamista.</p>
            </div>

            <div class="clausula">
                <p><span class="clausula-num">3. Cláusula de incumplimiento y acción legal.</span>
                En caso de que el cliente no realice el payment en la fecha establecida, el Prestamista podrá exigir el payment
                inmediato del saldo total pendiente y tendrá la facultad de iniciar las acciones legales correspondientes
                para la recuperación del adeudo.</p>
            </div>

            <div class="clausula">
                <p><span class="clausula-num">4. Pena convencional.</span>
                Las partes acuerdan que, en caso de incumplimiento, el cliente será responsable de cubrir una pena
                convencional por la cantidad de:</p>
                <div class="pena-box">
                    <strong>$_____________________________________________________________________________________________________</strong>
                </div>
                <p>sin perjuicio de los interestes, gastos de cobranza y demás accesorios legales que procedan.</p>
            </div>

            <div class="clausula">
                <p><span class="clausula-num">5. Reconocimiento de adeudo.</span>
                El cliente reconoce expresamente el adeudo señalado en este documento, mismo que se considera
                exigible en los términos aquí establecidos.</p>
            </div>

            <div class="clausula">
                <p><span class="clausula-num">6. Jurisdicción.</span>
                Para la interpretación y cumplimiento del presente acuerdo, las partes se someten a las leyes y
                tribunales competentes en el status que corresponda, renunciando a cualquier otro fuero que pudiera
                corresponderles.</p>
            </div>
        </div>

        <hr class="divider">

        {{-- Firmas --}}
        <div class="firmas">
            <div class="firma-col">
                <div class="firma-linea">
                    <div class="firma-nombre">El Prestamista</div>
                    <div class="firma-cargo">Nombre y firma</div>
                    <div class="firma-doc">Sello (si aplica)</div>
                </div>
            </div>
            <div class="firma-sep"></div>
            <div class="firma-col">
                <div class="firma-linea">
                    <div class="firma-nombre">{{ $restructuring->originalLoan->cliente->first_name_complete }}</div>
                    <div class="firma-cargo">El cliente — Firma</div>
                    @if($restructuring->originalLoan->cliente->document_type)
                        <div class="firma-doc">
                            {{ strtoupper($restructuring->originalLoan->cliente->document_type) }}:
                            {{ $restructuring->originalLoan->cliente->document_number ?? '—' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            <p>Generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }} por {{ $restructuring->recordedBy->name }}</p>
            <p>Este documento constituye un acuerdo formal administrativo y podrá ser utilizado como medio probatorio.</p>
        </div>
        <div class="footer-right">
            <p>Sistema desarrollado por</p>
            <span class="dev">melSolutions</span>
        </div>
    </div>

</body>
</html>