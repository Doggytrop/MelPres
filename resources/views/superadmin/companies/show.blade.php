@extends('superadmin.layouts.app')

@section('title', $company->name)

@section('content')
    @php
        $subscription = $company->subscription;
        $effectiveStatus = $subscription?->effectiveStatus();
        $statusValue = $effectiveStatus?->value;
        $companyStatus = \App\Support\SaasPresentation::companyStatus($company->status);
        $subscriptionStatus = \App\Support\SaasPresentation::subscriptionStatus($subscription);
        $canRenew = in_array($statusValue, ['active', 'past_due', 'suspended'], true);
        $canManageGrace = in_array($statusValue, ['active', 'past_due'], true);
        $canSuspend = in_array($statusValue, ['active', 'past_due'], true);
        $canReactivate = in_array($statusValue, ['suspended', 'cancelled'], true);
        $canCancel = in_array($statusValue, ['active', 'past_due', 'suspended'], true);
    @endphp

    <a href="{{ route('superadmin.companies.index') }}" class="text-muted" style="font-size:13px; text-decoration:none;">
    ← Volver a Empresas
    </a><p></p>

    <section class="company-heading superadmin-company-detail-heading">
        <div>
            <h1>{{ $company->name }}</h1>
            <div class="heading-statuses" aria-label="Estados de la empresa">
                <span class="status status-{{ $subscriptionStatus['tone'] }}">Suscripción: {{ $subscriptionStatus['label'] }}</span>
                <span class="status status-operation">Operación: {{ $companyStatus['label'] }}</span>
            </div>
        </div>
        <dl class="heading-meta">
            <div><dt>Administrador principal</dt><dd>{{ $company->primaryAdmin?->name ?? 'Sin administrador' }}</dd></div>
            <div><dt>Fecha de alta</dt><dd>{{ $company->created_at->format('d/m/Y') }}</dd></div>
        </dl>
    </section>

    <section class="summary-grid superadmin-metrics-grid superadmin-company-detail-metrics" aria-label="Resumen de la empresa">
        <article class="summary-card superadmin-metric-card superadmin-metric-card--wide">
            <span class="summary-card-label">Próxima renovación</span>
            <strong class="summary-card-value">{{ $subscription?->current_period_end?->format('d/m/Y') ?? 'Sin vencimiento' }}</strong>
            @if ($subscription?->current_period_end)
                <small class="summary-card-note">{{ \App\Support\SaasPresentation::renewalTiming($subscription) }}</small>
            @endif
        </article>
        <article class="summary-card superadmin-metric-card">
            <span class="summary-card-label">Suscripción</span>
            <strong class="summary-card-value">{{ $subscriptionStatus['label'] }}</strong>
            <small class="summary-card-note">{{ $subscription?->allowsAccess() ? 'Acceso permitido' : 'Acceso bloqueado' }}</small>
        </article>
        <article class="summary-card superadmin-metric-card">
            <span class="summary-card-label">Periodo de gracia</span>
            <strong class="summary-card-value">{{ $subscription?->grace_until?->format('d/m/Y') ?? 'Sin gracia' }}</strong>
            @if ($subscription?->grace_until)
                <small class="summary-card-note">{{ \App\Support\SaasPresentation::graceStatus($subscription) }}</small>
            @endif
        </article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">{{ $company->users_count === 1 ? 'Usuario' : 'Usuarios' }}</span><strong class="summary-card-value">{{ $company->users_count }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">{{ $company->customers_count === 1 ? 'Cliente' : 'Clientes' }}</span><strong class="summary-card-value">{{ $company->customers_count }}</strong></article>
        <article class="summary-card superadmin-metric-card"><span class="summary-card-label">{{ $company->loans_count === 1 ? 'Préstamo' : 'Préstamos' }}</span><strong class="summary-card-value">{{ $company->loans_count }}</strong></article>
    </section>

    @if ($subscription)
        <div class="commercial-grid">
            @if ($canRenew)
                <section class="panel commercial-panel">
                    <span class="section-kicker">Vigencia</span>
                    <h2>Renovar suscripción</h2>
                    <p class="section-copy">Extiende la vigencia del servicio a partir del vencimiento actual o de hoy si ya venció.</p>
                    <form method="POST" action="{{ route('superadmin.companies.renew', $company) }}">
                        @csrf
                        <div class="field">
                            <label for="subscription_years">Años pagados</label>
                            <select id="subscription_years" name="subscription_years" required data-renewal-base="{{ ($subscription->current_period_end?->isFuture() ? $subscription->current_period_end : now())->toDateString() }}">
                                @foreach ([1, 2, 3, 5] as $years)
                                    <option value="{{ $years }}">{{ $years }} {{ $years === 1 ? 'año' : 'años' }}</option>
                                @endforeach
                            </select>
                            @error('subscription_years') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <p class="estimate" id="renewal-estimate" aria-live="polite"></p>
                        <button class="sa-button sa-button--primary" type="submit">Registrar renovación</button>
                    </form>
                </section>
            @endif

            @if ($canManageGrace)
                <section class="panel commercial-panel">
                    <span class="section-kicker">Acceso temporal</span>
                    <h2>Periodo de gracia</h2>
                    <p class="section-copy">El periodo de gracia permite que la empresa continúe accediendo temporalmente después del vencimiento.</p>
                    <form method="POST" action="{{ route('superadmin.companies.grace.update', $company) }}">
                        @csrf
                        <div class="field">
                            <label for="grace_until">Gracia hasta</label>
                            <input id="grace_until" type="datetime-local" name="grace_until" required
                                   value="{{ old('grace_until', $subscription->grace_until?->format('Y-m-d\TH:i')) }}"
                                   min="{{ $subscription->current_period_end?->format('Y-m-d\TH:i') }}">
                            @error('grace_until') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="sa-button-group">
                            <button class="sa-button sa-button--secondary" type="submit">Actualizar gracia</button>
                        </div>
                    </form>
                    @if ($subscription->grace_until)
                        <form class="standalone-action" method="POST" action="{{ route('superadmin.companies.grace.remove', $company) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-button" type="submit">Quitar periodo de gracia</button>
                        </form>
                    @endif
                </section>
            @endif
        </div>

        <section class="panel sensitive-zone">
    <span class="section-kicker">Control de acceso</span>
    <h2>Acciones de suscripción</h2>
    <div class="sensitive-actions">
        @if ($canReactivate)
            <div class="sensitive-action">
                <div>
                    <strong>Reactivar empresa</strong>
                    <p>Restablece el estado comercial; el vencimiento y la gracia siguen determinando el acceso.</p>
                </div>
                <form method="POST"
                      action="{{ route('superadmin.companies.reactivate', $company) }}"
                      data-confirm-submit
                      data-confirm-title="Reactivar empresa"
                      data-confirm-message="¿Confirmas reactivar esta empresa? El vencimiento y la gracia seguirán determinando el acceso real.">
                    @csrf
                    <button class="sa-button sa-button--primary" type="submit">Reactivar</button>
                </form>
            </div>
        @endif

        @if ($canSuspend)
            <div class="sensitive-action">
                <div>
                    <strong>Suspender empresa</strong>
                    <p>Bloquea temporalmente el acceso de todos los usuarios de esta empresa sin eliminar sus datos.</p>
                </div>
                <form method="POST"
                      action="{{ route('superadmin.companies.suspend', $company) }}"
                      data-confirm-submit
                      data-confirm-title="Suspender empresa"
                      data-confirm-message="¿Confirmas la suspensión temporal de esta empresa? Los usuarios no podrán acceder hasta que la reactives.">
                    @csrf
                    <button class="sa-button sa-button--warning" type="submit">Suspender</button>
                </form>
            </div>
        @endif

        @if ($canCancel)
            <div class="sensitive-action">
                <div>
                    <strong>Cancelar suscripción</strong>
                    <p>Bloquea el acceso y marca la suscripción como cancelada. Los datos permanecen almacenados.</p>
                </div>
                <form method="POST"
                      action="{{ route('superadmin.companies.cancel', $company) }}"
                      data-confirm-submit
                      data-confirm-title="Cancelar suscripción"
                      data-confirm-message="¿Confirmas la cancelación de esta suscripción? El acceso se bloqueará y la suscripción quedará marcada como cancelada.">
                    @csrf
                    <button class="sa-button sa-button--danger" type="submit">Cancelar</button>
                </form>
            </div>
        @endif
    </div>
</section>
    @else
        <div class="alert alert-error">Esta empresa no tiene una suscripción asociada.</div>
    @endif

    <script>
        (() => {
            const field = document.getElementById('subscription_years');
            const output = document.getElementById('renewal-estimate');
            if (!field || !output) return;

            const updateEstimate = () => {
                const base = new Date(`${field.dataset.renewalBase}T12:00:00`);
                base.setFullYear(base.getFullYear() + Number(field.value));
                output.textContent = `Nueva fecha estimada: ${new Intl.DateTimeFormat('es-MX').format(base)}`;
            };
            field.addEventListener('change', updateEstimate);
            updateEstimate();
        })();
    </script>
@endsection
