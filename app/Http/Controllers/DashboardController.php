<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\Pago;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index', [
            'clientes' => Cliente::count(),
            'prestamos' => Prestamo::where('estado', 'activo')->count(),
            'pagos' => Pago::whereDate('fecha_pago', now())->count(),
        ]);
    }
}