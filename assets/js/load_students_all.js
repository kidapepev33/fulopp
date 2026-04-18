document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector(".students-table tbody");
    const searchInput = document.getElementById("students-search");
    const scanButton = document.getElementById("scan-barcode-button");
    const scannerModal = document.getElementById("scanner-modal");
    const scannerVideo = document.getElementById("scanner-video");
    const scannerFallbackTarget = document.getElementById("scanner-fallback-target");
    const scannerClose = document.getElementById("scanner-close");
    const scannerMessage = document.getElementById("scanner-message");
    const scannerResult = document.getElementById("scanner-result");

    if (!tableBody) return;

    const STUDENT_CODE_REGEX = /^EST-\d{4}$/i;
    const REQUIRED_HITS = 2;

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
        if (["1", "si", "s", "true", "yes"].includes(normalized)) return "Si";
        if (["0", "no", "n", "false"].includes(normalized)) return "No";
        return toSafeText(value);
    };

    const getInitials = name => {
        const safeName = String(name ?? "").trim();
        if (!safeName) return "?";
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
            tableBody.innerHTML = '<tr class="table-placeholder"><td colspan="7">No hay estudiantes registrados</td></tr>';
            return;
        }

        tableBody.innerHTML = rows.map(student => `
            <tr>
                <td class="td-photo" >${getPhotoCell(student)}</td>
                <td class="td-cedula" >${escapeHtml(toSafeText(student.cedula))}</td>
                <td class="td-nombre" >${escapeHtml(toSafeText(student.nombre))}</td>
                <td class="td-seccion" >${escapeHtml(toSafeText(student.seccion))}</td>
                <td class="td-beca" >${escapeHtml(toBecadoText(student.becado))}</td>
                <td class="td-qr_barcode" >${getBarcodeCell(student)}</td>
                <td><a class="action-link" href="student.html?id=${encodeURIComponent(student.id)}">Editar</a></td>
            </tr>
        `).join("");
    };

    const setScannerMessage = text => {
        if (scannerMessage) scannerMessage.textContent = text;
    };

    const setResult = student => {
        if (!scannerResult) return;
        if (!student) {
            scannerResult.innerHTML = "";
            return;
        }
        scannerResult.innerHTML = `
            <div class="scan-result-card">
                <strong>${escapeHtml(toSafeText(student.nombre))}</strong>
                <span>Cedula: ${escapeHtml(toSafeText(student.cedula))}</span>
                <span>Seccion: ${escapeHtml(toSafeText(student.seccion))}</span>
                <span>Codigo: ${escapeHtml(toSafeText(student.codigo_barras))}</span>
            </div>
        `;
    };

    let allStudents = [];
    let scanLocked = false;
    let quaggaRunning = false;
    let quaggaDetectedHandler = null;
    let lastCandidateCode = "";
    let candidateHits = 0;

    if (scannerModal) scannerModal.hidden = true;
    if (scannerVideo) scannerVideo.hidden = true;

    const normalizeDetectedCode = rawValue => {
        let normalized = String(rawValue ?? "").trim().toUpperCase();
        normalized = normalized.replace(/^\*+|\*+$/g, "");
        normalized = normalized.replace(/\s+/g, "");
        return normalized;
    };

    const stopQuagga = () => {
        if (!window.Quagga) return;
        if (quaggaDetectedHandler) {
            try { window.Quagga.offDetected(quaggaDetectedHandler); } catch (_) { }
            quaggaDetectedHandler = null;
        }
        if (quaggaRunning) {
            try { window.Quagga.stop(); } catch (_) { }
        }
        quaggaRunning = false;
        if (scannerFallbackTarget) {
            scannerFallbackTarget.innerHTML = "";
            scannerFallbackTarget.hidden = true;
        }
    };

    const stopScanner = () => {
        stopQuagga();
        if (scannerVideo) {
            scannerVideo.srcObject = null;
            scannerVideo.hidden = true;
        }
        if (scannerModal) scannerModal.hidden = true;
        scanLocked = false;
        lastCandidateCode = "";
        candidateHits = 0;
    };

    const findStudentByBarcode = value => {
        const target = String(value ?? "").trim().toLowerCase();
        if (!target) return null;
        return allStudents.find(student => String(student.codigo_barras ?? "").trim().toLowerCase() === target) || null;
    };

    const applySearch = value => {
        if (!searchInput) return;
        searchInput.value = value;
        searchInput.dispatchEvent(new Event("input"));
    };

    const handleDetectedCode = rawValue => {
        if (scanLocked) return;

        const normalized = normalizeDetectedCode(rawValue);
        if (!STUDENT_CODE_REGEX.test(normalized)) return;

        if (normalized === lastCandidateCode) {
            candidateHits += 1;
        } else {
            lastCandidateCode = normalized;
            candidateHits = 1;
        }

        if (candidateHits < REQUIRED_HITS) {
            setScannerMessage(`Confirmando codigo ${normalized}... (${candidateHits}/${REQUIRED_HITS})`);
            return;
        }

        scanLocked = true;
        applySearch(normalized);

        const found = findStudentByBarcode(normalized);
        if (found) {
            setResult(found);
            setScannerMessage("Codigo detectado correctamente.");
        } else {
            setResult(null);
            setScannerMessage("Codigo detectado, pero no existe en la base de datos.");
        }

        stopScanner();
    };

    const startQuagga = async () => {
        if (!window.Quagga || !scannerFallbackTarget) return false;

        const attempts = [
            { facingMode: { exact: "environment" }, width: { ideal: 1280 }, height: { ideal: 720 } },
            { facingMode: { ideal: "environment" }, width: { ideal: 1280 }, height: { ideal: 720 } },
            { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 720 } },
            true
        ];

        scannerFallbackTarget.hidden = false;
        if (scannerVideo) scannerVideo.hidden = true;

        for (let i = 0; i < attempts.length; i++) {
            const constraints = attempts[i];
            const ok = await new Promise(resolve => {
                window.Quagga.init({
                    inputStream: {
                        type: "LiveStream",
                        target: scannerFallbackTarget,
                        constraints
                    },
                    locator: {
                        patchSize: "small",
                        halfSample: false
                    },
                    numOfWorkers: navigator.hardwareConcurrency > 2 ? 2 : 1,
                    frequency: 10,
                    decoder: {
                        readers: ["code_39_reader"]
                    },
                    locate: true
                }, err => resolve(!err));
            });

            if (!ok) continue;

            try {
                window.Quagga.start();
                quaggaRunning = true;
                setScannerMessage("Camara activa. Apunta al codigo de barras.");

                quaggaDetectedHandler = data => {
                    const code = data && data.codeResult ? data.codeResult.code : "";
                    if (code) handleDetectedCode(code);
                };
                window.Quagga.onDetected(quaggaDetectedHandler);

                return true;
            } catch (_) {
                stopQuagga();
            }
        }

        return false;
    };

    const openScanner = async () => {
        setScannerMessage("");
        setResult(null);
        scanLocked = false;
        lastCandidateCode = "";
        candidateHits = 0;

        if (!scannerModal) return;
        scannerModal.hidden = false;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setScannerMessage("No se puede usar camara. Abre el sitio en HTTPS o localhost y revisa permisos.");
            return;
        }

        const quaggaOk = await startQuagga();
        if (quaggaOk) return;

        setScannerMessage("No se pudo iniciar el escaner. Revisa permisos de camara.");
    };

    if (scannerClose) {
        scannerClose.addEventListener("click", () => {
            stopScanner();
            setScannerMessage("");
        });
    }

    if (scannerModal) {
        scannerModal.addEventListener("click", event => {
            if (event.target === scannerModal) {
                stopScanner();
                setScannerMessage("");
            }
        });
    }

    if (scanButton) {
        scanButton.addEventListener("click", openScanner);
    }

    fetch("../includes/functions/load_students_all.php")
        .then(res => res.json())
        .then(data => {
            const students = Array.isArray(data) ? data : [];
            allStudents = students;
            renderRows(students);

            if (!searchInput) return;

            searchInput.addEventListener("input", event => {
                const term = event.target.value.trim().toLowerCase();

                if (!term) {
                    renderRows(students);
                    setResult(null);
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

                const exact = findStudentByBarcode(term);
                setResult(exact);
            });

            const params = new URLSearchParams(window.location.search);
            const initialTerm = params.get("q");
            if (initialTerm) {
                applySearch(initialTerm);
                const exact = findStudentByBarcode(initialTerm);
                setResult(exact);
            }
        })
        .catch(() => {
            tableBody.innerHTML = '<tr class="table-placeholder"><td colspan="7">Error cargando estudiantes</td></tr>';
        });
});
