<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'MelPres') — {{ $config_sistema['negocio_nombre'] ?? 'MelPres' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    @php
        $colorPrimario   = $config_sistema['color_primario'] ?? '#1f6b21';
        $colorSecundario = $config_sistema['color_secundario'] ?? '#e8f5e9';
    @endphp

    <style>
        :root {
            --color-primary: {{ $colorPrimario }};
            --color-secondary: {{ $colorSecundario }};
        }

        body { background: #f8f9f8; }

        .nav-link:hover {
            background: {{ $colorSecundario }} !important;
            color: {{ $colorPrimario }} !important;
        }

        .nav-link.text-white {
            background: {{ $colorPrimario }} !important;
        }

        .dropdown-item:active,
        .dropdown-item:focus {
            background-color: {{ $colorSecundario }} !important;
            color: {{ $colorPrimario }} !important;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            vertical-align: middle;
            white-space: nowrap;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .table tbody tr {
            min-height: 72px;
        }

        .table td > .d-flex {
            align-items: center;
        }

        .table td form {
            margin-bottom: 0;
        }

        .table td a,
        .table td button {
            line-height: 1.2;
        }

        .table td a[style*="border"],
        .table td button[style*="border"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            white-space: nowrap;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .page-header {
            gap: 1rem;
        }

        .page-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        @media (max-width: 575.98px) {
            .page-header {
                align-items: flex-start !important;
                flex-wrap: wrap;
                margin-bottom: .75rem !important;
            }

            .page-header > div:first-child {
                min-width: 0;
                flex: 1 1 140px;
            }

            .page-actions {
                flex: 1 1 100%;
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .5rem !important;
                align-items: start;
            }

            .page-actions > *,
            .page-actions .btn,
            .page-actions form,
            .page-actions .form-control {
                width: 100%;
            }

            .page-actions.page-actions-single {
                flex: 0 0 auto;
                display: flex !important;
                align-items: flex-start;
                justify-content: flex-end;
                max-width: 55%;
            }

            .page-actions.page-actions-single > *,
            .page-actions.page-actions-single .btn {
                width: auto;
                min-width: 150px;
            }

            .page-actions .btn {
                align-items: center;
                justify-content: center;
                white-space: normal;
                text-align: center;
                min-height: 0;
                line-height: 1.25;
                padding-top: .55rem !important;
                padding-bottom: .55rem !important;
            }

            .table-responsive {
                border-radius: inherit;
            }

            .table-responsive .table {
                min-width: 680px;
            }
        }

        /* Sidebar desktop */
        @media (min-width: 992px) {
            #sidebarOffcanvas {
                position: sticky;
                top: 0;
                height: 100vh;
                width: 240px;
                min-width: 240px;
                display: flex !important;
                flex-direction: column;
                visibility: visible !important;
                transform: none !important;
            }
            .offcanvas-backdrop { display: none !important; }
            #btnSidebar { display: none; }
        }

        /* Botones dinámicos */
        .btn-primary-dynamic {
            background: {{ $colorPrimario }} !important;
            color: white !important;
            border: none;
        }

        .btn-primary-dynamic:hover {
            filter: brightness(1.1);
        }

        /* Links dinámicos */
        a.text-primary-dynamic {
            color: {{ $colorPrimario }} !important;
        }

        /* Badges dinámicos */
        .badge-primary-dynamic {
            background: {{ $colorSecundario }} !important;
            color: {{ $colorPrimario }} !important;
        }

        /* Progress bars */
        .progress-dynamic {
            background: {{ $colorPrimario }} !important;
        }

        .confirm-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fdecea;
            color: #c0392b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

    <div class="d-flex vh-100 overflow-hidden">

        {{-- Sidebar --}}
        <div class="offcanvas offcanvas-start border-end bg-white"
             tabindex="-1"
             id="sidebarOffcanvas"
             style="width: 240px;">
            @include('partials.sidebar')
        </div>

        {{-- Zona derecha --}}
        <div class="d-flex flex-column flex-grow-1 overflow-hidden">

            {{-- Navbar superior --}}
            @include('partials.navbar')

            {{-- Contenido --}}
            <main class="flex-grow-1 overflow-auto p-3 p-md-4">
                @yield('content')
            </main>

        </div>
    </div>

    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-3 shadow-sm">
                <div class="modal-body p-4">
                    <div class="d-flex gap-3">
                        <div class="confirm-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <path d="M12 9v4M12 17h.01"/>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-medium mb-1" id="confirmActionTitle" style="color:#1a2e1a;">Confirmar acción</h6>
                            <p class="text-muted mb-0" id="confirmActionMessage" style="font-size:13px;">
                                Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-sm"
                            data-bs-dismiss="modal"
                            style="background:#f5f5f5; color:#555; border-radius:8px; padding:8px 16px;">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-sm" id="confirmActionButton"
                            style="background:#c0392b; color:white; border-radius:8px; padding:8px 16px;">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('confirmActionModal');
            if (!modalElement) return;

            const modal = new bootstrap.Modal(modalElement);
            const title = document.getElementById('confirmActionTitle');
            const message = document.getElementById('confirmActionMessage');
            const confirmButton = document.getElementById('confirmActionButton');
            let pendingForm = null;

            document.addEventListener('submit', function(event) {
                const form = event.target.closest('form[data-confirm-submit]');
                if (!form) return;

                event.preventDefault();
                pendingForm = form;
                title.textContent = form.dataset.confirmTitle || 'Confirmar eliminación';
                message.textContent = form.dataset.confirmMessage || 'Esta acción no se puede deshacer.';
                confirmButton.textContent = form.dataset.confirmButton || 'Sí, eliminar';
                modal.show();
            });

            confirmButton.addEventListener('click', function() {
                if (!pendingForm) return;
                const form = pendingForm;
                pendingForm = null;
                modal.hide();
                HTMLFormElement.prototype.submit.call(form);
            });

            modalElement.addEventListener('hidden.bs.modal', function() {
                pendingForm = null;
            });
        });
    </script>
</body>
</html>
