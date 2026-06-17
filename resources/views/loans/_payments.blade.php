{{-- Registrar pago --}}
@if($loan->status !== 'paid' && $loan->status !== 'refinanced')
<div class="bg-white border rounded-3 p-4 mb-3" style="border-color:#e8e8e8 !important;"
     x-data="{
    paidCount: {{ $paidCount ?? 0 }},
    periods: 1,
    dailyPayment: {{ floatval($loan->daily_payment ?: $loan->suggested_payment) }},
    nextAmount: {{ $nextAmount ?? floatval($loan->daily_payment ?: $loan->suggested_payment) }},
    amountPaid: {{ $nextAmount ?? floatval($loan->daily_payment ?: $loan->suggested_payment) }},
    get base() {
        if (this.periods === 1) {
            return Math.round(this.nextAmount * 100) / 100;
        }
        return Math.round((this.nextAmount + (this.periods - 1) * this.dailyPayment) * 100) / 100;
    },
    get carryOver() { return Math.max(0, Math.round((this.amountPaid - this.base) * 100) / 100); },
    updateAmount() { this.amountPaid = this.base; }
}"
@select-period.window="
    periods = Math.max(1, $event.detail.number - paidCount);
    updateAmount();
">

    <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Registrar pago</p>

    <form method="POST" action="{{ route('loans.payments.store', $loan) }}">
        @csrf

        <div class="row g-2 align-items-end">

            {{-- Períodos --}}
            <div class="col-6 col-md-2">
                <label class="d-block mb-1 text-muted" style="font-size:11px;">Períodos *</label>
                <input type="number" name="periods" min="1" max="52"
                       x-model.number="periods"
                       @input="updateAmount()"
                       class="form-control form-control-sm text-center">
            </div>

            {{-- Esperado --}}
            <div class="col-6 col-md-2">
                <label class="d-block mb-1 text-muted" style="font-size:11px;">Esperado</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control form-control-sm bg-light" readonly
                           :value="base.toFixed(2)">
                </div>
            </div>

            {{-- Monto real --}}
            <div class="col-6 col-md-2">
                <label class="d-block mb-1 text-muted" style="font-size:11px;">Monto real *</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="amount_paid"
                           x-model.number="amountPaid"
                           class="form-control form-control-sm @error('amount_paid') is-invalid @enderror">
                </div>
                @error('amount_paid') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Sobrante --}}
            <div class="col-6 col-md-2">
                <label class="d-block mb-1 text-muted" style="font-size:11px;">Sobrante</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control form-control-sm bg-light" readonly
                           :value="carryOver.toFixed(2)"
                           :class="carryOver > 0 ? 'text-success fw-bold' : ''">
                </div>
            </div>

            {{-- Fecha --}}
            <div class="col-6 col-md-2">
                <label class="d-block mb-1 text-muted" style="font-size:11px;">Fecha *</label>
                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}"
                       class="form-control form-control-sm">
            </div>

            {{-- Notas + botón --}}
            <div class="col-9 col-md-3">
                <label class="d-block mb-1 text-muted" style="font-size:11px;">Notas</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opcional">
            </div>
            <div class="col-3 col-md-2">
                <button type="submit" class="btn btn-sm w-100"
                        style="background:var(--color-primary); color:white; border-radius:8px; font-size:13px; padding:6px;">
                    Aplicar
                </button>
            </div>

        </div>

        <p x-show="carryOver > 0" x-cloak class="mb-0 mt-2 text-success" style="font-size:11px;">
            ⚡ El sobrante de <span x-text="'$' + carryOver.toFixed(2)"></span> se abonará al siguiente período.
        </p>

    </form>
</div>
@endif

{{-- Historial de pagos --}}
<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
         style="border-color:#f0f0f0 !important;">
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Historial de pagos</span>
        <span class="text-muted" style="font-size:12px;">{{ $loan->payments->count() }} pagos</span>
    </div>

    @forelse($loan->payments as $payment)
        <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:28px; height:28px; background:var(--color-secondary); flex-shrink:0;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </div>
                    <div>
                        <span style="font-size:14px; color:var(--color-primary); font-weight:500;">
                            ${{ number_format($payment->amount_paid, 2) }}
                        </span>
                        <span class="text-muted ms-2" style="font-size:12px;">
                            {{ $payment->payment_date instanceof \Carbon\Carbon ? $payment->payment_date->format('d/m/Y') : $payment->payment_date }}
                        </span>
                            @if($payment->periods_covered > 1)
                        <span class="ms-2 badge" style="background:#e8f4e8; color:#2d6a2d; font-size:10px;">
                            {{ $payment->periods_covered }} períodos
                        </span>
                        @endif
                            @if($payment->carry_over > 0)
                        <span class="ms-1 badge" style="background:#fff3e0; color:#e65100; font-size:10px;">
                            +${{ number_format($payment->carry_over, 2) }} sobrante
                        </span>
                        @endif
                    </div>
                </div>
                <div class="text-end" style="font-size:11px; color:#888;">
                    @if($payment->capital_payment > 0)
                        <span>Capital: ${{ number_format($payment->capital_payment, 2) }}</span>
                    @endif
                    @if($payment->interest_payment > 0)
                        <span class="ms-2">Interés: ${{ number_format($payment->interest_payment, 2) }}</span>
                    @endif
                    @if($payment->penalty_payment > 0)
                        <span class="ms-2" style="color:#c0392b;">Mora: ${{ number_format($payment->penalty_payment, 2) }}</span>
                    @endif
                </div>
            </div>
            @if($payment->notes)
                <p class="mb-0 mt-1 text-muted" style="font-size:11px; padding-left:40px;">{{ $payment->notes }}</p>
            @endif
        </div>
    @empty
        <div class="text-center py-4 text-muted" style="font-size:13px;">
            No hay pagos registrados aún.
        </div>
    @endforelse
</div>