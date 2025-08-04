<?php

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}



$empresa_id = $_SESSION['empresa_id'];



// --- Generar Periodos (6 meses hacia atrás desde el seleccionado) ---
$periodos = [];
for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("$SELECTED_YEAR-$SELECTED_MONTH-01");
    $date->modify("-$i months");
    $y = $date->format('Y');
    $m = $date->format('n');
    $periodos[] = ['anio' => $y, 'mes' => $m, 'label' => $date->format('M y')];
}

// --- Obtener Datos de la Base de Datos para el Periodo ---

// KPIs Generales
$stmt = $conn->prepare("SELECT * FROM metas_generales WHERE anio = ? AND mes = ? AND empresa_id = ?");
$stmt->bind_param("iii", $SELECTED_YEAR, $SELECTED_MONTH, $empresa_id);
$stmt->execute();
$general_kpis = $stmt->get_result()->fetch_assoc() ?? [];
$stmt = $conn->prepare("SELECT SUM(instalaciones) as total FROM rendimiento_sedes WHERE anio = ? AND mes = ? AND empresa_id = ?");
$stmt->bind_param("iii", $SELECTED_YEAR, $SELECTED_MONTH, $empresa_id);
$stmt->execute();
$total_instalaciones = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$general_kpis['instalaciones_actuales'] = $total_instalaciones;

// Datos Financieros (Histórico)
$financiero_data_raw = [];
$placeholders = implode(', ', array_fill(0, count($periodos), '(?, ?)'));
$types = str_repeat('ii', count($periodos));
$params = [];
foreach ($periodos as $p) {
    $params[] = $p['anio'];
    $params[] = $p['mes'];
}

$stmt = $conn->prepare("SELECT mes, anio, facturacion_cobrada, facturas_cobradas FROM datos_financieros WHERE empresa_id = ? AND (anio, mes) IN ($placeholders) ORDER BY anio ASC, mes ASC");
$stmt->bind_param("i" . $types, $empresa_id, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $financiero_data_raw[$row['anio'] . '-' . $row['mes']] = $row;
}

foreach ($periodos as $p) {
    $financiero['meses'][] = $p['label'];
    $key = $p['anio'] . '-' . $p['mes'];
    $res = $financiero_data_raw[$key] ?? ['facturacion_cobrada' => 0, 'facturas_cobradas' => 0];
    $financiero['facturacion'][] = (float)$res['facturacion_cobrada'];
    $financiero['facturas'][] = (int)$res['facturas_cobradas'];
    $financiero['arpu'][] = ($res['facturas_cobradas'] > 0) ? ($res['facturacion_cobrada'] / $res['facturas_cobradas']) : 0;
}

// --- Cálculo de Proyección de Clientes ---
$clientes_actuales = end($financiero['facturas']);
$meta_clientes = $general_kpis['meta_clientes'] ?? 0;
$clientes_faltantes = max(0, $meta_clientes - $clientes_actuales);
$crecimiento_total = end($financiero['facturas']) - $financiero['facturas'][0];
$crecimiento_promedio_mensual = $crecimiento_total / 5; // 5 intervalos en 6 meses

$fecha_proyeccion = "N/A";
if ($crecimiento_promedio_mensual > 0 && $clientes_faltantes > 0) {
    $meses_para_meta = ceil($clientes_faltantes / $crecimiento_promedio_mensual);
    $fecha_meta = new DateTime("$SELECTED_YEAR-$SELECTED_MONTH-01");
    $fecha_meta->modify(" +$meses_para_meta months");
    $fecha_proyeccion = $fecha_meta->format('F Y');
}
$general_kpis['clientes_actuales'] = $clientes_actuales;
$general_kpis['clientes_faltantes'] = $clientes_faltantes;
$general_kpis['fecha_proyeccion'] = $fecha_proyeccion;


// Datos Sedes (Histórico)
$sedes_data = [];
$stmt_sedes = $conn->prepare("SELECT id, nombre FROM sedes WHERE empresa_id = ? ORDER BY nombre");
$stmt_sedes->bind_param("i", $empresa_id);
$stmt_sedes->execute();
$sedes_result = $stmt_sedes->get_result();
$all_sedes = [];
while($sede = $sedes_result->fetch_assoc()) {
    $all_sedes[$sede['id']] = ['id' => $sede['id'], 'nombre' => $sede['nombre'], 'historico' => array_fill(0, count($periodos), 0)];
}

if (!empty($all_sedes)) {
    
    $sede_placeholders = implode(', ', array_fill(0, count($all_sedes), '?'));
    $period_params = [];
    foreach ($periodos as $p) {
        $period_params[] = $p['anio'];
        $period_params[] = $p['mes'];
    }
    $period_placeholders = implode(', ', array_fill(0, count($periodos), '(?, ?)'));
    $types = str_repeat('i', count($all_sedes)) . str_repeat('ii', count($periodos));
    $params = array_merge(array_keys($all_sedes), $period_params);

    $stmt = $conn->prepare("SELECT sede_id, mes, anio, instalaciones FROM rendimiento_sedes WHERE empresa_id = ? AND sede_id IN ($sede_placeholders) AND (anio, mes) IN ($period_placeholders) ORDER BY sede_id, anio, mes");
    $stmt->bind_param("i" . $types, $empresa_id, ...$params);
    $stmt->execute();
    $rendimiento_sedes_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $rendimiento_sedes_raw = [];
}

$rendimiento_lookup = [];
foreach ($rendimiento_sedes_raw as $row) {
    $rendimiento_lookup[$row['sede_id']]["{$row['anio']}-{$row['mes']}"] = $row['instalaciones'];
}

foreach ($all_sedes as $id => $data) {
    $sedes_data[$id] = $data;
    foreach ($periodos as $idx => $p) {
        $key = "{$p['anio']}-{$p['mes']}";
        $sedes_data[$id]['historico'][$idx] = $rendimiento_lookup[$id][$key] ?? 0;
    }
}

// Ordenar sedes por las instalaciones del último mes (de mayor a menor)
usort($sedes_data, function($a, $b) use ($periodos) {
    $last_month_idx = count($periodos) - 1;
    return $b['historico'][$last_month_idx] <=> $a['historico'][$last_month_idx];
});

// Datos Agentes (KPIs Mes Seleccionado + Histórico)
$stmt = $conn->prepare("SELECT a.nombre, s.nombre AS sede_nombre, ra.* FROM rendimiento_agentes ra JOIN agentes a ON ra.agente_id = a.id JOIN sedes s ON a.sede_id = s.id WHERE ra.anio = ? AND ra.mes = ? AND ra.empresa_id = ? ORDER BY ra.cierres DESC");
$stmt->bind_param("iii", $SELECTED_YEAR, $SELECTED_MONTH, $empresa_id);
$stmt->execute();
$agentes_kpi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$agentes_data = [];
$stmt_agentes = $conn->prepare("SELECT id, nombre FROM agentes WHERE empresa_id = ? ORDER BY nombre");
$stmt_agentes->bind_param("i", $empresa_id);
$stmt_agentes->execute();
$agentes_result = $stmt_agentes->get_result();
$all_agentes = [];
while($agente = $agentes_result->fetch_assoc()) {
    $all_agentes[$agente['id']] = ['id' => $agente['id'], 'nombre' => $agente['nombre'], 'historico_cierres' => array_fill(0, count($periodos), 0)];
}

if (!empty($all_agentes)) {
    $agente_placeholders = implode(', ', array_fill(0, count($all_agentes), '?'));
    $period_params = [];
    foreach ($periodos as $p) {
        $period_params[] = $p['anio'];
        $period_params[] = $p['mes'];
    }
    $period_placeholders = implode(', ', array_fill(0, count($periodos), '(?, ?)'));
    $types = str_repeat('i', count($all_agentes)) . str_repeat('ii', count($periodos));
    $params = array_merge(array_keys($all_agentes), $period_params);

    $stmt = $conn->prepare("SELECT agente_id, mes, anio, cierres FROM rendimiento_agentes WHERE empresa_id = ? AND agente_id IN ($agente_placeholders) AND (anio, mes) IN ($period_placeholders) ORDER BY agente_id, anio, mes");
    $stmt->bind_param("i" . $types, $empresa_id, ...$params);
    $stmt->execute();
    $rendimiento_agentes_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $rendimiento_agentes_raw = [];
}

$rendimiento_lookup = [];
foreach ($rendimiento_agentes_raw as $row) {
    $rendimiento_lookup[$row['agente_id']]["{$row['anio']}-{$row['mes']}"] = $row['cierres'];
}

foreach ($all_agentes as $id => $data) {
    $agentes_data[$id] = $data;
    foreach ($periodos as $idx => $p) {
        $key = "{$p['anio']}-{$p['mes']}";
        $agentes_data[$id]['historico_cierres'][$idx] = $rendimiento_lookup[$id][$key] ?? 0;
    }
}

// Datos Closers (KPIs Mes Seleccionado + Histórico)
$stmt = $conn->prepare("SELECT c.nombre, rc.* FROM rendimiento_closers rc JOIN closers c ON rc.closer_id = c.id WHERE rc.anio = ? AND rc.mes = ? AND rc.empresa_id = ? ORDER BY rc.cierres DESC");
$stmt->bind_param("iii", $SELECTED_YEAR, $SELECTED_MONTH, $empresa_id);
$stmt->execute();
$closers_kpi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$closers_data = [];
$stmt_closers = $conn->prepare("SELECT id, nombre FROM closers WHERE empresa_id = ? ORDER BY nombre");
$stmt_closers->bind_param("i", $empresa_id);
$stmt_closers->execute();
$closers_result = $stmt_closers->get_result();
$all_closers = [];
while($closer = $closers_result->fetch_assoc()) {
    $all_closers[$closer['id']] = ['id' => $closer['id'], 'nombre' => $closer['nombre'], 'historico_cierres' => array_fill(0, count($periodos), 0)];
}

if (!empty($all_closers)) {
    $closer_placeholders = implode(', ', array_fill(0, count($all_closers), '?'));
    $period_params = [];
    foreach ($periodos as $p) {
        $period_params[] = $p['anio'];
        $period_params[] = $p['mes'];
    }
    $period_placeholders = implode(', ', array_fill(0, count($periodos), '(?, ?)'));
    $types = str_repeat('i', count($all_closers)) . str_repeat('ii', count($periodos));
    $params = array_merge(array_keys($all_closers), $period_params);

    $stmt = $conn->prepare("SELECT closer_id, mes, anio, cierres FROM rendimiento_closers WHERE empresa_id = ? AND closer_id IN ($closer_placeholders) AND (anio, mes) IN ($period_placeholders) ORDER BY closer_id, anio, mes");
    $stmt->bind_param("i" . $types, $empresa_id, ...$params);
    $stmt->execute();
    $rendimiento_closers_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $rendimiento_closers_raw = [];
}

$rendimiento_lookup = [];
foreach ($rendimiento_closers_raw as $row) {
    $rendimiento_lookup[$row['closer_id']]["{$row['anio']}-{$row['mes']}"] = $row['cierres'];
}

foreach ($all_closers as $id => $data) {
    $closers_data[$id] = $data;
    foreach ($periodos as $idx => $p) {
        $key = "{$p['anio']}-{$p['mes']}";
        $closers_data[$id]['historico_cierres'][$idx] = $rendimiento_lookup[$id][$key] ?? 0;
    }
}

?>



<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h2">Dashboard</h1>
        <?php 
            $meses_espanol = [
                1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            $nombre_mes = $meses_espanol[(int)$SELECTED_MONTH];
        ?>
        <p class="text-muted">Datos del mes de <?php echo $nombre_mes . ' ' . $SELECTED_YEAR; ?></p>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="dashboardTab" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" id="dashboard-general-tab" data-bs-toggle="tab" data-bs-target="#dashboard-general-pane" type="button">General</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="dashboard-sedes-tab" data-bs-toggle="tab" data-bs-target="#dashboard-sedes-pane" type="button">Por Sede</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="dashboard-agentes-tab" data-bs-toggle="tab" data-bs-target="#dashboard-agentes-pane" type="button">Por Agente</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="dashboard-closers-tab" data-bs-toggle="tab" data-bs-target="#dashboard-closers-pane" type="button">Por Closer</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="dashboard-analysis-tab" data-bs-toggle="tab" data-bs-target="#dashboard-analysis-pane" type="button">Análisis Gerencial</button></li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content" id="dashboardTabContent">
        <!-- General View -->
        <div class="tab-pane fade show active" id="dashboard-general-pane" role="tabpanel">
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h5 class="card-title">Meta de Instalaciones</h5>
                            <?php 
                                $meta_instalaciones = $general_kpis['meta_instalaciones'] ?? 0;
                                $porcentaje = ($meta_instalaciones > 0) ? ($total_instalaciones / $meta_instalaciones) * 100 : 0;
                            ?>
                            <p class="kpi-card-value"><?php echo $total_instalaciones; ?> / <span class="kpi-card-meta"><?php echo $meta_instalaciones; ?></span></p>
                            <div class="progress mx-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $porcentaje; ?>%;" aria-valuenow="<?php echo $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo round($porcentaje); ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
                 <div class="col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title">Proyección de Clientes</h5>
                            <p class="kpi-card-value"><?php echo number_format($general_kpis['clientes_actuales'] ?? 0); ?> / <span class="kpi-card-meta"><?php echo number_format($general_kpis['meta_clientes'] ?? 0); ?></span></p>
                            <p class="kpi-card-text mb-0">Faltan <strong><?php echo number_format($general_kpis['clientes_faltantes'] ?? 0); ?></strong> para la meta.</p>
                            <p class="kpi-card-text"><small>Fecha estimada: <strong><?php echo htmlspecialchars($general_kpis['fecha_proyeccion']); ?></strong></small></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="card text-center h-100">
                        <div class="card-body">
                            <h5 class="card-title">ARPU (Mes Seleccionado)</h5>
                            <?php $arpu_actual = end($financiero['arpu']) ?? 0; ?>
                            <p class="kpi-card-value">$<?php echo number_format($arpu_actual, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Histórico de Facturación ($)</h5>
                            <div class="chart-container">
                                <canvas id="facturacion-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Histórico de Facturas (#)</h5>
                            <div class="chart-container">
                                <canvas id="facturas-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Comportamiento del ARPU ($)</h5>
                            <div class="chart-container">
                                <canvas id="arpu-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sedes View -->
        <div class="tab-pane fade" id="dashboard-sedes-pane" role="tabpanel">
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Tabla de Datos: Instalaciones por Sede</h4></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sede</th>
                                        <?php foreach($financiero['meses'] as $mes) { echo "<th>$mes</th>"; } ?>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totals_por_mes = array_fill(0, 6, 0);
                                    $grand_total = 0;
                                    foreach($sedes_data as $id => $sede): 
                                        $total_sede = array_sum($sede['historico']);
                                        $grand_total += $total_sede;
                                    ?>
                                    <tr data-id="<?php echo $id; ?>">
                                        <td><?php echo htmlspecialchars($sede['nombre']); ?></td>
                                        <?php 
                                        foreach($sede['historico'] as $i => $valor) {
                                            echo "<td>$valor</td>";
                                            $totals_por_mes[$i] += $valor;
                                        }
                                        ?>
                                        <td><strong><?php echo $total_sede; ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-group-divider fw-bold">
                                        <td>Total General</td>
                                        <?php 
                                        $total_instalaciones_historico = array_fill(0, count($periodos), 0);
                                        foreach($sedes_data as $id => $sede) {
                                            foreach($sede['historico'] as $i => $valor) {
                                                $total_instalaciones_historico[$i] += $valor;
                                            }
                                        }
                                        foreach($total_instalaciones_historico as $total_mes) { echo "<td>$total_mes</td>"; } 
                                        ?>
                                        <td><?php echo $grand_total; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Histórico de Instalaciones por Sede</h4></div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="sedes-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agentes View -->
        <div class="tab-pane fade" id="dashboard-agentes-pane" role="tabpanel">
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">KPIs por Agente</h4></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-striped table-hover">
                                <thead><tr><th>#</th><th>Agente</th><th>Sede</th><th>Cierres</th><th>Meta C.</th><th>% Cierre</th><th>Prospectos</th><th>Meta P.</th><th>% Pros.</th><th>Conversión</th></tr></thead>
                                <tbody>
                                <?php 
                                $row_num = 1; 
                                $total_cierres = 0;
                                $total_meta_cierres = 0;
                                $total_prospectos = 0;
                                $total_meta_prospectos = 0;
                                foreach($agentes_kpi as $agente): 
                                    $total_cierres += $agente['cierres'];
                                    $total_meta_cierres += $agente['meta_cierres'];
                                    $total_prospectos += $agente['prospectos'];
                                    $total_meta_prospectos += $agente['meta_prospectos'];
                                ?>
                                    <tr data-id="<?php echo $agente['agente_id']; ?>">
                                        <td><?php echo $row_num++; ?></td>
                                        <td><?php echo htmlspecialchars($agente['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($agente['sede_nombre'] ?? 'N/A'); ?></td>
                                        <td><?php echo $agente['cierres']; ?></td>
                                        <td><?php echo $agente['meta_cierres']; ?></td>
                                        <td><?php echo ($agente['meta_cierres'] > 0) ? round(($agente['cierres']/$agente['meta_cierres'])*100) . '%' : 'N/A'; ?></td>
                                        <td><?php echo $agente['prospectos']; ?></td>
                                        <td><?php echo $agente['meta_prospectos']; ?></td>
                                        <td><?php echo ($agente['meta_prospectos'] > 0) ? round(($agente['prospectos']/$agente['meta_prospectos'])*100) . '%' : 'N/A'; ?></td>
                                        <td><?php echo ($agente['prospectos'] > 0) ? round(($agente['cierres']/$agente['prospectos'])*100) . '%' : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-group-divider fw-bold">
                                        <td colspan="3">Total General</td>
                                        <td><?php echo $total_cierres; ?></td>
                                        <td><?php echo $total_meta_cierres; ?></td>
                                        <td><?php echo ($total_meta_cierres > 0) ? round(($total_cierres/$total_meta_cierres)*100) . '%' : 'N/A'; ?></td>
                                        <td><?php echo $total_prospectos; ?></td>
                                        <td><?php echo $total_meta_prospectos; ?></td>
                                        <td><?php echo ($total_meta_prospectos > 0) ? round(($total_prospectos/$total_meta_prospectos)*100) . '%' : 'N/A'; ?></td>
                                        <td><?php echo ($total_prospectos > 0) ? round(($total_cierres/$total_prospectos)*100) . '%' : 'N/A'; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Histórico de Cierres por Agente</h4></div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="agentes-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Closers View -->
        <div class="tab-pane fade" id="dashboard-closers-pane" role="tabpanel">
             <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">KPIs por Closer (Mes Seleccionado)</h4></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-striped table-hover">
                                <thead><tr><th>Closer</th><th>Cierres</th><th>Meta</th><th>% Cumplimiento</th></tr></thead>
                                <tbody>
                                <?php foreach($closers_kpi as $closer): ?>
                                    <tr data-id="<?php echo $closer['closer_id']; ?>">
                                        <td><?php echo htmlspecialchars($closer['nombre']); ?></td>
                                        <td><?php echo $closer['cierres']; ?></td>
                                        <td><?php echo $closer['meta_cierres']; ?></td>
                                        <td><?php echo ($closer['meta_cierres'] > 0) ? round(($closer['cierres']/$closer['meta_cierres'])*100) . '%' : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
             <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Histórico de Cierres por Closer</h4></div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="closers-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis View -->
        <div class="tab-pane fade" id="dashboard-analysis-pane" role="tabpanel">
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Análisis Gerencial</h4>
                    <button id="regenerate-analysis" class="btn btn-secondary btn-sm"><i class="fas fa-sync-alt me-2"></i> Regenerar Análisis</button>
                </div>
                <div class="card-body">
                    <div id="analysis-content-wrapper">
                        <!-- El contenido del análisis se cargará aquí -->
                        <div class="text-center p-5">
                            <p class="text-muted">Haga clic en una pestaña para ver el análisis.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartColors = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#6f42c1', '#fd7e14', '#20c997'];
    const mesesRaw = <?php echo json_encode($financiero['meses']); ?>;
    const meses = [...new Set(mesesRaw)]; // Eliminar duplicados
    let charts = {};
    let analysisLoaded = false;

    // Almacenar datos originales para filtrado
    const originalSedesData = <?php echo json_encode(array_values($sedes_data)); ?>;
    const originalAgentesData = <?php echo json_encode(array_values($agentes_data)); ?>;
    const originalClosersData = <?php echo json_encode(array_values($closers_data)); ?>;
    const totalInstalacionesHistorico = <?php echo json_encode($total_instalaciones_historico); ?>;

    // Sets para mantener el estado de selección
    let selectedSedes = new Set();
    let selectedAgentes = new Set();
    let selectedClosers = new Set();

    function initChart(id, config) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        if (charts[id]) charts[id].destroy();
        
        config.options = config.options || {};
        config.options.responsive = true;

        charts[id] = new Chart(canvas, config);
    }

    // --- Funciones de inicialización de gráficos ---
    function initGeneralCharts() {
        initChart('facturacion-chart', { type: 'line', data: { labels: meses, datasets: [{ label: 'Facturación ($)', data: <?php echo json_encode($financiero['facturacion']); ?>, borderColor: '#198754', fill: false }] } });
        initChart('facturas-chart', { type: 'line', data: { labels: meses, datasets: [{ label: 'Facturas (#)', data: <?php echo json_encode($financiero['facturas']); ?>, borderColor: '#0d6efd', fill: false }] } });
        initChart('arpu-chart', { type: 'bar', data: { labels: meses, datasets: [{ label: 'ARPU ($)', data: <?php echo json_encode($financiero['arpu']); ?>, backgroundColor: '#ffc107' }] } });
    }

    function initSedesChart() {
        let datasets;
        if (selectedSedes.size === 0) {
            // Mostrar todos si no hay selección
            datasets = originalSedesData.map((s, i) => ({
                label: s.nombre,
                data: s.historico,
                borderColor: chartColors[i % chartColors.length],
                fill: false,
                tension: 0.1
            }));
        } else {
            // Mostrar solo los seleccionados
            datasets = originalSedesData.filter(s => selectedSedes.has(s.id)).map((s, i) => ({
                label: s.nombre,
                data: s.historico,
                borderColor: chartColors[i % chartColors.length],
                fill: false,
                tension: 0.1
            }));
        }
        // Añadir la línea de total de instalaciones
        datasets.push({
            label: 'Total Instalaciones',
            data: totalInstalacionesHistorico,
            borderColor: '#000000', // Color negro para el total
            borderWidth: 2,
            fill: false,
            tension: 0.1
        });
        initChart('sedes-chart', { type: 'line', data: { labels: meses, datasets: datasets } });
    }

    function initAgentesChart() {
        let datasets;
        if (selectedAgentes.size === 0) {
            datasets = originalAgentesData.map((a, i) => ({ label: a.nombre, data: a.historico_cierres, borderColor: chartColors[i % chartColors.length], fill: false }));
        } else {
            datasets = originalAgentesData.filter(a => selectedAgentes.has(a.id)).map((a, i) => ({ label: a.nombre, data: a.historico_cierres, borderColor: chartColors[i % chartColors.length], fill: false }));
        }
        initChart('agentes-chart', { type: 'line', data: { labels: meses, datasets: datasets } });
    }

    function initClosersChart() {
        let datasets;
        if (selectedClosers.size === 0) {
            datasets = originalClosersData.map((c, i) => ({ label: c.nombre, data: c.historico_cierres, borderColor: chartColors[i % chartColors.length], fill: false }));
        } else {
            datasets = originalClosersData.filter(c => selectedClosers.has(c.id)).map((c, i) => ({ label: c.nombre, data: c.historico_cierres, borderColor: chartColors[i % chartColors.length], fill: false }));
        }
        initChart('closers-chart', { type: 'line', data: { labels: meses, datasets: datasets } });
    }

    function loadAnalysis() {
        const wrapper = document.getElementById('analysis-content-wrapper');
        wrapper.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Generando análisis...</p></div>`;
        analysisLoaded = true;

        fetch(`actions/generate_analysis.php?mes=<?php echo $SELECTED_MONTH; ?>&anio=<?php echo $SELECTED_YEAR; ?>`)
            .then(response => response.text())
            .then(html => { wrapper.innerHTML = html; })
            .catch(error => {
                wrapper.innerHTML = `<div class="alert alert-danger">Error al cargar el análisis: ${error}</div>`;
                analysisLoaded = false;
            });
    }

    // --- Lógica de Pestañas Manual ---
    const tabTriggers = document.querySelectorAll('#dashboardTab button[data-bs-toggle="tab"]');
    const tabPanes = document.querySelectorAll('#dashboardTabContent .tab-pane');

    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(event) {
            event.preventDefault(); // Prevenir el comportamiento por defecto de Bootstrap

            const targetPaneId = this.dataset.bsTarget; // Ej: '#dashboard-sedes-pane'
            const targetPane = document.querySelector(targetPaneId);

            if (!targetPane) return; // Verificación de seguridad

            // 1. Desactivar todos los botones de pestaña
            tabTriggers.forEach(t => t.classList.remove('active'));

            // 2. Ocultar todos los paneles de contenido
            tabPanes.forEach(p => p.classList.remove('show', 'active'));

            // 3. Activar el botón de pestaña clickeado
            this.classList.add('active');

            // 4. Mostrar el panel de contenido objetivo
            targetPane.classList.add('show', 'active');

            // 5. Inicializar el gráfico/análisis para el panel activado si no ha sido inicializado
            switch (targetPaneId) {
                case '#dashboard-general-pane':
                    initGeneralCharts(); // Siempre reinicializar los gráficos generales
                    break;
                case '#dashboard-sedes-pane':
                    initSedesChart(); // Siempre reinicializar para aplicar filtros
                    break;
                case '#dashboard-agentes-pane':
                    initAgentesChart(); // Siempre reinicializar para aplicar filtros
                    break;
                case '#dashboard-closers-pane':
                    initClosersChart(); // Siempre reinicializar para aplicar filtros
                    break;
                case '#dashboard-analysis-pane':
                    if (!analysisLoaded) loadAnalysis();
                    break;
            }
        });
    });

    // Inicializar gráficos de la pestaña visible por defecto (General)
    initGeneralCharts();

    // Listener para el botón de regenerar análisis
    document.getElementById('regenerate-analysis')?.addEventListener('click', () => {
        analysisLoaded = false; // Permitir recarga
        loadAnalysis();
    });

    

    // --- Lógica de filtrado de tablas ---
    document.querySelectorAll('#dashboard-sedes-pane table tbody tr').forEach(row => {
        row.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            if (selectedSedes.has(id)) {
                selectedSedes.delete(id);
                this.classList.remove('table-active');
            } else {
                selectedSedes.add(id);
                this.classList.add('table-active');
            }
            initSedesChart();
        });
    });

    document.querySelectorAll('#dashboard-agentes-pane table tbody tr').forEach(row => {
        row.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            if (selectedAgentes.has(id)) {
                selectedAgentes.delete(id);
                this.classList.remove('table-active');
            } else {
                selectedAgentes.add(id);
                this.classList.add('table-active');
            }
            initAgentesChart();
        });
    });

    document.querySelectorAll('#dashboard-closers-pane table tbody tr').forEach(row => {
        row.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            if (selectedClosers.has(id)) {
                selectedClosers.delete(id);
                this.classList.remove('table-active');
            } else {
                selectedClosers.add(id);
                this.classList.add('table-active');
            }
            initClosersChart();
        });
    });
});
</script>
