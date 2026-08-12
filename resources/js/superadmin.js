document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-superadmin-nav-toggle]');
    const menu = document.getElementById('superadmin-navigation');

    if (toggle && menu) {
        const closeMenu = () => {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            const isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        menu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
                toggle.focus();
            }
        });
    }

    // --- Confirmación de acciones sensibles (data-confirm-submit) ---
    const confirmModal = document.getElementById('confirm-modal');

    if (confirmModal) {
        const titleEl = document.getElementById('confirm-modal-title');
        const messageEl = document.getElementById('confirm-modal-message');
        const acceptBtn = document.getElementById('confirm-modal-accept');
        const cancelBtn = document.getElementById('confirm-modal-cancel');

        let pendingForm = null;

        document.querySelectorAll('[data-confirm-submit]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                pendingForm = form;
                titleEl.textContent = form.dataset.confirmTitle || 'Confirmar acción';
                messageEl.textContent = form.dataset.confirmMessage || '¿Estás seguro?';
                confirmModal.style.display = 'flex';
            });
        });

        acceptBtn.addEventListener('click', () => {
            if (pendingForm) {
                confirmModal.style.display = 'none';
                pendingForm.submit();
            }
        });

        cancelBtn.addEventListener('click', () => {
            pendingForm = null;
            confirmModal.style.display = 'none';
        });
    }
});