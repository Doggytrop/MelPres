@extends('layouts.app')

@section('title', 'Editar Asesor')

@section('content')

<div class="mb-4">
    <a href="{{ route('asesores.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
        ← Volver a asesores
    </a>
</div>

<div class="bg-white border rounded-3 p-4" style="max-width:540px; border-color:#e8e8e8 !important;">

    <h6 class="fw-medium mb-4" style="color:#1a2e1a;">Editar asesor — {{ $asesor->name }}</h6>

    <form method="POST" action="{{ route('asesores.update', $asesor) }}">
        @csrf @method('PUT')
        @include('asesores._form', ['asesor' => $asesor])

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-sm"
                    style="background:#1f6b21; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                Actualizar asesor
            </button>
            <a href="{{ route('asesores.index') }}" class="btn btn-sm"
               style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:8px 20px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection