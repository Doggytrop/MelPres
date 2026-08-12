@extends('superadmin.layouts.app')

@section('title', 'Auditoría MelPres')

@section('content')
    <div class="page-heading superadmin-audit-heading">
        <div><h1>Auditoría MelPres</h1><p>Historial de las acciones comerciales realizadas por Superadmin.</p></div>
    </div>

    <form class="panel audit-filter-card" method="GET" action="{{ route('superadmin.activity-logs.index') }}">
        <div class="audit-filter-content">
            <div class="audit-filter-form">
                <div class="field audit-filter-field">
                    <label for="action">Acción</label>
                    <select class="audit-filter-select" id="action" name="action">
                        <option value="">Todas</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>
                                {{ \App\Support\SaasPresentation::actionLabel($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="audit-filter-actions">
                    <div class="sa-button-group">
                        <button class="sa-button sa-button--primary" type="submit">Filtrar</button>
                        <a class="sa-button sa-button--secondary" href="{{ route('superadmin.activity-logs.index') }}">Limpiar</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="table-wrap audit-table-card">
        <div class="sa-table-scroll audit-table-scroll">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Acción</th>
                    <th>Actor</th>
                    <th>Sujeto</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td><span class="status status-{{ \App\Support\SaasPresentation::actionTone($log->action) }}">{{ \App\Support\SaasPresentation::actionLabel($log->action) }}</span></td>
                        <td>{{ $log->actor?->name ?? $log->actor_name ?? 'Actor eliminado' }}</td>
                        <td>
                            <div>{{ \App\Support\SaasPresentation::subjectName($log->subject) ?? \App\Support\SaasPresentation::subjectLabel($log->subject_type) }}</div>
                            <div class="muted">ID: {{ $log->subject_id ?? 'N/D' }}</div>
                        </td>
                        <td>{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay actividad SaaS registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="pagination-wrap audit-pagination-wrap">{{ $logs->links() }}</div>
@endsection
