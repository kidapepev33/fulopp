document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    const toast = window.AppToast;
    const appPrefix = window.location.pathname.startsWith('/fulopp/') ? '/fulopp' : '';

    const parseJsonSafely = async response => {
        const raw = await response.text();
        try {
            return JSON.parse(raw);
        } catch (error) {
            const preview = raw ? raw.slice(0, 180) : 'respuesta vacia';
            throw new Error(`Respuesta no valida del servidor: ${preview}`);
        }
    };

    if (!form) {
        return;
    }

    fetch('../../includes/auth/session_status.php', { credentials: 'same-origin' })
        .then(parseJsonSafely)
        .then(data => {
            if (data && data.logged_in) {
                const params = new URLSearchParams(window.location.search);
                const next = params.get('next');
                if (next && (next.startsWith('/fulopp/') || next.startsWith('/pages/'))) {
                    window.location.href = next;
                } else {
                    window.location.href = appPrefix + '/pages/rutas.html';
                }
            }
        })
        .catch(() => {
            // no-op
        });

    form.addEventListener('submit', event => {
        event.preventDefault();

        const formData = new FormData(form);
        if (toast) toast.info('Validando credenciales...');

        fetch('../../includes/auth/login.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(parseJsonSafely)
            .then(result => {
                if (!result || !result.success) {
                    throw new Error(result && result.message ? result.message : 'No se pudo iniciar sesion');
                }

                const params = new URLSearchParams(window.location.search);
                const next = params.get('next');
                const safeNext = next && (next.startsWith('/fulopp/') || next.startsWith('/pages/')) ? next : null;
                window.location.href = safeNext || result.redirect || (appPrefix + '/pages/rutas.html');
            })
            .catch(error => {
                if (toast) {
                    toast.error(error.message || 'Credenciales invalidas.');
                }
            });
    });
});
