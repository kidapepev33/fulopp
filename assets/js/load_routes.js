document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("rutas-container");
    const toast = window.AppToast;
    const parseJsonSafely = async response => {
        const raw = await response.text();
        try {
            return JSON.parse(raw);
        } catch (error) {
            const preview = raw ? raw.slice(0, 180) : "respuesta vacia";
            throw new Error(`Respuesta no valida del servidor (${response.status}): ${preview}`);
        }
    };

    fetch("../includes/functions/load_routes.php")
        .then(parseJsonSafely)
        .then(data => {
            if (!Array.isArray(data)) {
                throw new Error(data && data.message ? data.message : "No se pudieron cargar rutas");
            }
            container.innerHTML = ""; // quita "Cargando..."

            data.forEach(ruta => {
                const link = document.createElement("a");
                link.href = `ruta.html?id=${ruta.id}`;
                link.textContent = `${ruta.nombre} (${ruta.total})`;

                const div = document.createElement("div");
                div.appendChild(link);

                container.appendChild(div);
            });
        })
        .catch(err => {
            console.error("Error cargando rutas:", err);
            container.innerHTML = err.message || "Error cargando rutas";
            if (toast) toast.error(err.message || "Error cargando rutas");
        });
});
