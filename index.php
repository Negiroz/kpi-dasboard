<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de KPIs</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jsPDF y plugins -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Solutek</h3>
            <p>Análisis de resultados</p>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" data-view="dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboards</a>
            <a class="nav-link" href="#" data-view="data-management"><i class="fas fa-database me-2"></i> Gestión de Datos</a>
            <a class="nav-link" href="#" data-view="admin"><i class="fas fa-user-shield me-2"></i> Administración</a>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a>
        </nav>
    </div>

    <div class="main-content-wrapper">
        <div class="top-navbar">
            
            
        </div>

        <main class="main-content">
            <div id="dashboard-view">
                <?php include 'views/dashboard.php'; ?>
            </div>
            <div id="data-management-view" style="display: none;">
                <?php include 'views/data_management.php'; ?>
            </div>
            <div id="admin-view" style="display: none;">
                <?php include 'views/admin.php'; ?>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            const views = {
                'dashboard': document.getElementById('dashboard-view'),
                'data-management': document.getElementById('data-management-view'),
                'admin': document.getElementById('admin-view')
            };

            function switchView(viewName) {
                if (!views[viewName]) return;

                navLinks.forEach(l => {
                    l.classList.toggle('active', l.dataset.view === viewName);
                });

                for (const key in views) {
                    views[key].style.display = (key === viewName) ? 'block' : 'none';
                }
            }

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const viewName = this.dataset.view;
                    switchView(viewName);
                    window.location.hash = viewName;
                });
            });

            const urlParams = new URLSearchParams(window.location.search);
            const paramView = urlParams.get('view');
            const hashView = window.location.hash.substring(1);

            const initialView = paramView || hashView || 'dashboard';
            switchView(initialView);

            if (paramView) {
                history.replaceState(null, '', `index.php#${paramView}`);
            }
        });
    </script>

</body>
</html>
