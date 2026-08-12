@extends('superadmin.layouts.app')

@section('title', 'Empresas')

@section('content')
    <div class="page-heading superadmin-companies-heading">
        <div><h1>Empresas</h1><p>Consulta el estado comercial y operativo de cada empresa.</p></div>
        <a class="sa-button sa-button--primary" href="{{ route('superadmin.companies.create') }}">Nueva empresa</a>
    </div>

    <section class="summary-grid summary-grid-four superadmin-metrics-grid" aria-label="Resumen de empresas">
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Total de empresas</span><strong class="summary-card-value">{{ $summary['total'] }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Activas</span><strong class="summary-card-value">{{ $summary['active'] }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Pago pendiente</span><strong class="summary-card-value">{{ $summary['past_due'] }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Suspendidas</span><strong class="summary-card-value">{{ $summary['suspended'] }}</strong></article>
    </section>

    <form class="filter-bar" method="GET" action="{{ route('superadmin.companies.index') }}">
        <div class="field"><label for="search">Buscar por nombre</label><input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nombre de la empresa"></div>
        <div class="field">
            <label for="subscription_status">Estado de suscripción</label>
            <select id="subscription_status" name="subscription_status">
                <option value="">Todos</option>
                @foreach (['active', 'past_due', 'suspended', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['subscription_status'] ?? '') === $status)>
                        {{ \App\Support\SaasPresentation::subscriptionStatus($status)['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="sa-button-group"><button class="sa-button sa-button--primary" type="submit">Filtrar</button><a class="sa-button sa-button--secondary" href="{{ route('superadmin.companies.index') }}">Limpiar</a></div>
    </form>

    <div class="table-wrap sa-table-scroll sa-table-scroll--companies">
        <table class="company-table">
            <thead><tr><th>Empresa</th><th>Estado operativo</th><th>Suscripción</th><th>Próxima renovación</th><th>Administrador principal</th><th>Usuarios</th><th>Clientes</th><th>Préstamos</th><th></th></tr></thead>
            <tbody>
                @forelse ($companies as $company)
                    @php
                        $companyStatus = \App\Support\SaasPresentation::companyStatus($company->status);
                        $subscriptionStatus = \App\Support\SaasPresentation::subscriptionStatus($company->subscription);
                    @endphp
                    <tr>
                        <td><strong>{{ $company->name }}</strong><div class="muted">Alta: {{ $company->created_at->format('d/m/Y') }}</div></td>
                        <td><span class="status status-{{ $companyStatus['tone'] }}">{{ $companyStatus['label'] }}</span></td>
                        <td><span class="status status-{{ $subscriptionStatus['tone'] }}">{{ $subscriptionStatus['label'] }}</span></td>
                        <td><div>{{ $company->subscription?->current_period_end?->format('d/m/Y') ?? 'Sin vencimiento' }}</div>@if ($company->subscription?->current_period_end)<div class="muted">{{ \App\Support\SaasPresentation::renewalTiming($company->subscription) }}</div>@endif</td>
                        <td>{{ $company->primaryAdmin?->name ?? 'Sin administrador' }}</td>
                        <td>{{ $company->users_count }}</td><td>{{ $company->customers_count }}</td><td>{{ $company->loans_count }}</td>
                        <td><a class="sa-button sa-button--secondary table-link" href="{{ route('superadmin.companies.show', $company) }}">Ver detalles</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9">No se encontraron empresas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $companies->links() }}</div>
@endsection
