@extends('layouts.app')

@section('title', 'Nuevo Préstamo')

@section('content')

<div class="mb-4">
    <a href="{{ route('loans.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a préstamos
    </a>
</div>

<div class="bg-white border rounded-3 p-3 p-md-4" style="max-width:720px; border-color:#e8e8e8 !important;">

    <h6 class="fw-medium mb-4" style="color:#1a2e1a;">Registrar nuevo préstamo</h6>

    <form method="POST" action="{{ route('loans.store') }}">
        @csrf
        @include('loans._form', ['loan' => $loan, 'customers' => $customers])

        <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
            <button type="submit" class="btn btn-sm"
                    style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                Guardar préstamo
            </button>
            <a href="{{ route('loans.index') }}" class="btn btn-sm text-center"
               style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:8px 20px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection