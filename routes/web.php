<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ClienteDocumentoController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\AsesorController;
use App\Http\Controllers\CorteCajaController;
use App\Http\Controllers\SimuladorController;
use App\Http\Controllers\ReestructuracionController;
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('clientes', ClienteController::class);
    Route::get('prestamos/buscar-cliente', [PrestamoController::class, 'buscarCliente'])
     ->name('prestamos.buscar-cliente');
    Route::resource('prestamos', PrestamoController::class);
    Route::post('prestamos/{prestamo}/pagos', [PagoController::class, 'store'])
        ->name('prestamos.pagos.store');
    
       

    Route::post('clientes/{cliente}/documentos', [ClienteDocumentoController::class, 'store'])
        ->name('clientes.documentos.store');

    Route::delete('clientes/{cliente}/documentos/{documento}', [ClienteDocumentoController::class, 'destroy'])
        ->name('clientes.documentos.destroy');
    

    Route::get('historial', [HistorialController::class, 'index'])->name('historial.index');
    Route::get('historial/{prestamo}', [HistorialController::class, 'show'])->name('historial.show');
    });
    Route::get('historial/{prestamo}/pdf', [HistorialController::class, 'pdf'])
     ->name('historial.pdf');


    Route::get('configuracion', [ConfiguracionController::class, 'index'])
        ->name('configuracion.index');
    Route::post('configuracion', [ConfiguracionController::class, 'update'])
        ->name('configuracion.update');
        

    Route::get('corte-caja', [CorteCajaController::class, 'index'])->name('corte-caja.index');
    Route::get('corte-caja/pdf', [CorteCajaController::class, 'pdf'])->name('corte-caja.pdf');
    
    # Rutas para el simulador de préstamos
    Route::get('simulador', [SimuladorController::class, 'index'])->name('simulador.index');
    Route::post('simulador/calcular', [SimuladorController::class, 'calcular'])->name('simulador.calcular');
    


    // Reestructuración
    Route::get('reestructuracion/vencidos', [ReestructuracionController::class, 'vencidos'])
        ->name('reestructuracion.vencidos');
    Route::get('reestructuracion/activos', [ReestructuracionController::class, 'activos'])
        ->name('reestructuracion.activos');
    Route::get('reestructuracion/historial', [ReestructuracionController::class, 'historial'])
        ->name('reestructuracion.historial');
    Route::get('reestructuracion/{prestamo}/crear', [ReestructuracionController::class, 'create'])
        ->name('reestructuracion.create');
    Route::post('reestructuracion/{prestamo}/crear', [ReestructuracionController::class, 'store'])
        ->name('reestructuracion.store');
    Route::get('reestructuracion/pdf/{reestructuracion}', [ReestructuracionController::class, 'pdf'])
        ->name('reestructuracion.pdf');

    // Solo administrador
    Route::middleware(['auth', 'solo.admin'])->group(function () {
        Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])
            ->name('clientes.destroy');
        Route::delete('prestamos/{prestamo}', [PrestamoController::class, 'destroy'])
            ->name('prestamos.destroy');
        Route::get('configuracion', [ConfiguracionController::class, 'index'])
            ->name('configuracion.index');
        Route::post('configuracion', [ConfiguracionController::class, 'update'])
            ->name('configuracion.update');
        Route::resource('asesores', AsesorController::class)
            ->parameters(['asesores' => 'asesor']);
    });
require __DIR__.'/auth.php';
