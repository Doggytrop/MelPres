<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $grupos = Configuracion::all()->groupBy('grupo');
        return view('configuracion.index', compact('grupos'));
    }

    public function update(Request $request)
    {
        $datos = $request->except(['_token', '_method']);

        foreach ($datos as $clave => $valor) {
            $config = Configuracion::where('clave', $clave)->first();
            if (!$config) continue;

            // Los checkboxes no envían valor si están desmarcados
            if ($config->tipo === 'boolean') {
                $valor = isset($datos[$clave]) ? '1' : '0';
            }

            $config->update(['valor' => $valor]);
        }

        // Procesar booleans que no vinieron en el request (desmarcados)
        Configuracion::where('tipo', 'boolean')->each(function($config) use ($datos) {
            if (!array_key_exists($config->clave, $datos)) {
                $config->update(['valor' => '0']);
            }
        });

        return redirect()->route('configuracion.index')
                         ->with('success', 'Configuración guardada correctamente.');
    }
}