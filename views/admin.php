<?php
session_start(); // Requerido para acceder a las variables de sesión en un script cargado por fetch

// 1. Comprobar si el usuario tiene permisos de superusuario
if (!isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    // Si no es superusuario, mostrar un mensaje de acceso denegado
    echo '<div class="container mt-4"><div class="alert alert-danger"><strong>Acceso Denegado:</strong> No tienes permisos para acceder a esta sección.</div></div>';
    // Detener la ejecución para no mostrar el resto del contenido de administración
    exit;
}

// 2. Si es superusuario, mostrar el contenido de la página de administración.
// El token CSRF se obtiene de la sesión, que fue iniciada en index.php
$csrf_token = $_SESSION['csrf_token'];
?>

<div class="container-fluid">
    <h1 class="mt-4 mb-4">Panel de Administración</h1>

    <!-- Fila para selectores y acciones -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Gestión de Empresas</h5>
                    <p class="card-text">Selecciona una empresa para ver o editar sus usuarios y KPIs.</p>
                    <form id="company-selection-form">
                        <div class="mb-3">
                            <label for="empresa-select" class="form-label">Empresa:</label>
                            <select id="empresa-select" class="form-select">
                                <!-- Las opciones se cargarán dinámicamente con JS -->
                                <option selected disabled>Cargando empresas...</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Acciones Rápidas</h5>
                    <p>Crea nuevas empresas, usuarios o KPIs.</p>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#create-company-modal"><i class="fas fa-building"></i> Crear Nueva Empresa</button>
                            <button id="btn-add-user" class="btn btn-success w-100 mb-2" disabled><i class="fas fa-user-plus"></i> Añadir Nuevo Usuario</button>
                            <button id="btn-add-agent" class="btn btn-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#create-agent-modal"><i class="fas fa-headset"></i> Crear Agente</button>
                            <button id="btn-add-closer" class="btn btn-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#create-closer-modal"><i class="fas fa-handshake"></i> Crear Closer</button>
                            <button id="btn-add-location" class="btn btn-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#create-location-modal"><i class="fas fa-map-marker-alt"></i> Crear Sede</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila para mostrar tablas de usuarios, agentes, closers y sedes -->
    <div class="row mt-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="adminEntitiesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-pane" type="button" role="tab" aria-controls="users-pane" aria-selected="true">Usuarios</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="agents-tab" data-bs-toggle="tab" data-bs-target="#agents-pane" type="button" role="tab" aria-controls="agents-pane" aria-selected="false">Agentes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="closers-tab" data-bs-toggle="tab" data-bs-target="#closers-pane" type="button" role="tab" aria-controls="closers-pane" aria-selected="false">Closers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations-pane" type="button" role="tab" aria-controls="locations-pane" aria-selected="false">Sedes</button>
                </li>
            </ul>
            <div class="tab-content" id="adminEntitiesTabContent">
                <!-- Users Pane -->
                <div class="tab-pane fade show active" id="users-pane" role="tabpanel" aria-labelledby="users-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Usuarios de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div id="users-table-container" class="table-responsive">
                                <p class="text-muted">Selecciona una empresa para ver sus usuarios.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Agents Pane -->
                <div class="tab-pane fade" id="agents-pane" role="tabpanel" aria-labelledby="agents-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Agentes de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div id="agents-table-container" class="table-responsive">
                                <p class="text-muted">Selecciona una empresa para ver sus agentes.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Closers Pane -->
                <div class="tab-pane fade" id="closers-pane" role="tabpanel" aria-labelledby="closers-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Closers de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div id="closers-table-container" class="table-responsive">
                                <p class="text-muted">Selecciona una empresa para ver sus closers.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Locations Pane -->
                <div class="tab-pane fade" id="locations-pane" role="tabpanel" aria-labelledby="locations-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Sedes de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div id="locations-table-container" class="table-responsive">
                                <p class="text-muted">Selecciona una empresa para ver sus sedes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
<?php include 'modals/admin_modals.php'; ?>

