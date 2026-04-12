<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Services\PagoService;
use App\Http\Requests\StorePagoRequest;

class PagoController extends Controller
{
    public function __construct(protected PagoService $pagoService) {}

    public function store(StorePagoRequest $request, Prestamo $prestamo)
    {
        if ($prestamo->estado === 'pagado') {
            return back()->with('error', 'Este préstamo ya está pagado.');
        }

        $this->pagoService->aplicarPago($prestamo, $request->validated());

        return redirect()->route('prestamos.show', $prestamo)
                         ->with('success', 'Pago registrado correctamente.');
    }
}