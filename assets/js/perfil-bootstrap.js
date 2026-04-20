(() => {
    const setError = message => {
        const toast = window.AppToast;
        if (toast) {
            toast.error(message);
            return;
        }
        // fallback if toast is unavailable
        // eslint-disable-next-line no-console
        console.error(message);
    };

    const loadComponent = (selector, url) => {
        const target = document.querySelector(selector);
        if (!target) return Promise.resolve();

        return fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error("No se pudo cargar componente: " + url);
                }
                return response.text();
            })
            .then(html => {
                target.innerHTML = html;
            });
    };

    const loadScript = src => new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = src;
        script.onload = resolve;
        script.onerror = () => reject(new Error("No se pudo cargar script: " + src));
        document.body.appendChild(script);
    });

    const bootstrapPerfil = () => {
        Promise.all([
            loadComponent("#perfil-sidebar-slot", "../includes/components/perfil/sidebar.html"),
            loadComponent("#perfil-panels-slot", "../includes/components/perfil/panels.html")
        ])
            .then(() => loadScript("../assets/js/add_student.js"))
            .then(() => loadScript("../assets/js/perfil.js"))
            .catch(error => {
                setError(error.message || "No se pudo inicializar el modulo de gestion.");
            });
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", bootstrapPerfil);
    } else {
        bootstrapPerfil();
    }
})();
