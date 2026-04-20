const initAddStudentForm = () => {
    const form = document.getElementById("add-student-form");
    const rutaSelect = document.getElementById("ruta_id");
    const becadoInput = document.getElementById("becado");
    const toast = window.AppToast;

    if (!form || !rutaSelect || !becadoInput) {
        return;
    }

    const escapeHtml = value => String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");

    fetch("../includes/functions/load_routes.php")
        .then(res => res.json())
        .then(routes => {
            if (!Array.isArray(routes) || routes.length === 0) {
                rutaSelect.innerHTML = '<option value="">No hay rutas disponibles</option>';
                return;
            }

            const options = routes.map(route => {
                const id = String(route.id ?? "").trim();
                const name = String(route.nombre ?? id).trim();
                return '<option value="' + escapeHtml(id) + '">' + escapeHtml(name) + "</option>";
            }).join("");

            rutaSelect.innerHTML = '<option value="">Selecciona una ruta</option>' + options;
        })
        .catch(() => {
            rutaSelect.innerHTML = '<option value="">Error cargando rutas</option>';
            if (toast) toast.error("No se pudieron cargar rutas.");
        });

    form.addEventListener("submit", event => {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        const formData = new FormData(form);
        formData.set("becado", becadoInput.checked ? "1" : "0");

        fetch("../includes/functions/create_student.php", {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(result => {
                if (!result || !result.success) {
                    throw new Error(result && result.message ? result.message : "No se pudo guardar");
                }

                const barcode = String(result.codigo_barras || "").trim();
                if (toast) {
                    toast.success("Estudiante guardado. Codigo asignado: " + barcode);
                }
                form.reset();
            })
            .catch(error => {
                if (toast) {
                    toast.error(error.message || "Error al guardar estudiante.");
                }
            });
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAddStudentForm);
} else {
    initAddStudentForm();
}
