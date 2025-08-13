// Función de inicialización para la página de administración
function initAdminPage() {
    console.log('initAdminPage() called.');
    // Comprobación para asegurar que el script solo se ejecute si el contenedor principal de admin existe.
    const adminView = document.getElementById('admin-view');
    if (!adminView) {
        console.error('admin-view element not found. Exiting initAdminPage.');
        return;
    }
    console.log('admin-view element found.');

    // --- Elementos del DOM ---
    const empresaSelect = document.getElementById('empresa-select');
    const usersTableContainer = document.getElementById('users-table-container');
    const agentsTableContainer = document.getElementById('agents-table-container');
    const closersTableContainer = document.getElementById('closers-table-container');
    const locationsTableContainer = document.getElementById('locations-table-container');

    const btnAddUser = document.getElementById('btn-add-user');
    const btnAddAgent = document.getElementById('btn-add-agent');
    const btnAddCloser = document.getElementById('btn-add-closer');
    const btnAddLocation = document.getElementById('btn-add-location');

    // --- Modales y Formularios ---
    const createCompanyModalEl = document.getElementById('create-company-modal');
    const userModalEl = document.getElementById('user-modal');
    const agentModalEl = document.getElementById('agent-modal');
    const closerModalEl = document.getElementById('closer-modal');
    const locationModalEl = document.getElementById('location-modal');

    if (!createCompanyModalEl || !userModalEl || !agentModalEl || !closerModalEl || !locationModalEl) {
        console.error('Uno o más elementos de modales no se encontraron en el DOM. Asegúrate de que admin_modals.php está incluido y actualizado.');
        return;
    }

    const createCompanyModal = new bootstrap.Modal(createCompanyModalEl);
    const userModal = new bootstrap.Modal(userModalEl);
    const agentModal = new bootstrap.Modal(agentModalEl);
    const closerModal = new bootstrap.Modal(closerModalEl);
    const locationModal = new bootstrap.Modal(locationModalEl);

    const createCompanyForm = document.getElementById('create-company-form');
    const userForm = document.getElementById('user-form');
    const agentForm = document.getElementById('agent-form');
    const closerForm = document.getElementById('closer-form');
    const locationForm = document.getElementById('location-form');

    let selectedCompanyId = null;

    // --- Funciones de API ---
    async function apiRequest(action, data = {}) {
        console.log(`Making API request: ${action} with data:`, data);
        const formData = new FormData();
        formData.append('action', action);
        if (typeof window.CSRF_TOKEN === 'undefined' || !window.CSRF_TOKEN) {
            console.error('CSRF_TOKEN no está definido o es inválido en window.CSRF_TOKEN.');
            return { success: false, message: 'Error de seguridad. Recargue la página.' };
        }
        formData.append('csrf_token', window.CSRF_TOKEN);

        for (const key in data) {
            formData.append(key, data[key]);
        }

        try {
            const response = await fetch('actions/admin_actions.php', {
                method: 'POST',
                body: formData
            });
            console.log(`API response status for ${action}:`, response.status);
            if (!response.ok) {
                const errorText = await response.text();
                console.error(`API response error for ${action}:`, errorText);
                throw new Error(`Error HTTP: ${response.status} - ${errorText}`);
            }
            const jsonResponse = await response.json();
            console.log(`API response data for ${action}:`, jsonResponse);
            return jsonResponse;
        } catch (error) {
            console.error('Error en la petición API:', error);
            return { success: false, message: 'Error de comunicación con el servidor.' };
        }
    }

    // --- Lógica de Renderizado ---
    function renderTable(container, headers, data, type) {
        console.log(`Rendering ${type} table with data:`, data);
        if (data.length === 0) {
            container.innerHTML = '<p class="text-muted">No se encontraron resultados.</p>';
            return;
        }
        const table = document.createElement('table');
        table.className = 'table table-striped table-hover';
        const thead = table.createTHead().insertRow();
        headers.forEach(text => thead.innerHTML += `<th>${text}</th>`);

        const tbody = table.createTBody();
        data.forEach(row => {
            const tr = tbody.insertRow();
            let rowHtml = `<td>${row.id}</td>`;
            if (type === 'users') {
                rowHtml += `<td>${row.username}</td>`;
                rowHtml += `<td>${row.is_superuser ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'}</td>`;
            } else if (type === 'agents') {
                rowHtml += `<td>${row.nombre}</td>`;
                rowHtml += `<td>${row.empresa_nombre || ''}</td>`;
                rowHtml += `<td>${row.sede_nombre || ''}</td>`;
            } else if (type === 'closers') {
                rowHtml += `<td>${row.nombre}</td>`;
                rowHtml += `<td>${row.empresa_nombre || ''}</td>`;
            } else if (type === 'locations') {
                rowHtml += `<td>${row.nombre}</td>`;
                rowHtml += `<td>${row.empresa_nombre || ''}</td>`;
            }
            rowHtml += `
                <td>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="${row.id}" data-type="${type}" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}" data-type="${type}" title="Eliminar"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tr.innerHTML = rowHtml;
        });
        container.innerHTML = '';
        container.appendChild(table);
    }

    async function loadCompanyData(companyId) {
        console.log(`Loading data for company ID: ${companyId}`);
        selectedCompanyId = companyId;
        btnAddUser.disabled = false;
        btnAddAgent.disabled = false;
        btnAddCloser.disabled = false;
        btnAddLocation.disabled = false;

        // Cargar Usuarios
        usersTableContainer.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Cargando...</span></div>';
        const usersResponse = await apiRequest('get_users', { empresa_id: companyId });
        if (usersResponse.success) {
            renderTable(usersTableContainer, ['ID', 'Usuario', 'Superusuario', 'Acciones'], usersResponse.data, 'users');
        } else {
            usersTableContainer.innerHTML = `<p class="text-danger">${usersResponse.message}</p>`;
        }

        // Cargar Agentes
        agentsTableContainer.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Cargando...</span></div>';
        const agentsResponse = await apiRequest('get_agents', { empresa_id: companyId });
        if (agentsResponse.success) {
            renderTable(agentsTableContainer, ['ID', 'Nombre', 'Empresa', 'Sede', 'Acciones'], agentsResponse.data, 'agents');
        } else {
            agentsTableContainer.innerHTML = `<p class="text-danger">${agentsResponse.message}</p>`;
        }

        // Cargar Closers
        closersTableContainer.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Cargando...</span></div>';
        const closersResponse = await apiRequest('get_closers', { empresa_id: companyId });
        if (closersResponse.success) {
            renderTable(closersTableContainer, ['ID', 'Nombre', 'Empresa', 'Acciones'], closersResponse.data, 'closers');
        } else {
            closersTableContainer.innerHTML = `<p class="text-danger">${closersResponse.message}</p>`;
        }

        // Cargar Sedes
        locationsTableContainer.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Cargando...</span></div>';
        const locationsResponse = await apiRequest('get_locations', { empresa_id: companyId });
        if (locationsResponse.success) {
            renderTable(locationsTableContainer, ['ID', 'Nombre', 'Empresa', 'Acciones'], locationsResponse.data, 'locations');
        } else {
            locationsTableContainer.innerHTML = `<p class="text-danger">${locationsResponse.message}</p>`;
        }
    }

    async function loadCompanies() {
        console.log('Loading companies...');
        const response = await apiRequest('get_companies');
        if (response.success && response.data.length > 0) {
            empresaSelect.innerHTML = '<option selected disabled value="">Selecciona una empresa...</option>';
            response.data.forEach(company => {
                empresaSelect.innerHTML += `<option value="${company.id}">${company.nombre}</option>`;
            });
            console.log('Companies loaded successfully.', response.data);
        } else if (response.success) {
             empresaSelect.innerHTML = '<option selected disabled>No hay empresas. Crea una para empezar.</option>';
             console.log('No companies found.');
        } else {
            empresaSelect.innerHTML = '<option selected disabled>Error al cargar</option>';
            console.error('Failed to load companies:', response.message);
        }
    }

    async function loadCompaniesToSelect(selectElementId, selectedValue = null) {
        const selectElement = document.getElementById(selectElementId);
        if (!selectElement) return;

        selectElement.innerHTML = '<option value="">Cargando empresas...</option>';
        const response = await apiRequest('get_companies');
        if (response.success && response.data.length > 0) {
            selectElement.innerHTML = '<option value="">Selecciona una empresa...</option>';
            response.data.forEach(company => {
                const option = document.createElement('option');
                option.value = company.id;
                option.textContent = company.nombre;
                if (selectedValue && company.id == selectedValue) {
                    option.selected = true;
                }
                selectElement.appendChild(option);
            });
        } else {
            selectElement.innerHTML = '<option value="">No hay empresas disponibles</option>';
        }
    }

    async function loadLocationsToSelect(selectElementId, companyId, selectedValue = null) {
        const selectElement = document.getElementById(selectElementId);
        if (!selectElement) return;

        selectElement.innerHTML = '<option value="">Cargando sedes...</option>';
        if (!companyId) {
            selectElement.innerHTML = '<option value="">Selecciona una empresa primero</option>';
            return;
        }

        const response = await apiRequest('get_locations_by_company', { empresa_id: companyId });
        if (response.success && response.data.length > 0) {
            selectElement.innerHTML = '<option value="">Selecciona una sede...</option>';
            response.data.forEach(location => {
                const option = document.createElement('option');
                option.value = location.id;
                option.textContent = location.nombre;
                if (selectedValue && location.id == selectedValue) {
                    option.selected = true;
                }
                selectElement.appendChild(option);
            });
        } else {
            selectElement.innerHTML = '<option value="">No hay sedes disponibles</option>';
        }
    }

    // --- Manejadores de Eventos ---
    if (empresaSelect) {
        empresaSelect.addEventListener('change', () => loadCompanyData(empresaSelect.value));
    }

    if (document.getElementById('save-new-company')) {
        document.getElementById('save-new-company').addEventListener('click', async () => {
            const companyName = document.getElementById('new-company-name').value.trim();
            if (!companyName) { alert('El nombre es obligatorio.'); return; }
            const response = await apiRequest('create_company', { nombre: companyName });
            if (response.success) {
                createCompanyModal.hide();
                createCompanyForm.reset();
                await loadCompanies();
            } else {
                const errorDiv = document.getElementById('create-company-error');
                errorDiv.textContent = response.message;
                errorDiv.classList.remove('d-none');
            }
        });
    }

    // Abrir modal para añadir usuario
    if (btnAddUser) {
        btnAddUser.addEventListener('click', () => {
            userForm.reset();
            document.getElementById('user-id').value = '';
            document.getElementById('user-empresa-id').value = selectedCompanyId;
            document.getElementById('userModalLabel').textContent = 'Añadir Usuario';
            userModal.show();
        });
    }

    // Abrir modal para añadir Agente
    if (btnAddAgent) {
        btnAddAgent.addEventListener('click', async () => {
            agentForm.reset();
            document.getElementById('agent-id').value = '';
            // Cargar empresas para el selector de empresa del agente
            await loadCompaniesToSelect('agent-empresa-select', selectedCompanyId);
            // Cargar sedes para el selector de sede del agente (basado en la empresa seleccionada)
            const agentEmpresaSelect = document.getElementById('agent-empresa-select');
            if (agentEmpresaSelect.value) {
                await loadLocationsToSelect('agent-sede-select', agentEmpresaSelect.value);
            } else {
                document.getElementById('agent-sede-select').innerHTML = '<option value="">Selecciona una empresa primero</option>';
            }
            // Event listener para actualizar sedes cuando cambia la empresa
            agentEmpresaSelect.onchange = () => loadLocationsToSelect('agent-sede-select', agentEmpresaSelect.value);

            document.getElementById('agentModalLabel').textContent = 'Añadir Agente';
            agentModal.show();
        });
    }

    // Abrir modal para añadir Closer
    if (btnAddCloser) {
        btnAddCloser.addEventListener('click', async () => {
            closerForm.reset();
            document.getElementById('closer-id').value = '';
            // Cargar empresas para el selector de empresa del closer
            await loadCompaniesToSelect('closer-empresa-select', selectedCompanyId);
            document.getElementById('closerModalLabel').textContent = 'Añadir Closer';
            closerModal.show();
        });
    }

    // Abrir modal para añadir Sede
    if (btnAddLocation) {
        btnAddLocation.addEventListener('click', async () => {
            locationForm.reset();
            document.getElementById('location-id').value = '';
            // Cargar empresas para el selector de empresa de la sede
            await loadCompaniesToSelect('location-empresa-select', selectedCompanyId);
            document.getElementById('locationModalLabel').textContent = 'Añadir Sede';
            locationModal.show();
        });
    }

    // Guardar usuario (crear o editar)
    if (document.getElementById('save-user-button')) {
        document.getElementById('save-user-button').addEventListener('click', async () => {
            const userId = document.getElementById('user-id').value;
            const action = userId ? 'update_user' : 'create_user';
            const formData = new FormData(userForm);
            const data = Object.fromEntries(formData.entries());

            const response = await apiRequest(action, data);

            if (response.success) {
                userModal.hide();
                loadCompanyData(selectedCompanyId); // Recargar datos
            } else {
                document.getElementById('user-error').textContent = response.message;
                document.getElementById('user-error').classList.remove('d-none');
            }
        });
    }

    // Guardar Agente (crear o editar)
    if (document.getElementById('save-agent-button')) {
        document.getElementById('save-agent-button').addEventListener('click', async () => {
            const agentId = document.getElementById('agent-id').value;
            const action = agentId ? 'update_agent' : 'create_agent';
            const formData = new FormData(agentForm);
            const data = Object.fromEntries(formData.entries());
            data.empresa_id = document.getElementById('agent-empresa-select').value;
            data.sede_id = document.getElementById('agent-sede-select').value;

            const response = await apiRequest(action, data);

            if (response.success) {
                agentModal.hide();
                loadCompanyData(selectedCompanyId); // Recargar datos
            } else {
                document.getElementById('agent-error').textContent = response.message;
                document.getElementById('agent-error').classList.remove('d-none');
            }
        });
    }

    // Guardar Closer (crear o editar)
    if (document.getElementById('save-closer-button')) {
        document.getElementById('save-closer-button').addEventListener('click', async () => {
            const closerId = document.getElementById('closer-id').value;
            const action = closerId ? 'update_closer' : 'create_closer';
            const formData = new FormData(closerForm);
            const data = Object.fromEntries(formData.entries());
            data.empresa_id = document.getElementById('closer-empresa-select').value;

            const response = await apiRequest(action, data);

            if (response.success) {
                closerModal.hide();
                loadCompanyData(selectedCompanyId); // Recargar datos
            } else {
                document.getElementById('closer-error').textContent = response.message;
                document.getElementById('closer-error').classList.remove('d-none');
            }
        });
    }

    // Guardar Sede (crear o editar)
    if (document.getElementById('save-location-button')) {
        document.getElementById('save-location-button').addEventListener('click', async () => {
            const locationId = document.getElementById('location-id').value;
            const action = locationId ? 'update_location' : 'create_location';
            const formData = new FormData(locationForm);
            const data = Object.fromEntries(formData.entries());
            data.empresa_id = document.getElementById('location-empresa-select').value;

            const response = await apiRequest(action, data);

            if (response.success) {
                locationModal.hide();
                loadCompanyData(selectedCompanyId); // Recargar datos
            } else {
                document.getElementById('location-error').textContent = response.message;
                document.getElementById('location-error').classList.remove('d-none');
            }
        });
    }

    // Delegación de eventos para botones de editar y eliminar
    if (adminView) {
        adminView.addEventListener('click', async (e) => {
            const target = e.target.closest('button');
            if (!target) return;

            const id = target.dataset.id;
            const type = target.dataset.type;

            if (target.classList.contains('btn-edit')) {
                const response = await apiRequest(`get_${type.slice(0, -1)}`, { id });
                if (response.success) {
                    if (type === 'users') {
                        const data = response.data;
                        document.getElementById('user-id').value = data.id;
                        document.getElementById('user-empresa-id').value = selectedCompanyId;
                        document.getElementById('user-nombre').value = data.username;
                        document.getElementById('user-is-superuser').checked = data.is_superuser;
                        document.getElementById('userModalLabel').textContent = 'Editar Usuario';
                        userModal.show();
                    } else if (type === 'agents') {
                        const data = response.data;
                        document.getElementById('agent-id').value = data.id;
                        document.getElementById('agent-nombre').value = data.nombre;
                        await loadCompaniesToSelect('agent-empresa-select', data.empresa_id);
                        await loadLocationsToSelect('agent-sede-select', data.empresa_id, data.sede_id);
                        document.getElementById('agent-empresa-select').onchange = () => loadLocationsToSelect('agent-sede-select', document.getElementById('agent-empresa-select').value);
                        document.getElementById('agentModalLabel').textContent = 'Editar Agente';
                        agentModal.show();
                    } else if (type === 'closers') {
                        const data = response.data;
                        document.getElementById('closer-id').value = data.id;
                        document.getElementById('closer-nombre').value = data.nombre;
                        await loadCompaniesToSelect('closer-empresa-select', data.empresa_id);
                        document.getElementById('closerModalLabel').textContent = 'Editar Closer';
                        closerModal.show();
                    } else if (type === 'locations') {
                        const data = response.data;
                        document.getElementById('location-id').value = data.id;
                        document.getElementById('location-nombre').value = data.nombre;
                        await loadCompaniesToSelect('location-empresa-select', data.empresa_id);
                        document.getElementById('locationModalLabel').textContent = 'Editar Sede';
                        locationModal.show();
                    }
                }
            } else if (target.classList.contains('btn-delete')) {
                if (confirm(`¿Estás seguro de que quieres eliminar este elemento?`)) {
                    const response = await apiRequest(`delete_${type.slice(0, -1)}`, { id });
                    if (response.success) {
                        loadCompanyData(selectedCompanyId);
                    } else {
                        alert(response.message);
                    }
                }
            }
        });
    }

    // --- Inicialización ---
    loadCompanies();
}