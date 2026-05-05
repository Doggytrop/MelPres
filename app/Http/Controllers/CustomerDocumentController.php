<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Http\Requests\StoreCustomerDocumentRequest;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentController extends Controller
{
    public function store(StoreCustomerDocumentRequest $request, Customer $customer)
    {
        $file = $request->file('file');

        $folder = "customers/{$customer->id}/documents";
        $path = $file->store($folder, 'public');

        CustomerDocument::create([
            'customer_id'  => $customer->id,
            'type'         => $request->type,
            'original_name'=> $file->getClientOriginalName(),
            'path'         => $path,
            'mime_type'    => $file->getMimeType(),
            'size'         => $file->getSize(),
            'notes'        => $request->notes,
        ]);

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Documento subido correctamente.');
    }

    public function destroy(Customer $customer, CustomerDocument $document)
    {
        if ($document->customer_id !== $customer->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Documento eliminado correctamente.');
    }
}