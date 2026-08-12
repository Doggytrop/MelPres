@extends('superadmin.layouts.app')

@section('title', 'Editar empresa')

@section('content')
    <h1>Editar {{ $company->name }}</h1>
    <form method="POST" action="{{ route('superadmin.companies.update', $company) }}">
        @csrf
        @method('PUT')
        @include('superadmin.companies._form', ['company' => $company])
    </form>
@endsection
