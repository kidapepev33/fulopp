document.addEventListener("DOMContentLoaded", () => {
    const sidebarLinks = Array.from(document.querySelectorAll(".profile-nav-link"));
    const panels = Array.from(document.querySelectorAll("[data-panel]"));

    const profileMessage = document.getElementById("profile-message");
    const summaryDriverName = document.getElementById("summary-driver-name");
    const summaryVehicle = document.getElementById("summary-vehicle");
    const summaryRoutes = document.getElementById("summary-routes");

    const driverForm = document.getElementById("add-driver-form");
    const driverMessage = document.getElementById("driver-message");
    const driverAdminCheckbox = document.getElementById("driver-admin");
    const vehicleCodeInput = document.getElementById("vehicle-code");
    const driverRoute1 = document.getElementById("driver-route-1");
    const driverRoute2 = document.getElementById("driver-route-2");
    const previewPlate = document.getElementById("driver-vehicle-plate");
    const previewCapacity = document.getElementById("driver-vehicle-capacity");
    const previewStatus = document.getElementById("driver-vehicle-status");

    const vehicleForm = document.getElementById("add-vehicle-form");
    const vehicleMessage = document.getElementById("vehicle-message");
    const vehicleRoute1 = document.getElementById("vehicle-route-1");
    const vehicleRoute2 = document.getElementById("vehicle-route-2");

    let latestData = {
        routes: [],
        vehicles: []
    };

    const setMessage = (element, text, type) => {
        if (!element) {
            return;
        }
        element.textContent = text || "";
        element.className = "save-message" + (type ? " " + type : "");
    };

    const setVehiclePreview = vehicle => {
        if (!previewPlate || !previewCapacity || !previewStatus) {
            return;
        }

        if (!vehicle) {
            previewPlate.textContent = "Sin seleccionar";
            previewCapacity.textContent = "Sin seleccionar";
            previewStatus.textContent = "Sin seleccionar";
            return;
        }

        previewPlate.textContent = vehicle.placa || "Sin placa";
        previewCapacity.textContent = String(vehicle.capacidad || "Sin capacidad");
        previewStatus.textContent = vehicle.estado || "Sin estado";
    };

    const populateRouteSelect = (select, routes, placeholder, selectedId) => {
        if (!select) {
            return;
        }
        const currentValue = selectedId || select.value || "";
        const options = ['<option value="">' + placeholder + "</option>"];

        routes.forEach(route => {
            const routeId = String(route.id || "").trim();
            const routeName = String(route.nombre || routeId).trim();
            if (routeId === "") {
                return;
            }
            const selected = routeId === String(currentValue) ? " selected" : "";
            options.push('<option value="' + routeId + '"' + selected + ">" + routeName + "</option>");
        });

        select.innerHTML = options.join("");
    };

    const activatePanel = targetId => {
        sidebarLinks.forEach(link => {
            const active = link.dataset.target === targetId;
            link.classList.toggle("active", active);
        });

        panels.forEach(panel => {
            panel.classList.toggle("active", panel.id === targetId);
        });
    };

    const validateDifferentRoutes = (first, second) => {
        if (!first || !second) {
            return true;
        }
        if (!first.value || !second.value) {
            return true;
        }
        return first.value !== second.value;
    };

    const loadVehicleByCode = () => {
        if (!vehicleCodeInput) {
            return Promise.resolve(null);
        }

        const code = (vehicleCodeInput.value || "").trim();
        if (!code) {
            setVehiclePreview(null);
            return Promise.resolve(null);
        }

        return fetch("../includes/functions/get_vehicle_by_code.php?codigo_interno=" + encodeURIComponent(code))
            .then(res => res.json())
            .then(result => {
                if (!result || !result.success || !result.vehicle) {
                    setVehiclePreview(null);
                    throw new Error(result && result.message ? result.message : "Vehiculo no encontrado");
                }
                setVehiclePreview(result.vehicle);
                return result.vehicle;
            });
    };

    const refreshProfileData = () => {
        setMessage(profileMessage, "Cargando informacion de perfil...", "");
        return fetch("../includes/functions/get_profile_data.php")
            .then(res => res.json())
            .then(result => {
                if (!result || !result.success) {
                    throw new Error(result && result.message ? result.message : "No se pudo cargar perfil");
                }

                latestData.routes = Array.isArray(result.routes) ? result.routes : [];
                latestData.vehicles = Array.isArray(result.vehicles) ? result.vehicles : [];

                const profile = result.profile || {};
                const name = [profile.nombre, profile.apellidos].filter(Boolean).join(" ").trim();
                summaryDriverName.textContent = name || "Cuenta sin nombre";

                if (profile.vehiculo_id && profile.codigo_interno) {
                    summaryVehicle.textContent = profile.codigo_interno + " - " + (profile.placa || "Sin placa");
                } else {
                    summaryVehicle.textContent = "No tiene vehiculo asignado";
                }

                const assignedRoutes = Array.isArray(result.assigned_routes) ? result.assigned_routes : [];
                if (assignedRoutes.length === 0) {
                    summaryRoutes.textContent = "No tiene rutas asignadas";
                } else {
                    summaryRoutes.textContent = assignedRoutes.map(route => route.nombre).join(", ");
                }

                populateRouteSelect(driverRoute1, latestData.routes, "Selecciona ruta", driverRoute1.value);
                populateRouteSelect(driverRoute2, latestData.routes, "Selecciona ruta", driverRoute2.value);
                populateRouteSelect(vehicleRoute1, latestData.routes, "Selecciona ruta", vehicleRoute1.value);
                populateRouteSelect(vehicleRoute2, latestData.routes, "Selecciona ruta", vehicleRoute2.value);

                setMessage(profileMessage, "Perfil actualizado.", "success");
            })
            .catch(error => {
                setMessage(profileMessage, error.message || "No se pudo cargar informacion de perfil.", "error");
            });
    };

    const updateDriverMode = () => {
        if (!driverForm || !driverAdminCheckbox || !vehicleCodeInput || !driverRoute1 || !driverRoute2) {
            return;
        }

        const isAdmin = !!driverAdminCheckbox.checked;
        driverForm.classList.toggle("is-admin", isAdmin);

        vehicleCodeInput.required = !isAdmin;
        driverRoute1.required = !isAdmin;

        if (isAdmin) {
            vehicleCodeInput.value = "";
            driverRoute1.value = "";
            driverRoute2.value = "";
            setVehiclePreview(null);
        }
    };

    sidebarLinks.forEach(link => {
        link.addEventListener("click", () => {
            const target = link.dataset.target;
            if (target) {
                activatePanel(target);
            }
        });
    });

    if (vehicleCodeInput) {
        vehicleCodeInput.addEventListener("blur", () => {
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

            if (!driverForm.reportValidity()) {
                return;
            }

            setMessage(driverMessage, "Guardando cuenta de chofer...", "");

            loadVehicleByCode()
                .catch(() => null)
                .then(vehicle => {
                    if (!driverAdminCheckbox.checked && !vehicle) {
                        throw new Error("Debes indicar un codigo interno de vehiculo valido.");
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
                    setVehiclePreview(null);
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

            if (!validateDifferentRoutes(vehicleRoute1, vehicleRoute2)) {
                setMessage(vehicleMessage, "No puedes seleccionar la misma ruta dos veces.", "error");
                return;
            }

            if (!vehicleForm.reportValidity()) {
                return;
            }

            setMessage(vehicleMessage, "Guardando vehiculo...", "");
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

    activatePanel("perfil-panel");
    updateDriverMode();
    refreshProfileData();
});

