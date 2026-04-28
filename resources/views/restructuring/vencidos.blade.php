@extends('layouts.app')

@section('title', 'Préstamos vencidos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Préstamos vencidos</h5>
        <span class="text-muted" style="font-size:13px;">{{ $loans->total() }} préstamos con atraso</span>
    </div>
</div>

@if(session('success'))
    <div class="alert border rounded-3 mb-4 d-flex align-items-center gap-2"
         style="background:#e8f5e9; border-color:#c8e6c9 !important; color:#1f6b21; font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@if(!$loans->total())
    <div class="bg-white border rounded-3 p-5 text-center" style="border-color:#e8e8e8 !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
             style="width:56px; height:56px; background:#e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1f6b21" stroke-width="1.5">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
        </div>
        <p class="fw-medium mb-1" style="color:#1a2e1a;">No hay préstamos vencidos</p>
        <p class="text-muted mb-0" style="font-size:13px;">Todos los préstamos están al corriente.</p>
    </div>
@else
    <div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:14px; min-width:640px;">
                <thead style="background:#f8f9f8; border-bottom:1px solid #e8e8e8;">
                    <tr>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">cliente</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Mora</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Último pago esperado</th>
                        <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr style="border-top:0.5px solid #f0f0f0;">
                            <td class="px-4 py-3 text-muted">{{ $loan->id }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('loans.show', $loan) }}"
                                   style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                    {{ $loan->cliente?->first_name_complete ?? 'cliente eliminado' }}
                                </a>
                            </td>
                            <td class="px-4 py-3" style="color:#c0392b; font-weight:500;">
                                ${{ number_format($loan->remaining_balance, 2) }}
                            </td>
                            <td class="px-4 py-3" style="color:#c0392b;">
                                ${{ number_format($loan->accumulated_penalty, 2) }}
                            </td>
                            <td class="px-4 py-3 text-muted">
                                {{ $loan->next_payment_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('loans.show', $loan) }}"
                                       style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                                        Ver
                                    </a>
                                    <a href="{{ route('restructuring.create', $loan) }}"
                                       style="font-size:12px; color:#e65100; text-decoration:none; border:0.5px solid #ffcc80; border-radius:6px; padding:4px 10px;">
                                        Reestructurar
                                    </a>
                                </div>
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