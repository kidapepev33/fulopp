(function () {
    const CONTAINER_ID = "app-toast-container";
    const MAX_TOASTS = 3;

    function getContainer() {
        let container = document.getElementById(CONTAINER_ID);
        if (container) return container;

        container = document.createElement("div");
        container.id = CONTAINER_ID;
        container.className = "toast-container";
        document.body.appendChild(container);
        return container;
    }

    function show(message, type, duration) {
        if (!message) return null;
        const container = getContainer();
        while (container.children.length >= MAX_TOASTS) {
            container.firstElementChild.remove();
        }
        const toast = document.createElement("div");
        toast.className = "toast toast--" + (type || "info");
        toast.textContent = String(message);
        container.appendChild(toast);

        const ttl = typeof duration === "number" ? duration : 3600;
        setTimeout(() => {
            toast.remove();
        }, ttl);
        return toast;
    }

    function confirm(message, options) {
        const config = options || {};
        return new Promise(resolve => {
            const container = getContainer();
            while (container.children.length >= MAX_TOASTS) {
                container.firstElementChild.remove();
            }
            const toast = document.createElement("div");
            toast.className = "toast toast--" + (config.type || "warning");
            toast.textContent = String(message || "Confirmar accion");

            const actions = document.createElement("div");
            actions.className = "toast-actions";

            const cancelBtn = document.createElement("button");
            cancelBtn.type = "button";
            cancelBtn.className = "toast-action-btn";
            cancelBtn.textContent = config.cancelText || "Cancelar";

            const confirmBtn = document.createElement("button");
            confirmBtn.type = "button";
            confirmBtn.className = "toast-action-btn";
            confirmBtn.textContent = config.confirmText || "Confirmar";

            cancelBtn.addEventListener("click", () => {
                toast.remove();
                resolve(false);
            });
            confirmBtn.addEventListener("click", () => {
                toast.remove();
                resolve(true);
            });

            actions.appendChild(cancelBtn);
            actions.appendChild(confirmBtn);
            toast.appendChild(actions);
            container.appendChild(toast);
        });
    }

    window.AppToast = {
        show,
        success: (message, duration) => show(message, "success", duration),
        error: (message, duration) => show(message, "error", duration),
        info: (message, duration) => show(message, "info", duration),
        warning: (message, duration) => show(message, "warning", duration),
        confirm
    };
})();
