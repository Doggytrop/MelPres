<div class="bg-white border rounded-3 overflow-hidden mt-4" style="border-color:#e8e8e8 !important;">

    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center"
         style="border-color:#f0f0f0 !important;">
        <span class="fw-medium" style="font-size:14px; color:#1a2e1a;">Historial de pagos</span>
    </div>

    {{-- Formulario de nuevo pago --}}
    @if($prestamo->estado !== 'pagado')
        <div class="px-4 py-3 border-bottom" style="border-color:#f0f0f0 !important; background:#fafafa;">
            <p class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Registrar pago</p>
            <form method="POST" action="{{ route('prestamos.pagos.store', $prestamo) }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Monto pagado *</label>
                        <input type="number" step="0.01" name="monto_pagado"
                               class="form-control form-control-sm @error('monto_pagado') is-invalid @enderror"
                               placeholder="Ej: 500.00">
                        @error('monto_pagado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Fecha de pago *</label>
                        <input type="date" name="fecha_pago" value="{{ date('Y-m-d') }}"
                               class="form-control form-control-sm @error('fecha_pago') is-invalid @enderror">
                        @error('fecha_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Observaciones</label>
                        <input type="text" name="observaciones"
                               class="form-control form-control-sm" placeholder="Opcional">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100"
                                style="background:#1f6b21; color:white; border-radius:8px; font-size:13px;">
                            Aplicar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    {{-- Listado de pagos --}}
    @forelse($prestamo->pagos as $pago)
        <div class="px-4 py-3 border-bottom" style="border-color:#f8f8f8 !important;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span style="font-size:14px; color:#1a2e1a; font-weight:500;">
                        ${{ number_format($pago->monto_pagado, 2) }}
                    </span>
                    <span class="ms-2 px-2 py-1 rounded-2"
                          style="background:#e8f5e9; color:#1f6b21; font-size:11px;">
                        {{ ucfirst(str_replace('_', ' ', $pago->tipo_pago)) }}
                    </span>
                </div>
                <span class="text-muted" style="font-size:12px;">
                    {{ $pago->fecha_pago->format('d/m/Y') }}
                </span>
            </div>
            <div class="mt-1 d-flex gap-3" style="font-size:12px; color:#888;">
                @if($pago->abono_mora > 0)
                    <span>Mora: ${{ number_format($pago->abono_mora, 2) }}</span>
                @endif
                @if($pago->abono_interes > 0)
                    <span>Interés: ${{ number_format($pago->abono_interes, 2) }}</span>
                @endif
                @if($pago->abono_capital > 0)
                    <span>Capital: ${{ number_format($pago->abono_capital, 2) }}</span>
                @endif
                @if($pago->observaciones)
                    <span>— {{ $pago->observaciones }}</span>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted" style="font-size:13px;">
            No hay pagos registrados aún.
        </div>
    @endforelse

</div>