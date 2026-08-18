if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
            .catch(() => {
                // La aplicación sigue funcionando normalmente si el navegador no puede registrar la PWA.
            });
    });
}