@extends('superadmin.layouts.app')

@section('title', 'Dashboard Superadmin')

@section('content')
    <div class="page-heading superadmin-dashboard-heading">
        <div><h1>Resumen general</h1><p>Estado comercial de las empresas y actividad reciente.</p></div>
        <a class="sa-button sa-button--primary" href="{{ route('superadmin.companies.index') }}">Administrar empresas</a>
    </div>

    <section class="summary-grid dashboard-summary-grid superadmin-metrics-grid" aria-label="Indicadores SaaS">
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Empresas totales</span><strong class="summary-card-value">{{ $summary['total'] }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Activas</span><strong class="summary-card-value">{{ $summary['active'] }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Pago pendiente</span><strong class="summary-card-value">{{ $summary['past_due'] }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">Suspendidas</span><strong class="summary-card-value">{{ $summary['suspended'] }}</strong></article>
        <article class="summary-card superadmin-metric-card superadmin-metric-card--wide"><span class="summary-card-label">Renovaciones próximas</span><strong class="summary-card-value">{{ $summary['renewals_soon'] }}</strong><small class="summary-card-note">En los próximos 30 días</small></article>
    </section>

    <section class="panel">
        <div class="toolbar"><div><span class="section-kicker">Auditoría</span><h2>Últimas acciones SaaS</h2></div><a class="sa-button sa-button--secondary" href="{{ route('superadmin.activity-logs.index') }}">Ver toda la auditoría</a></div>
        <div class="table-wrap sa-table-scroll sa-table-scroll--dashboard">
            <table class="audit-table">
                <thead><tr><th>Fecha</th><th>Acción</th><th>Empresa</th><th>Actor</th></tr></thead>
                <tbody>
                    @forelse ($recentLogs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="status status-{{ \App\Support\SaasPresentation::actionTone($log->action) }}">{{ \App\Support\SaasPresentation::actionLabel($log->action) }}</span></td>
                            <td>{{ \App\Support\SaasPresentation::subjectName($log->subject) ?? 'Empresa no disponible' }}</td>
                            <td>{{ $log->actor?->name ?? $log->actor_name ?? 'Actor eliminado' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No hay actividad SaaS registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
