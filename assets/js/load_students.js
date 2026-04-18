document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector(".students-table tbody");
    const searchInput = document.getElementById("students-search");

    const params = new URLSearchParams(window.location.search);
    const id = params.get("id");

    if (!tableBody) {
        return;
    }

    if (!id) {
        tableBody.innerHTML = '<tr class="table-placeholder"><td colspan="6">Ruta no valida</td></tr>';
        return;
    }

    fetch(`../includes/functions/load_students.php?ruta_id=${id}`)
        .then(res => res.json())
        .then(data => {
            const students = Array.isArray(data) ? data : [];

            const escapeHtml = value => String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/\"/g, "&quot;")
                .replace(/'/g, "&#39;");

            const toSafeText = value => (value === null || value === undefined || value === "") ? "-" : String(value);
            const toBarcodePath = student => `../assets/images/Qr_student/barcode_${encodeURIComponent(String(student.id ?? ""))}.png`;
            const toBarcodeSvgPath = student => `../assets/images/Qr_student/barcode_${encodeURIComponent(String(student.id ?? ""))}.svg`;

            const toBecadoText = value => {
                const normalized = String(value ?? "").trim().toLowerCase();
                if (["1", "si", "s", "true", "yes"].includes(normalized)) {
                    return "Si";
                }
                if (["0", "no", "n", "false"].includes(normalized)) {
                    return "No";
                }
                return toSafeText(value);
            };

            const getInitials = name => {
                const safeName = String(name ?? "").trim();
                if (!safeName) {
                    return "?";
                }
                return safeName
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(part => part.charAt(0).toUpperCase())
                    .join("");
            };

            const getPhotoCell = student => {
                const photo = String(student.foto ?? "").trim();
                if (photo) {
                    return `<img class="student-photo" src="${escapeHtml(photo)}" alt="Foto de ${escapeHtml(toSafeText(student.nombre))}">`;
                }
                return `<div class="student-photo placeholder">${escapeHtml(getInitials(student.nombre))}</div>`;
            };

            const getBarcodeCell = student => {
                const safeCode = escapeHtml(toSafeText(student.codigo_barras));
                if (!student.id) {
                    return `<span class="barcode-text">${safeCode}</span>`;
                }
                return `
                    <div class="barcode-wrap">
                        <img
                            class="barcode-img"
                            src="${escapeHtml(toBarcodePath(student))}"
                            alt="Codigo de barras ${safeCode}"
                            onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='${escapeHtml(toBarcodeSvgPath(student))}';}else{this.style.display='none';}"
                        >
                    </div>
                `;
            };

            const renderRows = rows => {
                if (!rows.length) {
                    tableBody.innerHTML = '<tr class="table-placeholder"><td colspan="6">No hay estudiantes en esta ruta</td></tr>';
                    return;
                }

                tableBody.innerHTML = rows.map(student => `
                    <tr>
                        <td>${getPhotoCell(student)}</td>
                        <td>${escapeHtml(toSafeText(student.cedula))}</td>
                        <td>${escapeHtml(toSafeText(student.nombre))}</td>
                        <td>${escapeHtml(toSafeText(student.seccion))}</td>
                        <td>${escapeHtml(toBecadoText(student.becado))}</td>
                        <td>${getBarcodeCell(student)}</td>
                    </tr>
                `).join("");
            };

            renderRows(students);

            if (!searchInput) {
                return;
            }

            searchInput.addEventListener("input", event => {
                const term = event.target.value.trim().toLowerCase();

                if (!term) {
                    renderRows(students);
                    return;
                }

                const filtered = students.filter(student => {
                    const joined = [
                        student.nombre,
                        student.cedula,
                        student.seccion,
                        student.codigo_barras
                    ].map(field => String(field ?? "").toLowerCase());

                    return joined.some(value => value.includes(term));
                });

                renderRows(filtered);
            });
        })
        .catch(err => {
            console.error("Error:", err);
            tableBody.innerHTML = '<tr class="table-placeholder"><td colspan="6">Error cargando estudiantes</td></tr>';
        });
});
