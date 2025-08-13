<?php
session_start();

require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Redirigir si no se ha seleccionado una empresa
if (!isset($_SESSION['empresa_id'])) {
    header("Location: selector.php");
    exit;
}

// Generar y almacenar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Determinar el Periodo a Mostrar ---
$SELECTED_YEAR = date('Y');
$SELECTED_MONTH = date('n');

// Si se seleccionó una fecha específica desde el selector
if (isset($_GET['selected_date'])) {
    $selected_date_str = $_GET['selected_date'];
    $date_obj = DateTime::createFromFormat('Y-m-d', $selected_date_str);
    if ($date_obj) {
        $SELECTED_YEAR = (int)$date_obj->format('Y');
        $SELECTED_MONTH = (int)$date_obj->format('n');
    }
} else if (isset($_GET['mes']) && isset($_GET['anio'])) { // Si se seleccionó un mes y año específicos (para compatibilidad)
    $temp_mes = filter_var($_GET['mes'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
    $temp_anio = filter_var($_GET['anio'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);

    if ($temp_mes !== false && $temp_anio !== false) {
        $SELECTED_MONTH = $temp_mes;
        $SELECTED_YEAR = $temp_anio;
    }
}

// Formatear SELECTED_DATE para Flatpickr
$SELECTED_DATE = date('Y-m-d', strtotime("$SELECTED_YEAR-$SELECTED_MONTH-01"));
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
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Solutek</h3>
            <p>Análisis de resultados</p>
            <p class="text-white-50"><i class="fas fa-building me-2"></i><?php echo htmlspecialchars($_SESSION['empresa_nombre']); ?></p>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" data-view="dashboard"><i class="fas fa-tachometer-alt me-2"></i> <span>Dashboards</span></a>
            <a class="nav-link" href="#" data-view="data-management"><i class="fas fa-database me-2"></i> <span>Gestión de Datos</span></a>
            <?php if (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']): ?>
                <a class="nav-link" href="#" data-view="admin"><i class="fas fa-user-shield me-2"></i> <span>Administración</span></a>
            <?php endif; ?>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Cerrar Sesión</span></a>
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
</div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <script>
        window.CSRF_TOKEN = '<?php echo $csrf_token; ?>';
    </script>
    <script src="assets/js/main.js"></script>
