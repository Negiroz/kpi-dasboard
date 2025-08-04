<?php

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$empresa_id = $_SESSION['empresa_id'];

// --- Obtener datos para los formularios ---
$mes_actual = $_GET['mes'] ?? date('n');
$anio_actual = $_GET['anio'] ?? date('Y');

$stmt_sedes = $conn->prepare("SELECT * FROM sedes WHERE empresa_id = ? ORDER BY nombre");
$stmt_sedes->bind_param("i", $empresa_id);
$stmt_sedes->execute();
$sedes = $stmt_sedes->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_agentes = $conn->prepare("SELECT * FROM agentes WHERE empresa_id = ? ORDER BY nombre");
$stmt_agentes->bind_param("i", $empresa_id);
$stmt_agentes->execute();
$agentes = $stmt_agentes->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_closers = $conn->prepare("SELECT * FROM closers WHERE empresa_id = ? ORDER BY nombre");
$stmt_closers->bind_param("i", $empresa_id);
$stmt_closers->execute();
$closers = $stmt_closers->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM datos_financieros WHERE mes = ? AND anio = ? AND empresa_id = ?");
$stmt->bind_param("iii", $mes_actual, $anio_actual, $empresa_id);
$stmt->execute();
$datos_financieros_actuales = $stmt->get_result()->fetch_assoc() ?? [];
$stmt = $conn->prepare("SELECT * FROM metas_generales WHERE mes = ? AND anio = ? AND empresa_id = ?");
$stmt->bind_param("iii", $mes_actual, $anio_actual, $empresa_id);
$stmt->execute();
$metas_actuales = $stmt->get_result()->fetch_assoc() ?? [];

?>

<div class="container-fluid">
    <h1 class="h2">Gestión de Datos</h1>

    <!-- Selector de Mes y Año -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="data-management">
                <div class="col-md-4">
                    <label for="mes" class="form-label">Mes</label>
                    <select name="mes" id="mes" class="form-select">
                        <?php 
                            $meses_espanol = [
                                1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                            ];
                            for ($i = 1; $i <= 12; $i++):
                        ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == $mes_actual) ? 'selected' : ''; ?>><?php echo $meses_espanol[$i]; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="anio" class="form-label">Año</label>
                    <input type="number" name="anio" id="anio" class="form-control" value="<?php echo htmlspecialchars($anio_actual); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Cargar Datos del Periodo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formularios -->
    <div class="row">
        <!-- Datos Financieros y Metas -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">Datos Financieros y Metas Generales</div>
                <div class="card-body">
                    <form class="data-form" data-form-type="financiero">
                        <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">
                        <input type="hidden" name="anio" value="<?php echo $anio_actual; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <h6 class="text-muted">Datos del Mes</h6>
                        <div class="mb-3">
                            <label for="facturacion_cobrada" class="form-label">Facturación Cobrada ($)</label>
                            <input type="number" step="0.01" class="form-control" name="facturacion_cobrada" value="<?php echo htmlspecialchars($datos_financieros_actuales['facturacion_cobrada'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="facturas_cobradas" class="form-label">Facturas Cobradas (#)</label>
                            <input type="number" class="form-control" name="facturas_cobradas" value="<?php echo htmlspecialchars($datos_financieros_actuales['facturas_cobradas'] ?? ''); ?>">
                        </div>
                        <hr>
                        <h6 class="text-muted">Metas del Mes</h6>
                        <div class="mb-3">
                            <label for="meta_instalaciones" class="form-label">Meta de Instalaciones</label>
                            <input type="number" class="form-control" name="meta_instalaciones" value="<?php echo htmlspecialchars($metas_actuales['meta_instalaciones'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="meta_clientes" class="form-label">Meta de Clientes (Total)</label>
                            <input type="number" class="form-control" name="meta_clientes" value="<?php echo htmlspecialchars($metas_actuales['meta_clientes'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <span class="save-status ms-2"></span>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rendimiento por Sede -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">Rendimiento por Sede (Instalaciones)</div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <form class="data-form" data-form-type="sedes">
                        <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">
                        <input type="hidden" name="anio" value="<?php echo $anio_actual; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <?php 
                            $rendimiento_sedes_raw = [];
                            $stmt = $conn->prepare("SELECT sede_id, instalaciones FROM rendimiento_sedes WHERE mes = ? AND anio = ? AND empresa_id = ?");
                            $stmt->bind_param("iii", $mes_actual, $anio_actual, $empresa_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $rendimiento_sedes_raw[$row['sede_id']] = $row['instalaciones'];
                            }
                        foreach ($sedes as $sede): ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text" style="width: 150px;"><?php echo htmlspecialchars($sede['nombre']); ?></span>
                                <input type="number" class="form-control" name="sedes[<?php echo $sede['id']; ?>]" value="<?php echo htmlspecialchars($rendimiento_sedes_raw[$sede['id']] ?? ''); ?>">
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                        <span class="save-status ms-2"></span>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Rendimiento por Agente -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">Rendimiento por Agente</div>
                <div class="card-body">
                    <form class="data-form" data-form-type="agentes">
                        <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">
                        <input type="hidden" name="anio" value="<?php echo $anio_actual; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <table class="table table-sm">
                            <thead><tr><th>Agente</th><th>Cierres</th><th>Prospectos</th><th>Meta Cierres</th><th>Meta Prospectos</th></tr></thead>
                            <tbody>
                            <?php 
                                $rendimiento_agentes_raw = [];
                                $stmt = $conn->prepare("SELECT agente_id, cierres, prospectos, meta_cierres, meta_prospectos FROM rendimiento_agentes WHERE mes = ? AND anio = ? AND empresa_id = ?");
                                $stmt->bind_param("iii", $mes_actual, $anio_actual, $empresa_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    $rendimiento_agentes_raw[$row['agente_id']] = $row;
                                }
                            foreach ($agentes as $agente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agente['nombre']); ?></td>
                                    <td><input type="number" class="form-control form-control-sm" name="agentes[<?php echo $agente['id']; ?>][cierres]" value="<?php echo htmlspecialchars($rendimiento_agentes_raw[$agente['id']]['cierres'] ?? ''); ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="agentes[<?php echo $agente['id']; ?>][prospectos]" value="<?php echo htmlspecialchars($rendimiento_agentes_raw[$agente['id']]['prospectos'] ?? ''); ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="agentes[<?php echo $agente['id']; ?>][meta_cierres]" value="<?php echo htmlspecialchars($rendimiento_agentes_raw[$agente['id']]['meta_cierres'] ?? ''); ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="agentes[<?php echo $agente['id']; ?>][meta_prospectos]" value="<?php echo htmlspecialchars($rendimiento_agentes_raw[$agente['id']]['meta_prospectos'] ?? ''); ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <span class="save-status ms-2"></span>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rendimiento por Closer -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">Rendimiento por Closer</div>
                <div class="card-body">
                    <form class="data-form" data-form-type="closers">
                        <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">
                        <input type="hidden" name="anio" value="<?php echo $anio_actual; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                         <table class="table table-sm">
                            <thead><tr><th>Closer</th><th>Cierres</th><th>Meta Cierres</th></tr></thead>
                            <tbody>
                            <?php 
                                $rendimiento_closers_raw = [];
                                $stmt = $conn->prepare("SELECT closer_id, cierres, meta_cierres FROM rendimiento_closers WHERE mes = ? AND anio = ? AND empresa_id = ?");
                                $stmt->bind_param("iii", $mes_actual, $anio_actual, $empresa_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    $rendimiento_closers_raw[$row['closer_id']] = $row;
                                }
                            foreach ($closers as $closer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($closer['nombre']); ?></td>
                                    <td><input type="number" class="form-control form-control-sm" name="closers[<?php echo $closer['id']; ?>][cierres]" value="<?php echo htmlspecialchars($rendimiento_closers_raw[$closer['id']]['cierres'] ?? ''); ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="closers[<?php echo $closer['id']; ?>][meta_cierres]" value="<?php echo htmlspecialchars($rendimiento_closers_raw[$closer['id']]['meta_cierres'] ?? ''); ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <span class="save-status ms-2"></span>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.data-form');

    const csrfToken = "<?php echo $csrf_token; ?>"; // Inyectar el token CSRF

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const statusEl = this.querySelector('.save-status');
            const button = this.querySelector('button[type="submit"]');
            statusEl.textContent = 'Guardando...';
            statusEl.className = 'save-status ms-2 text-muted';
            button.disabled = true;

            const formData = new FormData(this);
            formData.append('form_type', this.dataset.formType);
            formData.append('csrf_token', csrfToken); // Añadir el token CSRF

            fetch('actions/save_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    statusEl.textContent = '¡Guardado!';
                    statusEl.className = 'save-status ms-2 text-success fw-bold';
                } else {
                    statusEl.textContent = 'Error.';
                    statusEl.className = 'save-status ms-2 text-danger fw-bold';
                    console.error(data.message);
                }
            })
            .catch(error => {
                statusEl.textContent = 'Error de conexión.';
                statusEl.className = 'save-status ms-2 text-danger fw-bold';
                console.error('Error:', error);
            })
            .finally(() => {
                button.disabled = false;
                setTimeout(() => {
                    statusEl.textContent = '';
                }, 3000);
            });
        });
    });
});
</script>