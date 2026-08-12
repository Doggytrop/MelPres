<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Http\Requests\StoreCustomerDocumentRequest;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CustomerDocumentController extends Controller
{
    public function store(StoreCustomerDocumentRequest $request, Customer $customer)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $customer = Customer::where('customers.id', $customer->getKey())
            ->where('customers.company_id', $companyId)
            ->firstOrFail();

        $data = $request->validated();
        $file = $request->file('file');

        $folder = "companies/{$companyId}/customers/{$customer->id}/documents";
        $path = $file->store($folder, 'local');

        if (! $path) {
            abort(500, 'No fue posible almacenar el documento.');
        }

        try {
            CustomerDocument::create([
                'company_id'    => $companyId,
                'customer_id'   => $customer->id,
                'type'          => $data['type'],
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'notes'         => $data['notes'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Documento subido correctamente.');
    }

    public function view(CustomerDocument $customerDocument)
    {
        [$document, $disk, $path] = $this->resolveAuthorizedDocument($customerDocument);

        return response()->download(
            Storage::disk($disk)->path($path),
            $this->safeOriginalName($document->original_name),
            $this->responseHeaders($document),
            'inline'
        );
    }

    public function download(CustomerDocument $customerDocument)
    {
        [$document, $disk, $path] = $this->resolveAuthorizedDocument($customerDocument);

        return response()->download(
            Storage::disk($disk)->path($path),
            $this->safeOriginalName($document->original_name),
            $this->responseHeaders($document)
        );
    }

    public function destroy(Customer $customer, CustomerDocument $document)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $customer = Customer::where('customers.id', $customer->getKey())
            ->where('customers.company_id', $companyId)
            ->firstOrFail();

        $document = CustomerDocument::where('customer_documents.id', $document->getKey())
            ->where('customer_documents.company_id', $companyId)
            ->where('customer_documents.customer_id', $customer->getKey())
            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
            ->firstOrFail();

        $path = $this->safeRelativePath($document->path);
        $disk = $this->locateDisk($path);
        $isShared = CustomerDocument::where('customer_documents.id', '!=', $document->getKey())
            ->where('customer_documents.path', $document->path)
            ->exists();

        if ($disk && ! $isShared && ! Storage::disk($disk)->delete($path)) {
            abort(500, 'No fue posible eliminar el archivo del documento.');
        }

        $document->delete();

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Documento eliminado correctamente.');
    }

    private function resolveAuthorizedDocument(CustomerDocument $customerDocument): array
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $document = CustomerDocument::where('customer_documents.id', $customerDocument->getKey())
            ->where('customer_documents.company_id', $companyId)
            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
            ->firstOrFail();

        $path = $this->safeRelativePath($document->path);
        $disk = $this->locateDisk($path);

        if (! $disk) {
            abort(404);
        }

        return [$document, $disk, $path];
    }

    private function locateDisk(string $path): ?string
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    private function safeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === ''
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path)
            || preg_match('#(^|/)\.\.(/|$)#', $path)
            || str_contains($path, "\0")) {
            abort(404);
        }

        return $path;
    }

    private function safeOriginalName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', $name ?: 'documento'));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        return $name !== '' ? $name : 'documento';
    }

    private function responseHeaders(CustomerDocument $document): array
    {
        $mimeType = is_string($document->mime_type)
            && preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', $document->mime_type)
                ? $document->mime_type
                : 'application/octet-stream';

        return [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
