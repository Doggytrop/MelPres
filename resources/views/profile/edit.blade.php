@extends('layouts.app')

@section('title', 'Perfil')

@section('content')

<div class="mb-4">
    <h5 class="fw-medium mb-0" style="color:#1a2e1a;">Mi perfil</h5>
    <span class="text-muted" style="font-size:13px;">Administra tu información y seguridad</span>
</div>

<div style="max-width:640px;" class="d-flex flex-column gap-4">

    <div class="bg-white border rounded-3 p-3 p-md-4" style="border-color:#e8e8e8 !important;">
        <h6 class="fw-medium mb-4" style="color:#1a2e1a; font-size:14px;">Información del perfil</h6>
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="bg-white border rounded-3 p-3 p-md-4" style="border-color:#e8e8e8 !important;">
        <h6 class="fw-medium mb-4" style="color:#1a2e1a; font-size:14px;">Cambiar contraseña</h6>
        @include('profile.partials.update-password-form')
    </div>

    <div class="bg-white border rounded-3 p-3 p-md-4" style="border-color:#e8e8e8 !important;">
        <h6 class="fw-medium mb-4" style="color:#c0392b; font-size:14px;">Zona de peligro</h6>
        @include('profile.partials.delete-user-form')
    </div>

</div>

@endsection