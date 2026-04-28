@extends('layouts.app')

@section('title', 'Historial de préstamos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Historial de préstamos</h5>
        <span class="text-muted" style="font-size:13px;">{{ $loans->total() }} préstamos liquidados</span>
    </div>
</div>

@if(!$loans->total())
    <div class="bg-white border rounded-3 p-5 text-center" style="border-color:#e8e8e8 !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
             style="width:56px; height:56px; background:#e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
        </div>
        <p class="fw-medium mb-1" style="color:#1a2e1a;">Sin préstamos liquidados aún</p>
        <p class="text-muted mb-0" style="font-size:13px;">Cuando un cliente termine de pagar aparecerá aquí.</p>
    </div>
@else
    <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:14px; min-width:640px;">
                <thead style="background:#f8f9f8; border-bottom:1px solid #e8e8e8;">
                    <tr>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">cliente</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Tipo</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Monto original</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Total pagado</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Liquidado</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr style="border-top:0.5px solid #f0f0f0;">
                            <td class="px-4 py-3 text-muted">{{ $loan->id }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('history.show', $loan) }}"
                                   style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                        {{ $loan->customer->full_name }}                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $tipoBadge = match($loan->type) {
                                        'interest' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Interés'],
                                        'term'   => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Plazo'],
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-2"
                                      style="background:{{ $tipoBadge['bg'] }}; color:{{ $tipoBadge['color'] }}; font-size:11px; font-weight:500;">
                                    {{ $tipoBadge['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3" style="color:#1a2e1a;">
                                ${{ number_format($loan->original_amount, 2) }}
                            </td>
                            <td class="px-4 py-3" style="color:#1f6b21; font-weight:500;">
                                ${{ number_format($loan->payments->sum('amount_paid'), 2) }}
                            </td>
                            <td class="px-4 py-3 text-muted" style="font-size:13px;">
                                {{ $loan->updated_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('history.show', $loan) }}"
                                   style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($loans->hasPages())
        <div class="mt-3">{{ $loans->links() }}</div>
    @endif
@endif

@endsection