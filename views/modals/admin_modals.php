<!-- Modal para Crear Nueva Empresa -->
<div class="modal fade" id="create-company-modal" tabindex="-1" aria-labelledby="createCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCompanyModalLabel">Crear Nueva Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-company-form">
                    <input type="hidden" name="action" value="create_company">
                    <div class="mb-3">
                        <label for="new-company-name" class="form-label">Nombre de la Empresa</label>
                        <input type="text" class="form-control" id="new-company-name" name="nombre" required>
                    </div>
                    <div class="alert alert-danger mt-2 d-none" id="create-company-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="save-new-company">Guardar Empresa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir/Editar Usuario -->
<div class="modal fade" id="user-modal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Añadir Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="user-id" name="id">
                    <input type="hidden" name="empresa_id" id="user-empresa-id">
                    <div class="mb-3">
                        <label for="user-nombre" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="user-nombre" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="user-password" class="form-label">Contraseña (dejar en blanco para no cambiar)</label>
                        <input type="password" class="form-control" id="user-password" name="password">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="user-is-superuser" name="is_superuser">
                        <label class="form-check-label" for="user-is-superuser">Es Superusuario</label>
                    </div>
                    <div class="alert alert-danger mt-2 d-none" id="user-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="save-user-button">Guardar Usuario</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir/Editar Agente -->
<div class="modal fade" id="agent-modal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentModalLabel">Añadir Agente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="agent-form">
                    <input type="hidden" id="agent-id" name="id">
                    <div class="mb-3">
                        <label for="agent-nombre" class="form-label">Nombre del Agente</label>
                        <input type="text" class="form-control" id="agent-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="agent-empresa-select" class="form-label">Empresa</label>
                        <select class="form-select" id="agent-empresa-select" name="empresa_id" required>
                            <!-- Opciones cargadas dinámicamente por JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="agent-sede-select" class="form-label">Sede</label>
                        <select class="form-select" id="agent-sede-select" name="sede_id" required>
                            <!-- Opciones cargadas dinámicamente por JS -->
                        </select>
                    </div>
                    <div class="alert alert-danger mt-2 d-none" id="agent-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="save-agent-button">Guardar Agente</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir/Editar Closer -->
<div class="modal fade" id="closer-modal" tabindex="-1" aria-labelledby="closerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closerModalLabel">Añadir Closer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="closer-form">
                    <input type="hidden" id="closer-id" name="id">
                    <div class="mb-3">
                        <label for="closer-nombre" class="form-label">Nombre del Closer</label>
                        <input type="text" class="form-control" id="closer-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="closer-empresa-select" class="form-label">Empresa</label>
                        <select class="form-select" id="closer-empresa-select" name="empresa_id" required>
                            <!-- Opciones cargadas dinámicamente por JS -->
                        </select>
                    </div>
                    <div class="alert alert-danger mt-2 d-none" id="closer-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="save-closer-button">Guardar Closer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir/Editar Sede -->
<div class="modal fade" id="location-modal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Añadir Sede</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="location-form">
                    <input type="hidden" id="location-id" name="id">
                    <div class="mb-3">
                        <label for="location-nombre" class="form-label">Nombre de la Sede</label>
                        <input type="text" class="form-control" id="location-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="location-empresa-select" class="form-label">Empresa</label>
                        <select class="form-select" id="location-empresa-select" name="empresa_id" required>
                            <!-- Opciones cargadas dinámicamente por JS -->
                        </select>
                    </div>
                    <div class="alert alert-danger mt-2 d-none" id="location-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="save-location-button">Guardar Sede</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir/Editar KPI -->
<div class="modal fade" id="kpi-modal" tabindex="-1" aria-labelledby="kpiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kpiModalLabel">Añadir KPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="kpi-form">
                    <input type="hidden" id="kpi-id" name="id">
                    <input type="hidden" name="empresa_id" id="kpi-empresa-id">
                    <div class="mb-3">
                        <label for="kpi-nombre" class="form-label">Nombre del KPI</label>
                        <input type="text" class="form-control" id="kpi-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="kpi-descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="kpi-descripcion" name="descripcion"></textarea>
                    </div>
                    <div class="alert alert-danger mt-2 d-none" id="kpi-error"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="save-kpi-button">Guardar KPI</button>
            </div>
        </div>
    </div>
</div>