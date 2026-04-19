document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("student-form-container");
    const toast = window.AppToast;
    const params = new URLSearchParams(window.location.search);
    const studentId = params.get("id");

    if (!container) {
        return;
    }

    if (!studentId) {
        container.innerHTML = '<div class="table-placeholder">Estudiante no valido.</div>';
        return;
    }

    const escapeHtml = value => String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");

    const labelize = key => key
        .replace(/_/g, " ")
        .replace(/\b\w/g, char => char.toUpperCase());

    const fieldType = columnType => {
        const type = String(columnType || "").toLowerCase();
        if (type.startsWith("tinyint(1)")) return "checkbox";
        if (type.includes("int")) return "number";
        if (type.includes("decimal") || type.includes("float") || type.includes("double")) return "number";
        if (type.includes("date") && !type.includes("time")) return "date";
        if (type.includes("datetime") || type.includes("timestamp")) return "datetime-local";
        if (type.includes("text")) return "textarea";
        return "text";
    };

    const toInputValue = (value, type) => {
        if (value === null || value === undefined) {
            return "";
        }
        if (type === "datetime-local") {
            const text = String(value).replace(" ", "T");
            return text.length >= 16 ? text.slice(0, 16) : "";
        }
        return String(value);
    };

    const renderField = (column, student, routes) => {
        const name = column.Field;
        const type = fieldType(column.Type);
        const required = column.Null === "NO" && column.Extra !== "auto_increment";
        const rawValue = student[name];

        if (column.Extra === "auto_increment") {
            return "";
        }

        if (name === "foto") {
            const currentPhoto = String(rawValue ?? "").trim();
            const emptyPreview = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='100%25' height='100%25' fill='%23204a7a'/%3E%3Ctext x='50%25' y='54%25' font-size='14' fill='white' text-anchor='middle' font-family='sans-serif'%3EFoto%3C/text%3E%3C/svg%3E";
            return `
                <div class="form-field">
                    <label for="foto">Foto</label>
                    <img id="foto-preview" class="student-photo-preview" src="${escapeHtml(currentPhoto || emptyPreview)}" alt="Preview de foto">
                    <input id="foto" name="foto" type="file" accept="image/*">
                </div>
            `;
        }

        if (name === "ruta_id") {
            const selectedValue = String(rawValue ?? "");
            const options = (Array.isArray(routes) ? routes : []).map(route => {
                const routeId = String(route.id ?? "");
                const selected = routeId === selectedValue ? "selected" : "";
                return `<option value="${escapeHtml(routeId)}" ${selected}>${escapeHtml(route.nombre ?? routeId)}</option>`;
            }).join("");

            return `
                <div class="form-field">
                    <label for="ruta_id">Ruta</label>
                    <select id="ruta_id" name="ruta_id" ${required ? "required" : ""}>
                        <option value="">Selecciona una ruta</option>
                        ${options}
                    </select>
                </div>
            `;
        }

        if (name === "codigo_barras") {
            return "";
        }

        if (type === "checkbox") {
            const checked = String(rawValue ?? "0") === "1" ? "checked" : "";
            return `
                <div class="form-field checkbox-field">
                    <label for="${escapeHtml(name)}">${escapeHtml(labelize(name))}</label>
                    <input id="${escapeHtml(name)}" name="${escapeHtml(name)}" type="checkbox" value="1" ${checked}>
                </div>
            `;
        }

        if (type === "textarea") {
            return `
                <div class="form-field">
                    <label for="${escapeHtml(name)}">${escapeHtml(labelize(name))}</label>
                    <textarea id="${escapeHtml(name)}" name="${escapeHtml(name)}" ${required ? "required" : ""}>${escapeHtml(toInputValue(rawValue, type))}</textarea>
                </div>
            `;
        }

        return `
            <div class="form-field">
                <label for="${escapeHtml(name)}">${escapeHtml(labelize(name))}</label>
                <input id="${escapeHtml(name)}" name="${escapeHtml(name)}" type="${type}" value="${escapeHtml(toInputValue(rawValue, type))}" ${required ? "required" : ""}>
            </div>
        `;
    };

    const attachSubmit = () => {
        const form = document.getElementById("student-edit-form");
        const fotoInput = document.getElementById("foto");
        const fotoPreview = document.getElementById("foto-preview");

        if (!form) {
            return;
        }

        if (fotoInput && fotoPreview) {
            fotoInput.addEventListener("change", () => {
                const file = fotoInput.files && fotoInput.files[0] ? fotoInput.files[0] : null;
                if (!file) {
                    return;
                }
                const fileUrl = URL.createObjectURL(file);
                fotoPreview.src = fileUrl;
            });
        }

        form.addEventListener("submit", event => {
            event.preventDefault();

            const formData = new FormData(form);
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(box => {
                formData.set(box.name, box.checked ? "1" : "0");
            });

            formData.set("id", studentId);

            fetch("../includes/functions/update_student.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(result => {
                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : "No se pudo guardar");
                    }
                    if (toast) toast.success("Cambios guardados correctamente.");
                })
                .catch(error => {
                    console.error("Error:", error);
                    if (toast) toast.error(error.message || "Error al guardar cambios.");
                });
        });
    };

    fetch(`../includes/functions/get_student.php?id=${encodeURIComponent(studentId)}`)
        .then(res => res.json())
        .then(payload => {
            if (!payload || !payload.student || !Array.isArray(payload.columns)) {
                const errorMessage = payload && payload.message ? payload.message : "No se encontro el estudiante.";
                container.innerHTML = `<div class="table-placeholder">${escapeHtml(errorMessage)}</div>`;
                return;
            }

            const fieldsHtml = payload.columns.map(column => renderField(column, payload.student, payload.routes)).join("");

            container.innerHTML = `
                <form id="student-edit-form" class="student-edit-form" enctype="multipart/form-data">
                    <div class="form-grid">
                        ${fieldsHtml}
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="action-link action-button">Guardar cambios</button>
                        <a class="action-link" href="informe.html">Volver al informe</a>
                    </div>
                </form>
            `;

            attachSubmit();
        })
        .catch(err => {
            console.error("Error:", err);
            container.innerHTML = '<div class="table-placeholder">Error cargando estudiante o sin permisos.</div>';
        });
});
