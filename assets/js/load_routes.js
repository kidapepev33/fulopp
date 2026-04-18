document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("rutas-container");

    fetch("../includes/functions/load_routes.php")
        .then(res => res.json())
        .then(data => {
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
            container.innerHTML = "Error cargando rutas";
        });
});