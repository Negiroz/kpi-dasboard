<?php
require_once 'db.php';

// Obtener todos los datos para las tablas
$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$agentes_con_sede = $conn->query("SELECT a.id, a.nombre, s.nombre as sede_nombre FROM agentes a LEFT JOIN sedes s ON a.sede_id = s.id ORDER BY a.nombre")->fetch_all(MYSQLI_ASSOC);
$closers = $conn->query("SELECT * FROM closers ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

?>

<div class="container-fluid">
    <h1 class="h2">Administración de Entidades</h1>

    <ul class="nav nav-tabs" id="adminEntityTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="admin-view-sedes-tab" data-bs-toggle="tab" data-bs-target="#admin-view-sedes-pane" type="button">Sedes</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="admin-view-agentes-tab" data-bs-toggle="tab" data-bs-target="#admin-view-agentes-pane" type="button">Agentes</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="admin-view-closers-tab" data-bs-toggle="tab" data-bs-target="#admin-view-closers-pane" type="button">Closers</button>
        </li>
    </ul>

    <div class="tab-content" id="adminEntityTabContent">
        <!-- Pestaña de Sedes -->
        <div class="tab-pane fade" id="admin-view-sedes-pane" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header">Gestionar Sedes</div>
                <div class="card-body">
                    <form action="actions/admin_actions.php" method="POST" class="mb-4">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="type" value="sede">
                        <input type="hidden" name="tab" value="sedes">
                        <div class="input-group">
                            <input type="text" name="nombre" class="form-control" placeholder="Nuevo nombre de sede" required>
                            <button class="btn btn-primary" type="submit">Crear Sede</button>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead><tr><th>Nombre</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($sedes as $sede): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sede['nombre']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm me-1 edit-sede-btn" data-id="<?php echo $sede['id']; ?>" data-nombre="<?php echo htmlspecialchars($sede['nombre']); ?>" data-bs-toggle="modal" data-bs-target="#editSedeModal">Editar</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $sede['id']; ?>" data-type="sede" data-tab="sedes" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pestaña de Agentes -->
        <div class="tab-pane fade" id="admin-view-agentes-pane" role="tabpanel">
             <div class="card mt-3">
                <div class="card-header">Gestionar Agentes</div>
                <div class="card-body">
                    <form action="actions/admin_actions.php" method="POST" class="mb-4 row g-3">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="type" value="agente">
                        <input type="hidden" name="tab" value="agentes">
                        <div class="col-md-6">
                            <input type="text" name="nombre" class="form-control" placeholder="Nuevo nombre de agente" required>
                        </div>
                        <div class="col-md-4">
                            <select name="sede_id" class="form-select" required>
                                <option value="" disabled selected>Seleccionar Sede</option>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">Crear Agente</button>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead><tr><th>Nombre</th><th>Sede</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php 
                                $agentes_con_sede = $conn->query("SELECT a.id, a.nombre, s.nombre as sede_nombre FROM agentes a LEFT JOIN sedes s ON a.sede_id = s.id ORDER BY a.nombre")->fetch_all(MYSQLI_ASSOC);
                                foreach ($agentes_con_sede as $agente): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agente['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($agente['sede_nombre'] ?? 'N/A'); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm me-1 edit-agente-btn" data-id="<?php echo $agente['id']; ?>" data-nombre="<?php echo htmlspecialchars($agente['nombre']); ?>" data-sede-id="<?php echo $agente['sede_id'] ?? ''; ?>" data-bs-toggle="modal" data-bs-target="#editAgenteModal">Editar</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $agente['id']; ?>" data-type="agente" data-tab="agentes" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pestaña de Closers -->
        <div class="tab-pane fade" id="admin-view-closers-pane" role="tabpanel">
             <div class="card mt-3">
                <div class="card-header">Gestionar Closers</div>
                <div class="card-body">
                    <form action="actions/admin_actions.php" method="POST" class="mb-4">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="type" value="closer">
                        <input type="hidden" name="tab" value="closers">
                        <div class="input-group">
                            <input type="text" name="nombre" class="form-control" placeholder="Nuevo nombre de closer" required>
                            <button class="btn btn-primary" type="submit">Crear Closer</button>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead><tr><th>Nombre</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($closers as $closer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($closer['nombre']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm me-1 edit-closer-btn" data-id="<?php echo $closer['id']; ?>" data-nombre="<?php echo htmlspecialchars($closer['nombre']); ?>" data-bs-toggle="modal" data-bs-target="#editCloserModal">Editar</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $closer['id']; ?>" data-type="closer" data-tab="closers" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
</div>

</div>

</div>

<!-- Modal Editar Closer -->
<div class="modal fade" id="editCloserModal" tabindex="-1" aria-labelledby="editCloserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCloserModalLabel">Editar Closer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/admin_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="type" value="closer">
                    <input type="hidden" name="tab" value="closers">
                    <input type="hidden" name="id" id="edit-closer-id">
                    <div class="mb-3">
                        <label for="edit-closer-nombre" class="form-label">Nombre del Closer</label>
                        <input type="text" class="form-control" id="edit-closer-nombre" name="nombre" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Agente -->
<div class="modal fade" id="editAgenteModal" tabindex="-1" aria-labelledby="editAgenteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAgenteModalLabel">Editar Agente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/admin_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="type" value="agente">
                    <input type="hidden" name="tab" value="agentes">
                    <input type="hidden" name="id" id="edit-agente-id">
                    <div class="mb-3">
                        <label for="edit-agente-nombre" class="form-label">Nombre del Agente</label>
                        <input type="text" class="form-control" id="edit-agente-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-agente-sede" class="form-label">Sede</label>
                        <select name="sede_id" id="edit-agente-sede" class="form-select" required>
                            <option value="" disabled>Seleccionar Sede</option>
                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Sede -->
<div class="modal fade" id="editSedeModal" tabindex="-1" aria-labelledby="editSedeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSedeModalLabel">Editar Sede</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/admin_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="type" value="sede">
                    <input type="hidden" name="tab" value="sedes">
                    <input type="hidden" name="id" id="edit-sede-id">
                    <div class="mb-3">
                        <label for="edit-sede-nombre" class="form-label">Nombre de la Sede</label>
                        <input type="text" class="form-control" id="edit-sede-nombre" name="nombre" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabTriggers = document.querySelectorAll('#adminEntityTab .nav-link');
    const tabPanes = document.querySelectorAll('#adminEntityTabContent .tab-pane');

    function showTab(targetPaneId) {
        // Deactivate all
        tabTriggers.forEach(t => t.classList.remove('active'));
        tabPanes.forEach(p => {
            p.classList.remove('show', 'active');
        });

        // Activate selected
        const trigger = document.querySelector(`button[data-bs-target="${targetPaneId}"]`);
        const pane = document.querySelector(targetPaneId);
        
        if (trigger) {
            trigger.classList.add('active');
        }
        if (pane) {
            pane.classList.add('show', 'active');
        }
    }

    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(event) {
            event.preventDefault();
            const targetPaneId = this.dataset.bsTarget;
            showTab(targetPaneId);
        });
    });

    // On page load, check for a tab parameter in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const targetPaneId = '#admin-view-' + tabParam + '-pane';
        showTab(targetPaneId);
    } else {
        // Default to the first tab if no param
        showTab('#admin-view-sedes-pane');
    }

    // Lógica para el modal de edición de Sedes
    const editSedeModal = document.getElementById('editSedeModal');
    editSedeModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');

        const modalIdInput = editSedeModal.querySelector('#edit-sede-id');
        const modalNombreInput = editSedeModal.querySelector('#edit-sede-nombre');

        modalIdInput.value = id;
        modalNombreInput.value = nombre;
    });

    // Lógica para el modal de edición de Agentes
    const editAgenteModal = document.getElementById('editAgenteModal');
    editAgenteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const sedeId = button.getAttribute('data-sede-id');

        const modalIdInput = editAgenteModal.querySelector('#edit-agente-id');
        const modalNombreInput = editAgenteModal.querySelector('#edit-agente-nombre');
        const modalSedeSelect = editAgenteModal.querySelector('#edit-agente-sede');

        modalIdInput.value = id;
        modalNombreInput.value = nombre;
        modalSedeSelect.value = sedeId;
    });
        modalNombreInput.value = nombre;
    });

    // Lógica para el modal de edición de Closers
    const editCloserModal = document.getElementById('editCloserModal');
    editCloserModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');

        const modalIdInput = editCloserModal.querySelector('#edit-closer-id');
        const modalNombreInput = editCloserModal.querySelector('#edit-closer-nombre');

        modalIdInput.value = id;
        modalNombreInput.value = nombre;
    });

    // Lógica para el modal de confirmación de eliminación
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let itemToDelete = {};

    confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        itemToDelete = {
            id: button.getAttribute('data-id'),
            type: button.getAttribute('data-type'),
            tab: button.getAttribute('data-tab')
        };
    });

    confirmDeleteBtn.addEventListener('click', function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'actions/admin_actions.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);

        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = itemToDelete.type;
        form.appendChild(typeInput);

        const tabInput = document.createElement('input');
        tabInput.type = 'hidden';
        tabInput.name = 'tab';
        tabInput.value = itemToDelete.tab;
        form.appendChild(tabInput);

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = itemToDelete.id;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    });
});
</script>
