<div class="bg-white border rounded-3 overflow-hidden mt-4" style="border-color:#e8e8e8 !important;">

    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
         style="border-color:#f0f0f0 !important;">
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Historial de pagos</span>
    </div>

    @if($loan->status !== 'paid')
        <div class="px-3 px-md-4 py-3 border-bottom" style="border-color:#f0f0f0 !important; background:#fafafa;">
            <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Registrar pago</p>
            <form method="POST" action="{{ route('loans.payments.store', $loan) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Monto pagado *</label>
                        <input type="number" step="0.01" name="amount_paid"
                               class="form-control form-control-sm @error('amount_paid') is-invalid @enderror"
                               placeholder="Ej: 500.00">
                        @error('amount_paid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Fecha de pago *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}"
                               class="form-control form-control-sm @error('payment_date') is-invalid @enderror">
                        @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Observaciones</label>
                        <input type="text" name="observaciones"
                               class="form-control form-control-sm" placeholder="Opcional">
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm w-100"
                                style="background:#1f6b21; color:white; border-radius:8px; font-size:13px;">
                            Aplicar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @forelse($loan->payments as $payment)
        <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span style="font-size:14px; color:#1a2e1a; font-weight:500;">
                        ${{ number_format($payment->amount_paid, 2) }}
                    </span>
                    <span class="ms-2 px-2 py-1 rounded-2"
                          style="background:#e8f5e9; color:#1f6b21; font-size:11px;">
                        {{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}
                    </span>
                </div>
                <span class="text-muted" style="font-size:12px;">
                    {{ $payment->payment_date->format('d/m/Y') }}
                </span>
            </div>
            <div class="mt-1 d-flex flex-wrap gap-3" style="font-size:12px; color:#888;">
                @if($payment->penalty_payment > 0)
                    <span>Mora: ${{ number_format($payment->penalty_payment, 2) }}</span>
                @endif
                @if($payment->interestt_payment > 0)
                    <span>Interés: ${{ number_format($payment->interestt_payment, 2) }}</span>
                @endif
                @if($payment->capital_payment > 0)
                    <span>Capital: ${{ number_format($payment->capital_payment, 2) }}</span>
                @endif
                @if($payment->notes)
                    <span>— {{ $payment->notes }}</span>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted" style="font-size:13px;">
            No hay pagos registrados aún.
        </div>
    @endforelse

</div>