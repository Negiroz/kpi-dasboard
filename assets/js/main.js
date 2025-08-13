document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.sidebar .nav-link[data-view]');
    const views = {
        'dashboard': document.getElementById('dashboard-view'),
        'data-management': document.getElementById('data-management-view'),
        'admin': document.getElementById('admin-view')
    };

    function switchView(viewName) {
        if (!views[viewName]) {
            console.error(`View "${viewName}" not found.`);
            return;
        }

        // Actualizar enlaces de navegación
        navLinks.forEach(link => {
            link.classList.toggle('active', link.dataset.view === viewName);
        });

        // Ocultar todas las vistas y mostrar la seleccionada
        for (const key in views) {
            if (views[key]) {
                views[key].style.display = (key === viewName) ? 'block' : 'none';
            }
        }

        // Carga dinámica para la vista de administración
        if (viewName === 'admin' && !views['admin'].innerHTML.trim()) {
            fetch('views/admin.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    views['admin'].innerHTML = html;
                    console.log('admin.php content loaded.');
                    // Cargar dinámicamente el script de admin.js
                    const script = document.createElement('script');
                    script.src = 'assets/js/admin.js';
                    script.onload = () => {
                        console.log('admin.js loaded.');
                        if (typeof initAdminPage === 'function') {
                            initAdminPage();
                            console.log('initAdminPage() called.');
                        } else {
                            console.error('initAdminPage is not defined after loading admin.js');
                        }
                    };
                    script.onerror = () => {
                        console.error('Error loading admin.js');
                    };
                    views['admin'].appendChild(script);
                })
                .catch(error => {
                    console.error('Error loading admin view:', error);
                    views['admin'].innerHTML = '<p class="text-danger">Error al cargar la vista de administración.</p>';
                });
        }
    }

    // Event listeners para los enlaces de navegación
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const viewName = this.dataset.view;
            switchView(viewName);
            // Actualizar el hash en la URL para mantener el estado
            window.location.hash = viewName;
        });
    });

    // Determinar la vista inicial al cargar la página
    const hashView = window.location.hash.substring(1);
    const initialView = hashView && views[hashView] ? hashView : 'dashboard';
    
    switchView(initialView);
});
