document.addEventListener('DOMContentLoaded', () => {
    loadHeader();
    loadFooter();
    window.addEventListener('resize', () => {
        loadHeader();
        loadFooter();
    });
});

let currentView = null;

function getBasePath(currentPath, section) {
    if (currentPath.includes('/pages/auth/')) {
        return section === 'components' ? '../../includes/components/' : '../../includes/auth/';
    }
    if (currentPath.includes('/pages/')) {
        return section === 'components' ? '../includes/components/' : '../includes/auth/';
    }
    return section === 'components' ? '/fulopp/includes/components/' : '/fulopp/includes/auth/';
}

function loadHeader() {
    const width = window.innerWidth;
    const currentPath = window.location.pathname;
    const basePath = getBasePath(currentPath, 'components');
    const newView = width <= 768 ? 'mobile' : 'desktop';

    if (currentView === newView) return;
    currentView = newView;

    const file = newView === 'mobile' ? 'header-mobile.html' : 'header-desktop.html';

    fetch(basePath + file)
        .then(res => res.text())
        .then(html => {
            const headerContainer = document.getElementById('header-container');
            if (!headerContainer) return;
            headerContainer.innerHTML = html;
            setupAuthNavigation();
        })
        .catch(err => console.error('Error cargando header:', err));
}

function setupAuthNavigation() {
    const currentPath = window.location.pathname;
    const authBasePath = getBasePath(currentPath, 'auth');

    fetch(authBasePath + 'session_status.php', { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            const loggedIn = !!(data && data.logged_in);

            document.querySelectorAll('[data-auth="private"]').forEach(el => {
                el.style.display = loggedIn ? '' : 'none';
            });

            document.querySelectorAll('[data-auth="guest"]').forEach(el => {
                el.style.display = loggedIn ? 'none' : '';
            });

            const protectedPaths = [
                '/fulopp/pages/rutas.html',
                '/fulopp/pages/informe.html',
                '/fulopp/pages/perfil.html',
                '/fulopp/pages/agregar.html'
            ];
            const isProtected = protectedPaths.some(path => currentPath.endsWith(path));

            if (!loggedIn && isProtected) {
                const next = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = '/fulopp/pages/auth/login.html?next=' + next;
                return;
            }

            if (loggedIn) {
                const logoutLink = document.getElementById('logout-link');
                if (logoutLink) {
                    logoutLink.addEventListener('click', event => {
                        event.preventDefault();
                        fetch(authBasePath + 'logout.php', {
                            method: 'POST',
                            credentials: 'same-origin'
                        })
                            .then(() => {
                                window.location.href = '/fulopp/pages/auth/login.html';
                            })
                            .catch(err => {
                                console.error('Error cerrando sesion:', err);
                            });
                    });
                }
            }
        })
        .catch(err => console.error('Error verificando sesion:', err));
}

function loadFooter() {
    const currentPath = window.location.pathname;
    const basePath = getBasePath(currentPath, 'components');

    fetch(basePath + 'footer.html')
        .then(res => res.text())
        .then(html => {
            const footerContainer = document.getElementById('footer-container');
            if (!footerContainer) return;
            footerContainer.innerHTML = html;
        })
        .catch(err => console.error('Error cargando footer:', err));
}
