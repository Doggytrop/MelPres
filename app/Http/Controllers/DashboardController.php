<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\Pago;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->esAdmin()) {
            return $this->dashboardAdmin();
        }

        return $this->dashboardAsesor();
    }

    private function dashboardAdmin()
    {
        $hoy = Carbon::today();

        $totalCapital = Prestamo::whereIn('estado', ['activo', 'vencido'])
                                ->sum('saldo_restante');

        $totalClientes = Cliente::where('estado', 'activo')->count();

        $prestamosActivos = Prestamo::where('estado', 'activo')->count();

        $prestamosVencidos = Prestamo::where('estado', 'vencido')->count();

        $pagosHoy = Pago::whereDate('fecha_pago', $hoy)
                        ->with(['prestamo.cliente', 'registradoPor'])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $pagosHoy->sum('monto_pagado');

        $interesDelMes = Pago::whereMonth('fecha_pago', $hoy->month)
                             ->whereYear('fecha_pago', $hoy->year)
                             ->sum('abono_interes');

        $moraDelMes = Pago::whereMonth('fecha_pago', $hoy->month)
                          ->whereYear('fecha_pago', $hoy->year)
                          ->sum('abono_mora');

        $vencidos = Prestamo::where('estado', 'vencido')
                            ->with('cliente')
                            ->latest()
                            ->take(10)
                            ->get();

        $proximosVencimientos = Prestamo::where('estado', 'activo')
                                        ->whereBetween('fecha_proximo_pago', [
                                            $hoy->copy()->toDateString(),
                                            $hoy->copy()->addDays(7)->toDateString(),
                                        ])
                                        ->with('cliente')
                                        ->orderBy('fecha_proximo_pago')
                                        ->take(10)
                                        ->get();

        return view('dashboard.admin', compact(
            'totalCapital',
            'totalClientes',
            'prestamosActivos',
            'prestamosVencidos',
            'pagosHoy',
            'totalCobradoHoy',
            'interesDelMes',
            'moraDelMes',
            'vencidos',
            'proximosVencimientos',
        ));
    }

    private function dashboardAsesor()
    {
        $hoy = Carbon::today();

        $pagosHoy = Pago::whereDate('fecha_pago', $hoy)
                        ->where('registrado_por', auth()->id())
                        ->with(['prestamo.cliente'])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $pagosHoy->sum('monto_pagado');

        $vencenHoy = Prestamo::where('estado', 'activo')
                             ->whereDate('fecha_proximo_pago', $hoy->toDateString())
                             ->with('cliente')
                             ->get();

        $vencidos = Prestamo::where('estado', 'vencido')
                            ->with('cliente')
                            ->latest()
                            ->take(5)
                            ->get();

        return view('dashboard.asesor', compact(
            'pagosHoy',
            'totalCobradoHoy',
            'vencenHoy',
            'vencidos',
        ));
    }
}