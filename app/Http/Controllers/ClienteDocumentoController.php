<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteDocumento;
use App\Http\Requests\StoreClienteDocumentoRequest;
use Illuminate\Support\Facades\Storage;

class ClienteDocumentoController extends Controller
{
    public function store(StoreClienteDocumentoRequest $request, Cliente $cliente)
    {
        $archivo = $request->file('archivo');

        // Carpeta organizada por cliente
        $carpeta = "clientes/{$cliente->id}/documentos";

        $ruta = $archivo->store($carpeta, 'public');

        ClienteDocumento::create([
            'cliente_id'      => $cliente->id,
            'tipo'            => $request->tipo,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta'            => $ruta,
            'mime_type'       => $archivo->getMimeType(),
            'tamanio'         => $archivo->getSize(),
            'notas'           => $request->notas,
        ]);

        return redirect()->route('clientes.show', $cliente)
                         ->with('success', 'Documento subido correctamente.');
    }

    public function destroy(Cliente $cliente, ClienteDocumento $documento)
    {
        // Verificar que el documento pertenece al cliente
        if ($documento->cliente_id !== $cliente->id) {
            abort(403);
        }

        // Eliminar archivo físico
        Storage::disk('public')->delete($documento->ruta);

        $documento->delete();

        return redirect()->route('clientes.show', $cliente)
                         ->with('success', 'Documento eliminado correctamente.');
    }
}