<?php

namespace App\Http\Controllers;

use App\Models\customer;
use App\Models\customerDocumento;
use App\Http\Requests\StorecustomerDocumentoRequest;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentController extends Controller
{
    public function store(StorecustomerDocumentoRequest $request, customer $customer)
    {
        $archivo = $request->file('archivo');

        // Carpeta organizada por customer
        $carpeta = "customers/{$customer->id}/documentos";

        $ruta = $archivo->store($carpeta, 'public');

        customerDocumento::create([
            'customer_id'      => $customer->id,
            'tipo'            => $request->type,
            'original_name' => $archivo->getClientOriginalName(),
            'ruta'            => $ruta,
            'mime_type'       => $archivo->getMimeType(),
            'size'         => $archivo->getSize(),
            'notas'           => $request->notes,
        ]);

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Documento subido correctamente.');
    }

    public function destroy(customer $customer, customerDocumento $documento)
    {
        // Verificar que el documento pertenece al customer
        if ($documento->customer_id !== $customer->id) {
            abort(403);
        }

        // Eliminar archivo físico
        Storage::disk('public')->delete($documento->path);

        $documento->delete();

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Documento eliminado correctamente.');
    }
}