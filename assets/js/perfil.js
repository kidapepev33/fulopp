const initPerfilPage = () => {
    const sidebarLinks = Array.from(document.querySelectorAll(".profile-nav-link"));
    const panels = Array.from(document.querySelectorAll("[data-panel]"));
    const adminOnlyLinks = Array.from(document.querySelectorAll(".profile-nav-link[data-admin-only='1']"));
    const adminOnlyPanels = Array.from(document.querySelectorAll("[data-panel][data-admin-only='1']"));
    const driverOnlyLinks = Array.from(document.querySelectorAll(".profile-nav-link[data-driver-only='1']"));
    const driverOnlyPanels = Array.from(document.querySelectorAll("[data-panel][data-driver-only='1']"));

    const profileMessage = document.getElementById("profile-message");
    const summaryDriverName = document.getElementById("summary-driver-name");
    const summaryVehicle = document.getElementById("summary-vehicle");
    const summaryVehicleCard = document.getElementById("summary-vehicle-card");
    const summaryRoutes = document.getElementById("summary-routes");
    const summaryEmail = document.getElementById("summary-email");
    const driverVehicleCodeSummary = document.getElementById("driver-vehicle-code");
    const driverVehiclePlateSummary = document.getElementById("driver-vehicle-plate-summary");
    const driverVehicleCapacitySummary = document.getElementById("driver-vehicle-capacity-summary");
    const driverVehicleStatusSummary = document.getElementById("driver-vehicle-status-summary");

    const driverForm = document.getElementById("add-driver-form");
    const driverMessage = document.getElementById("driver-message");
    const driverAdminCheckbox = document.getElementById("driver-admin");
    const vehicleCodeSelect = document.getElementById("vehicle-code");
    const driverRoute1 = document.getElementById("driver-route-1");
    const driverRoute2 = document.getElementById("driver-route-2");
    const previewPlate = document.getElementById("driver-vehicle-plate");
    const previewCapacity = document.getElementById("driver-vehicle-capacity");
    const previewStatus = document.getElementById("driver-vehicle-status");

    const vehicleForm = document.getElementById("add-vehicle-form");
    const vehicleMessage = document.getElementById("vehicle-message");

    const editDriverForm = document.getElementById("edit-driver-form");
    const editDriverSelect = document.getElementById("edit-driver-select");
    const editDriverName = document.getElementById("edit-driver-name");
    const editDriverEmail = document.getElementById("edit-driver-email");
    const editDriverVehicle = document.getElementById("edit-driver-vehicle");
    const editDriverRoute1 = document.getElementById("edit-driver-route-1");
    const editDriverRoute2 = document.getElementById("edit-driver-route-2");
    const editVehiclePlate = document.getElementById("edit-driver-vehicle-plate");
    const editVehicleCapacity = document.getElementById("edit-driver-vehicle-capacity");
    const editVehicleStatus = document.getElementById("edit-driver-vehicle-status");
    const editDriverMessage = document.getElementById("edit-driver-message");
    const deleteDriverButton = document.getElementById("delete-driver-button");

    const editVehicleForm = document.getElementById("edit-vehicle-form");
    const editVehicleSelect = document.getElementById("edit-vehicle-select");
    const editVehicleCode = document.getElementById("edit-vehicle-code");
    const editVehiclePlateInput = document.getElementById("edit-vehicle-plate");
    const editVehicleCapacityInput = document.getElementById("edit-vehicle-capacity");
    const editVehicleStatusSelect = document.getElementById("edit-vehicle-status");
    const editVehicleMessage = document.getElementById("edit-vehicle-message");
    const vehiclesTableBody = document.getElementById("vehicles-table-body");
    const toast = window.AppToast;

    let latestData = {
        routes: [],
        vehicles: [],
        drivers: []
    };
    let vehicleRowTemplate = "";

    const setMessage = (element, text, type) => {
        if (!text) return;
        if (!toast) {
            if (element) {
                element.textContent = text;
                element.className = "save-message" + (type ? " " + type : "");
            }
            return;
        }
        const kind = type || "info";
        if (kind === "success") toast.success(text);
        else if (kind === "error") toast.error(text);
        else if (kind === "warning") toast.warning(text);
        else toast.info(text);
    };

    const normalizeState = value => String(value || "").trim().toLowerCase();
    const isMaintenance = value => normalizeState(value) === "mantenimiento";
    const escapeHtml = value => String(value === null || value === undefined || value === "" ? "-" : value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");

    const loadUiTemplates = () => {
        return fetch("../includes/components/perfil/vehicle-row.html")
            .then(res => {
                if (!res.ok) throw new Error("No se pudo cargar plantilla de vehiculos");
                return res.text();
            })
            .then(template => {
                vehicleRowTemplate = String(template || "").trim();
            })
            .catch(() => {
                vehicleRowTemplate = [
                    "<tr>",
                    '<td data-label="Codigo interno">{{codigo_interno}}</td>',
                    '<td data-label="Placa">{{placa}}</td>',
                    '<td data-label="Capacidad">{{capacidad}}</td>',
                    '<td data-label="Estado">{{estado}}</td>',
                    '<td data-label="Accion"><button type="button" class="action-link action-button edit-vehicle-go" data-vehicle-id="{{id}}">Editar</button></td>',
                    "</tr>"
                ].join("");
            });
    };

    const setVehiclePreview = (vehicle, targets) => {
        const plateEl = targets.plate;
        const capacityEl = targets.capacity;
        const statusEl = targets.status;
        if (!plateEl || !capacityEl || !statusEl) return;

        if (!vehicle) {
            plateEl.textContent = "Sin seleccionar";
            capacityEl.textContent = "Sin seleccionar";
            statusEl.textContent = "Sin seleccionar";
            return;
        }

        plateEl.textContent = vehicle.placa || "Sin placa";
        capacityEl.textContent = String(vehicle.capacidad || "Sin capacidad");
        statusEl.textContent = vehicle.estado || "Sin estado";
    };

    const findVehicleByCode = code => {
        const targetCode = String(code || "").trim().toUpperCase();
        if (!targetCode) return null;
        return latestData.vehicles.find(vehicle =>
            String(vehicle.codigo_interno || "").trim().toUpperCase() === targetCode
        ) || null;
    };

    const findVehicleById = id => {
        const targetId = Number(id || 0);
        if (!targetId) return null;
        return latestData.vehicles.find(vehicle => Number(vehicle.id || 0) === targetId) || null;
    };

    const populateRouteSelect = (select, routes, placeholder, selectedId) => {
        if (!select) return;
        const currentValue = selectedId || select.value || "";
        const options = ['<option value="">' + placeholder + "</option>"];

        routes.forEach(route => {
            const routeId = String(route.id || "").trim();
            const routeName = String(route.nombre || routeId).trim();
            if (!routeId) return;
            const selected = routeId === String(currentValue) ? " selected" : "";
            options.push('<option value="' + routeId + '"' + selected + ">" + routeName + "</option>");
        });

        select.innerHTML = options.join("");
    };

    const populateVehicleSelectByCode = selectedCode => {
        if (!vehicleCodeSelect) return;
        const currentCode = String(selectedCode || vehicleCodeSelect.value || "").trim().toUpperCase();
        const options = ['<option value="">Selecciona vehiculo disponible</option>'];

        latestData.vehicles.forEach(vehicle => {
            const vehicleCode = String(vehicle.codigo_interno || "").trim();
            const assignedDriverId = Number(vehicle.assigned_driver_id || 0);
            if (!vehicleCode) return;
            if (isMaintenance(vehicle.estado)) return;
            if (assignedDriverId > 0 && vehicleCode.toUpperCase() !== currentCode) return;

            const selected = vehicleCode.toUpperCase() === currentCode ? " selected" : "";
            options.push('<option value="' + vehicleCode + '"' + selected + ">" + vehicleCode + "</option>");
        });

        vehicleCodeSelect.innerHTML = options.join("");
    };

    const populateDriversSelect = selectedId => {
        if (!editDriverSelect) return;
        const currentId = String(selectedId || editDriverSelect.value || "");
        const options = ['<option value="">Selecciona chofer</option>'];
        latestData.drivers.forEach(driver => {
            const driverId = String(driver.id || "");
            if (!driverId) return;
            const fullName = [driver.nombre, driver.apellidos].filter(Boolean).join(" ").trim() || ("Chofer #" + driverId);
            const selected = driverId === currentId ? " selected" : "";
            options.push('<option value="' + driverId + '"' + selected + ">" + fullName + "</option>");
        });
        editDriverSelect.innerHTML = options.join("");
    };

    const populateVehicleSelectById = (select, selectedVehicleId, currentDriverId) => {
        if (!select) return;
        const selectedId = Number(selectedVehicleId || 0);
        const driverId = Number(currentDriverId || 0);
        const options = ['<option value="">Sin vehiculo asignado</option>'];

        latestData.vehicles.forEach(vehicle => {
            const vehicleId = Number(vehicle.id || 0);
            const assignedDriverId = Number(vehicle.assigned_driver_id || 0);
            if (!vehicleId) return;
            if (isMaintenance(vehicle.estado)) return;

            if (assignedDriverId > 0 && assignedDriverId !== driverId && vehicleId !== selectedId) {
                return;
            }

            const label = [vehicle.codigo_interno, vehicle.placa].filter(Boolean).join(" - ");
            const selected = vehicleId === selectedId ? " selected" : "";
            options.push('<option value="' + vehicleId + '"' + selected + ">" + label + "</option>");
        });

        select.innerHTML = options.join("");
    };

    const getDriverById = driverId => {
        const target = Number(driverId || 0);
        if (!target) return null;
        return latestData.drivers.find(driver => Number(driver.id || 0) === target) || null;
    };

    const populateEditVehicleSelect = selectedId => {
        if (!editVehicleSelect) return;
        const currentId = String(selectedId || editVehicleSelect.value || "");
        const options = ['<option value="">Selecciona vehiculo</option>'];
        latestData.vehicles.forEach(vehicle => {
            const vehicleId = String(vehicle.id || "");
            if (!vehicleId) return;
            const label = [vehicle.codigo_interno, vehicle.placa].filter(Boolean).join(" - ");
            const selected = vehicleId === currentId ? " selected" : "";
            options.push('<option value="' + vehicleId + '"' + selected + ">" + label + "</option>");
        });
        editVehicleSelect.innerHTML = options.join("");
    };

    const renderVehiclesList = () => {
        if (!vehiclesTableBody) return;
        if (!Array.isArray(latestData.vehicles) || latestData.vehicles.length === 0) {
            vehiclesTableBody.innerHTML = '<tr class="table-placeholder"><td colspan="5">No hay vehiculos registrados</td></tr>';
            return;
        }

        vehiclesTableBody.innerHTML = latestData.vehicles.map(vehicle => {
            const status = isMaintenance(vehicle.estado) ? "Mantenimiento" : "Activo";
            return vehicleRowTemplate
                .replace("{{codigo_interno}}", escapeHtml(vehicle.codigo_interno))
                .replace("{{placa}}", escapeHtml(vehicle.placa))
                .replace("{{capacidad}}", escapeHtml(vehicle.capacidad))
                .replace("{{estado}}", escapeHtml(status))
                .replace("{{id}}", escapeHtml(vehicle.id));
        }).join("");

        vehiclesTableBody.querySelectorAll(".edit-vehicle-go").forEach(button => {
            button.addEventListener("click", () => {
                const vehicleId = String(button.dataset.vehicleId || "");
                activatePanel("editar-vehiculo-panel");
                if (editVehicleSelect) {
                    editVehicleSelect.value = vehicleId;
                }
                loadEditVehicleData();
            });
        });
    };

    const loadEditVehicleData = () => {
        if (!editVehicleSelect) return null;
        const vehicle = findVehicleById(editVehicleSelect.value);
        if (!vehicle) {
            if (editVehicleCode) editVehicleCode.value = "";
            if (editVehiclePlateInput) editVehiclePlateInput.value = "";
            if (editVehicleCapacityInput) editVehicleCapacityInput.value = "";
            if (editVehicleStatusSelect) editVehicleStatusSelect.value = "activo";
            return null;
        }

        if (editVehicleCode) editVehicleCode.value = vehicle.codigo_interno || "";
        if (editVehiclePlateInput) editVehiclePlateInput.value = vehicle.placa || "";
        if (editVehicleCapacityInput) editVehicleCapacityInput.value = String(vehicle.capacidad || "");
        if (editVehicleStatusSelect) editVehicleStatusSelect.value = isMaintenance(vehicle.estado) ? "mantenimiento" : "activo";
        return vehicle;
    };

    const activatePanel = targetId => {
        sidebarLinks.forEach(link => {
            link.classList.toggle("active", link.dataset.target === targetId);
        });

        panels.forEach(panel => {
            panel.classList.toggle("active", panel.id === targetId);
        });
    };

    const validateDifferentRoutes = (first, second) => {
        if (!first || !second) return true;
        if (!first.value || !second.value) return true;
        return first.value !== second.value;
    };

    const loadVehicleByCode = () => {
        if (!vehicleCodeSelect) return Promise.resolve(null);
        const code = (vehicleCodeSelect.value || "").trim();
        if (!code) {
            setVehiclePreview(null, { plate: previewPlate, capacity: previewCapacity, status: previewStatus });
            return Promise.resolve(null);
        }

        const vehicle = findVehicleByCode(code);
        if (!vehicle) {
            setVehiclePreview(null, { plate: previewPlate, capacity: previewCapacity, status: previewStatus });
            return Promise.reject(new Error("Vehiculo no encontrado"));
        }
        if (isMaintenance(vehicle.estado)) {
            setVehiclePreview(null, { plate: previewPlate, capacity: previewCapacity, status: previewStatus });
            return Promise.reject(new Error("No puedes asignar un vehiculo en mantenimiento"));
        }

        setVehiclePreview(vehicle, { plate: previewPlate, capacity: previewCapacity, status: previewStatus });
        return Promise.resolve(vehicle);
    };

    const loadEditDriverData = () => {
        if (!editDriverSelect) return;
        const driver = getDriverById(editDriverSelect.value);
        if (!driver) {
            if (editDriverName) editDriverName.value = "";
            if (editDriverEmail) editDriverEmail.value = "";
            populateVehicleSelectById(editDriverVehicle, "", "");
            populateRouteSelect(editDriverRoute1, latestData.routes, "Selecciona ruta", "");
            populateRouteSelect(editDriverRoute2, latestData.routes, "Selecciona ruta", "");
            setVehiclePreview(null, { plate: editVehiclePlate, capacity: editVehicleCapacity, status: editVehicleStatus });
            return;
        }

        const fullName = [driver.nombre, driver.apellidos].filter(Boolean).join(" ").trim();
        if (editDriverName) editDriverName.value = fullName;
        if (editDriverEmail) editDriverEmail.value = driver.email || "";

        const currentVehicleId = Number(driver.vehiculo_id || 0);
        populateVehicleSelectById(editDriverVehicle, currentVehicleId, driver.id);

        const routes = Array.isArray(driver.routes) ? driver.routes : [];
        const route1 = routes[0] ? String(routes[0].id) : "";
        const route2 = routes[1] ? String(routes[1].id) : "";

        populateRouteSelect(editDriverRoute1, latestData.routes, "Selecciona ruta", route1);
        populateRouteSelect(editDriverRoute2, latestData.routes, "Selecciona ruta", route2);

        const vehicle = findVehicleById(currentVehicleId);
        setVehiclePreview(vehicle, { plate: editVehiclePlate, capacity: editVehicleCapacity, status: editVehicleStatus });
    };

    const refreshProfileData = () => {
        return fetch("../includes/functions/get_profile_data.php")
            .then(res => res.json())
            .then(result => {
                if (!result || !result.success) {
                    throw new Error(result && result.message ? result.message : "No se pudo cargar perfil");
                }

                latestData.routes = Array.isArray(result.routes) ? result.routes : [];
                latestData.vehicles = Array.isArray(result.vehicles) ? result.vehicles : [];
                latestData.drivers = Array.isArray(result.drivers) ? result.drivers : [];

                const profile = result.profile || {};
                const name = [profile.nombre, profile.apellidos].filter(Boolean).join(" ").trim();
                summaryDriverName.textContent = name || "Cuenta sin nombre";
                if (summaryEmail) {
                    summaryEmail.textContent = profile.email || "Sin correo";
                }
                const isAdmin = String(profile.rol || "").toLowerCase() === "admin";

                adminOnlyLinks.forEach(link => { link.style.display = isAdmin ? "" : "none"; });
                adminOnlyPanels.forEach(panel => { panel.style.display = isAdmin ? "" : "none"; });
                driverOnlyLinks.forEach(link => { link.style.display = isAdmin ? "none" : ""; });
                driverOnlyPanels.forEach(panel => { panel.style.display = isAdmin ? "none" : ""; });
                if (!isAdmin) {
                    sidebarLinks.forEach(link => {
                        if (link.dataset.target !== "perfil-panel" && link.dataset.target !== "perfil-vehiculo-panel") {
                            link.style.display = "none";
                        }
                    });
                    panels.forEach(panel => {
                        if (panel.id !== "perfil-panel" && panel.id !== "perfil-vehiculo-panel") {
                            panel.style.display = "none";
                        }
                    });
                } else {
                    sidebarLinks.forEach(link => {
                        if (link.dataset.adminOnly !== "1" && link.dataset.driverOnly !== "1") {
                            link.style.display = "";
                        }
                    });
                    panels.forEach(panel => {
                        if (panel.dataset.adminOnly !== "1" && panel.dataset.driverOnly !== "1") {
                            panel.style.display = "";
                        }
                    });
                }

                const activeLink = document.querySelector(".profile-nav-link.active");
                if (!isAdmin && activeLink && (activeLink.dataset.adminOnly === "1" || activeLink.style.display === "none")) {
                    activatePanel("perfil-panel");
                }
                if (isAdmin && activeLink && activeLink.style.display === "none") {
                    activatePanel("perfil-panel");
                }

                if (profile.vehiculo_id && profile.codigo_interno) {
                    summaryVehicle.textContent = profile.codigo_interno + " - " + (profile.placa || "Sin placa");
                } else {
                    summaryVehicle.textContent = "No tiene vehiculo asignado";
                }
                if (summaryVehicleCard) {
                    summaryVehicleCard.style.display = isAdmin ? "" : "none";
                }

                if (!isAdmin) {
                    if (driverVehicleCodeSummary) {
                        driverVehicleCodeSummary.textContent = profile.codigo_interno || "No tiene vehiculo asignado";
                    }
                    if (driverVehiclePlateSummary) {
                        driverVehiclePlateSummary.textContent = profile.placa || "No tiene vehiculo asignado";
                    }
                    if (driverVehicleCapacitySummary) {
                        driverVehicleCapacitySummary.textContent = profile.capacidad ? String(profile.capacidad) : "No tiene vehiculo asignado";
                    }
                    if (driverVehicleStatusSummary) {
                        driverVehicleStatusSummary.textContent = profile.estado || "No tiene vehiculo asignado";
                    }
                }

                const assignedRoutes = Array.isArray(result.assigned_routes) ? result.assigned_routes : [];
                summaryRoutes.textContent = assignedRoutes.length === 0
                    ? "No tiene rutas asignadas"
                    : assignedRoutes.map(route => route.nombre).join(", ");

                populateRouteSelect(driverRoute1, latestData.routes, "Selecciona ruta", driverRoute1 ? driverRoute1.value : "");
                populateRouteSelect(driverRoute2, latestData.routes, "Selecciona ruta", driverRoute2 ? driverRoute2.value : "");
                populateVehicleSelectByCode(vehicleCodeSelect ? vehicleCodeSelect.value : "");

                populateDriversSelect(editDriverSelect ? editDriverSelect.value : "");
                loadEditDriverData();
                populateEditVehicleSelect(editVehicleSelect ? editVehicleSelect.value : "");
                loadEditVehicleData();
                renderVehiclesList();

                // no toast on every refresh to avoid noise
            })
            .catch(error => {
                setMessage(profileMessage, error.message || "No se pudo cargar informacion de perfil.", "error");
            });
    };

    const updateDriverMode = () => {
        if (!driverForm || !driverAdminCheckbox || !vehicleCodeSelect || !driverRoute1 || !driverRoute2) return;

        const isAdmin = !!driverAdminCheckbox.checked;
        driverForm.classList.toggle("is-admin", isAdmin);
        vehicleCodeSelect.required = !isAdmin;
        driverRoute1.required = !isAdmin;

        if (isAdmin) {
            vehicleCodeSelect.value = "";
            driverRoute1.value = "";
            driverRoute2.value = "";
            setVehiclePreview(null, { plate: previewPlate, capacity: previewCapacity, status: previewStatus });
        }
    };

    sidebarLinks.forEach(link => {
        link.addEventListener("click", () => {
            const target = link.dataset.target;
            if (target) activatePanel(target);
        });
    });

    if (vehicleCodeSelect) {
        vehicleCodeSelect.addEventListener("change", () => {
            loadVehicleByCode().catch(error => {
                setMessage(driverMessage, error.message || "No se encontro vehiculo.", "error");
            });
        });
    }

    if (driverAdminCheckbox) {
        driverAdminCheckbox.addEventListener("change", updateDriverMode);
    }

    if (driverForm) {
        driverForm.addEventListener("submit", event => {
            event.preventDefault();

            if (!validateDifferentRoutes(driverRoute1, driverRoute2)) {
                setMessage(driverMessage, "No puedes seleccionar la misma ruta dos veces.", "error");
                return;
            }
            if (!driverForm.reportValidity()) return;

            loadVehicleByCode()
                .catch(() => null)
                .then(vehicle => {
                    if (!driverAdminCheckbox.checked && !vehicle) {
                        throw new Error("Debes seleccionar un vehiculo valido.");
                    }

                    const formData = new FormData(driverForm);
                    formData.set("is_admin", driverAdminCheckbox.checked ? "1" : "0");

                    return fetch("../includes/functions/create_driver.php", {
                        method: "POST",
                        body: formData
                    });
                })
                .then(res => res.json())
                .then(result => {
                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : "No se pudo crear la cuenta");
                    }
                    setMessage(driverMessage, "Cuenta creada correctamente.", "success");
                    driverForm.reset();
                    updateDriverMode();
                    setVehiclePreview(null, { plate: previewPlate, capacity: previewCapacity, status: previewStatus });
                    return refreshProfileData();
                })
                .catch(error => {
                    setMessage(driverMessage, error.message || "Error al guardar chofer.", "error");
                });
        });
    }

    if (vehicleForm) {
        vehicleForm.addEventListener("submit", event => {
            event.preventDefault();
            if (!vehicleForm.reportValidity()) return;

            const formData = new FormData(vehicleForm);

            fetch("../includes/functions/create_vehicle.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(result => {
                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : "No se pudo guardar vehiculo");
                    }
                    setMessage(vehicleMessage, "Vehiculo guardado correctamente.", "success");
                    vehicleForm.reset();
                    return refreshProfileData();
                })
                .catch(error => {
                    setMessage(vehicleMessage, error.message || "Error al guardar vehiculo.", "error");
                });
        });
    }

    if (editDriverSelect) {
        editDriverSelect.addEventListener("change", () => {
            loadEditDriverData();
        });
    }

    if (editDriverVehicle) {
        editDriverVehicle.addEventListener("change", () => {
            const vehicle = findVehicleById(editDriverVehicle.value);
            setVehiclePreview(vehicle, { plate: editVehiclePlate, capacity: editVehicleCapacity, status: editVehicleStatus });
        });
    }

    if (editDriverForm) {
        editDriverForm.addEventListener("submit", event => {
            event.preventDefault();

            const driverId = editDriverSelect ? editDriverSelect.value : "";
            if (!driverId) {
                setMessage(editDriverMessage, "Selecciona un chofer para editar.", "error");
                return;
            }

            if (!validateDifferentRoutes(editDriverRoute1, editDriverRoute2)) {
                setMessage(editDriverMessage, "No puedes seleccionar la misma ruta dos veces.", "error");
                return;
            }

            const formData = new FormData();
            formData.set("driver_id", driverId);
            formData.set("vehiculo_id", editDriverVehicle ? String(editDriverVehicle.value || "") : "");
            if (editDriverRoute1) formData.append("ruta_ids[]", String(editDriverRoute1.value || ""));
            if (editDriverRoute2) formData.append("ruta_ids[]", String(editDriverRoute2.value || ""));

            fetch("../includes/functions/update_driver_account.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(result => {
                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : "No se pudo actualizar");
                    }
                    setMessage(editDriverMessage, "Chofer actualizado correctamente.", "success");
                    return refreshProfileData();
                })
                .catch(error => {
                    setMessage(editDriverMessage, error.message || "Error actualizando chofer.", "error");
                });
        });
    }

    if (deleteDriverButton) {
        deleteDriverButton.addEventListener("click", () => {
            const driverId = editDriverSelect ? editDriverSelect.value : "";
            if (!driverId) {
                setMessage(editDriverMessage, "Selecciona un chofer para borrar.", "error");
                return;
            }

            const doDelete = confirmed => {
                if (!confirmed) return;

                const formData = new FormData();
                formData.set("driver_id", driverId);

                fetch("../includes/functions/delete_driver_account.php", {
                    method: "POST",
                    body: formData
                })
                    .then(res => res.json())
                    .then(result => {
                        if (!result || !result.success) {
                            throw new Error(result && result.message ? result.message : "No se pudo borrar la cuenta");
                        }

                        if (editDriverSelect) editDriverSelect.value = "";
                        setMessage(editDriverMessage, "Cuenta eliminada correctamente.", "success");
                        return refreshProfileData();
                    })
                    .catch(error => {
                        setMessage(editDriverMessage, error.message || "Error borrando cuenta.", "error");
                    });
            };

            if (toast && typeof toast.confirm === "function") {
                toast.confirm("Esta accion eliminara la cuenta del chofer seleccionado. Deseas continuar?", {
                    type: "warning",
                    confirmText: "Borrar",
                    cancelText: "Cancelar"
                }).then(doDelete);
            } else {
                doDelete(true);
            }
        });
    }

    if (editVehicleSelect) {
        editVehicleSelect.addEventListener("change", () => {
            loadEditVehicleData();
        });
    }

    if (editVehicleForm) {
        editVehicleForm.addEventListener("submit", event => {
            event.preventDefault();
            if (!editVehicleForm.reportValidity()) return;

            const vehicleId = editVehicleSelect ? String(editVehicleSelect.value || "") : "";
            if (!vehicleId) {
                setMessage(editVehicleMessage, "Selecciona un vehiculo para editar.", "error");
                return;
            }

            const currentVehicle = findVehicleById(vehicleId);
            const nextState = editVehicleStatusSelect ? String(editVehicleStatusSelect.value || "activo") : "activo";
            const hasAssignedDriver = currentVehicle && Number(currentVehicle.assigned_driver_id || 0) > 0;

            if (nextState === "mantenimiento" && hasAssignedDriver && !isMaintenance(currentVehicle.estado)) {
                const driverName = String(currentVehicle.assigned_driver_name || "").trim() || "el chofer asignado";
                setMessage(editVehicleMessage, "Este vehiculo pasara a mantenimiento y se desasignara de " + driverName + ".", "warning");
            }

            const formData = new FormData();
            formData.set("vehicle_id", vehicleId);
            formData.set("placa", editVehiclePlateInput ? String(editVehiclePlateInput.value || "") : "");
            formData.set("capacidad", editVehicleCapacityInput ? String(editVehicleCapacityInput.value || "") : "");
            formData.set("estado", nextState);

            fetch("../includes/functions/update_vehicle.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(result => {
                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : "No se pudo actualizar el vehiculo");
                    }

                    setMessage(editVehicleMessage, "Vehiculo actualizado correctamente.", "success");

                    if (result.unassigned_driver) {
                        const name = result.unassigned_driver_name ? (" de " + result.unassigned_driver_name) : "";
                        setMessage(editVehicleMessage, "Vehiculo desasignado" + name + " por mantenimiento.", "warning");
                    }

                    return refreshProfileData();
                })
                .catch(error => {
                    setMessage(editVehicleMessage, error.message || "Error actualizando vehiculo.", "error");
                });
        });
    }

    activatePanel("perfil-panel");
    updateDriverMode();
    loadUiTemplates().then(() => refreshProfileData());
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPerfilPage);
} else {
    initPerfilPage();
}
