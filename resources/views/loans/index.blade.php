@extends('layouts.app')

@section('title', 'Préstamos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Préstamos</h5>
        <span class="text-muted" style="font-size:13px;">{{ $loans->total() }} registrados</span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="abrirModalPago()"
                class="btn btn-sm"
                style="background:#fff; color:#1f6b21; border:0.5px solid #c8e6c9; border-radius:8px; font-size:13px; padding:7px 16px;">
            + Nuevo pago
        </button>
        <a href="{{ route('loans.create') }}"
           class="btn btn-sm"
           style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:7px 16px;">
            + Nuevo préstamo
        </a>
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

<div class="bg-white border rounded-3 overflow-hidden" style="border-color:#e8e8e8 !important;">
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:14px; min-width:640px;">
            <thead style="background:#f8f9f8; border-bottom: 1px solid #e8e8e8;">
                <tr>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">#</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Cliente</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-sm-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Tipo</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Monto</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Saldo</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-md-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Interés</th>
                    <th class="px-4 py-3 fw-medium text-muted d-none d-sm-table-cell" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                    <th class="px-4 py-3 fw-medium text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr style="border-top: 0.5px solid #f0f0f0;">
                        <td class="px-4 py-3 text-muted">{{ $loan->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('loans.show', $loan) }}"
                               style="color:#1a2e1a; text-decoration:none; font-weight:500;">
                                {{ $loan->customer?->full_name ?? 'Cliente eliminado' }}
                            </a>
                        </td>
                        <td class="px-4 py-3 d-none d-sm-table-cell">
                            @php
                                $tipoBadge = match($loan->type) {
                                    'interest' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Interés'],
                                    'term'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Plazo'],
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
                            ${{ number_format($loan->remaining_balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-muted d-none d-md-table-cell">
                            {{ $loan->interest_rate }}%
                        </td>
                        <td class="px-4 py-3 d-none d-sm-table-cell">
                            @php
                                $statusBadge = match($loan->status) {
                                    'active'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Activo'],
                                    'paid'       => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Pagado'],
                                    'overdue'    => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Vencido'],
                                    'refinanced' => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Refinanciado'],
                                };
                            @endphp
                            <span class="px-2 py-1 rounded-2"
                                  style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['color'] }}; font-size:11px; font-weight:500;">
                                {{ $statusBadge['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex gap-2">
                                <a href="{{ route('loans.show', $loan) }}"
                                   style="font-size:12px; color:#1f6b21; text-decoration:none; border:0.5px solid #c8e6c9; border-radius:6px; padding:4px 10px;">
                                    Ver
                                </a>
                                <a href="{{ route('loans.edit', $loan) }}"
                                   class="d-none d-sm-inline"
                                   style="font-size:12px; color:#555; text-decoration:none; border:0.5px solid #ddd; border-radius:6px; padding:4px 10px;">
                                    Editar
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted" style="font-size:13px;">
                            No hay préstamos registrados aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($loans->hasPages())
    <div class="mt-3">{{ $loans->links() }}</div>
@endif

{{-- MODAL NUEVO PAGO --}}
<div id="modalPago" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.4); z-index:9999; align-items:center; justify-content:center;">
    <div class="bg-white rounded-3 p-4" style="width:100%; max-width:520px; margin:1rem;">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-medium mb-0" style="color:#1a2e1a;">Registrar pago</h6>
            <button onclick="cerrarModalPago()"
                    style="background:none; border:none; font-size:20px; color:#888; cursor:pointer;">×</button>
        </div>

        <div class="mb-3">
            <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">
                Buscar cliente
            </label>
            <input type="text" id="buscadorCliente"
                   class="form-control form-control-sm"
                   placeholder="Nombre o teléfono..."
                   oninput="buscarCliente(this.value)">
        </div>

        <div id="resultadosBusqueda" style="display:none;" class="mb-3">
            <div id="listaClientes"></div>
        </div>

        <div id="formPago" style="display:none;">
            <div class="p-3 rounded-3 mb-3" style="background:#f8f9f8; border:0.5px solid #eee;">
                <p class="fw-medium mb-1" style="font-size:13px; color:#1a2e1a;" id="nombreClienteSeleccionado"></p>
                <p class="mb-0 text-muted" style="font-size:12px;" id="infoPrestamo"></p>
            </div>

            <form method="POST" id="formPagoRapido">
                @csrf
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Monto pagado *</label>
                        <input type="number" step="0.01" name="amount_paid"
                               class="form-control form-control-sm" placeholder="0.00" required>
                    </div>
                    <div class="col-md-6">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Fecha de pago *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}"
                               class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="d-block mb-1 text-muted" style="font-size:11px;">Observaciones</label>
                        <input type="text" name="notes"
                               class="form-control form-control-sm" placeholder="Opcional">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-sm"
                            style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                        Aplicar pago
                    </button>
                    <button type="button" onclick="limpiarSeleccion()"
                            style="background:#f5f5f5; color:#555; border:none; border-radius:8px; font-size:13px; padding:8px 20px; cursor:pointer;">
                        Cambiar cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let busquedaTimeout = null;

function abrirModalPago() {
    document.getElementById('modalPago').style.display = 'flex';
    setTimeout(() => document.getElementById('buscadorCliente').focus(), 100);
}

function cerrarModalPago() {
    document.getElementById('modalPago').style.display = 'none';
    limpiarSeleccion();
    document.getElementById('buscadorCliente').value = '';
    document.getElementById('resultadosBusqueda').style.display = 'none';
}

function buscarCliente(q) {
    clearTimeout(busquedaTimeout);
    if (q.length < 2) {
        document.getElementById('resultadosBusqueda').style.display = 'none';
        return;
    }
    busquedaTimeout = setTimeout(() => {
        fetch(`/loans/search-customer?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(customers => mostrarResultados(customers));
    }, 300);
}

function mostrarResultados(customers) {
    const lista = document.getElementById('listaClientes');
    const box   = document.getElementById('resultadosBusqueda');

    if (!customers.length) {
        lista.innerHTML = '<p class="text-muted text-center py-3" style="font-size:13px;">No se encontraron clientes con préstamos activos.</p>';
        box.style.display = 'block';
        return;
    }

    lista.innerHTML = customers.map(c => `
        <div class="p-3 rounded-3 mb-2" style="border:0.5px solid #eee; cursor:pointer;"
             onmouseover="this.style.background='#f8f9f8'" onmouseout="this.style.background='#fff'">
            <p class="fw-medium mb-1" style="font-size:13px; color:#1a2e1a;">${c.name}</p>
            <p class="mb-2 text-muted" style="font-size:11px;">${c.phone}</p>
            ${c.loans.length
                ? c.loans.map(p => `
                    <div class="d-flex justify-content-between align-items-center py-1"
                         style="border-top:0.5px solid #f5f5f5; cursor:pointer;"
                         onclick="seleccionarPrestamo('${c.name}', ${p.id}, '${p.type}', '${p.balance}', '${p.penalty}', '${p.url}')">
                        <span style="font-size:12px; color:#555;">Préstamo #${p.id} — ${p.type}</span>
                        <span style="font-size:12px; color:#1f6b21; font-weight:500;">Saldo: $${p.balance}</span>
                    </div>
                `).join('')
                : '<p class="mb-0 text-muted" style="font-size:12px;">Sin préstamos activos</p>'
            }
        </div>
    `).join('');

    box.style.display = 'block';
}

function seleccionarPrestamo(nombre, loanId, tipo, saldo, mora, url) {
    document.getElementById('resultadosBusqueda').style.display = 'none';
    document.getElementById('buscadorCliente').style.display    = 'none';
    document.getElementById('formPago').style.display           = 'block';

    document.getElementById('nombreClienteSeleccionado').textContent = nombre;
    document.getElementById('infoPrestamo').textContent =
        `Préstamo #${loanId} — ${tipo} | Saldo: $${saldo}` +
        (parseFloat(mora) > 0 ? ` | Mora: $${mora}` : '');

    document.getElementById('formPagoRapido').action = url;
}

function limpiarSeleccion() {
    document.getElementById('formPago').style.display           = 'none';
    document.getElementById('buscadorCliente').style.display    = 'block';
    document.getElementById('resultadosBusqueda').style.display = 'none';
    document.getElementById('buscadorCliente').value            = '';
}

document.getElementById('modalPago').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPago();
});
</script>

@endsection