<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../db.php';

header('Content-Type: text/html; charset=utf-8');

// --- OBTENER EL PERIODO DE LA URL ---
$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');
$empresa_id = $_SESSION['empresa_id'];

// --- OBTENER DATOS DEL PERIODO ANTERIOR PARA COMPARACIÓN ---
$fecha_actual = new DateTime("$anio-$mes-01");
$fecha_anterior = (clone $fecha_actual)->modify('-1 month');
$mes_anterior = $fecha_anterior->format('n');
$anio_anterior = $fecha_anterior->format('Y');

// --- RECOPILAR DATOS ---
$stmt = $conn->prepare("SELECT * FROM datos_financieros WHERE anio = ? AND mes = ? AND empresa_id = ?");
$stmt->bind_param("iii", $anio, $mes, $empresa_id);
$stmt->execute();
$financiero_actual = $stmt->get_result()->fetch_assoc() ?? ['facturacion_cobrada' => 0, 'facturas_cobradas' => 0];
$stmt = $conn->prepare("SELECT * FROM datos_financieros WHERE anio = ? AND mes = ? AND empresa_id = ?");
$stmt->bind_param("iii", $anio_anterior, $mes_anterior, $empresa_id);
$stmt->execute();
$financiero_anterior = $stmt->get_result()->fetch_assoc() ?? ['facturacion_cobrada' => 0, 'facturas_cobradas' => 0];
$stmt = $conn->prepare("SELECT * FROM metas_generales WHERE anio = ? AND mes = ? AND empresa_id = ?");
$stmt->bind_param("iii", $anio, $mes, $empresa_id);
$stmt->execute();
$metas = $stmt->get_result()->fetch_assoc() ?? ['meta_instalaciones' => 0, 'meta_clientes' => 0];
$stmt = $conn->prepare("SELECT SUM(instalaciones) as total FROM rendimiento_sedes WHERE anio = ? AND mes = ? AND empresa_id = ?");
$stmt->bind_param("iii", $anio, $mes, $empresa_id);
$stmt->execute();
$total_instalaciones = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt = $conn->prepare("SELECT s.nombre, rs.instalaciones FROM rendimiento_sedes rs JOIN sedes s ON rs.sede_id = s.id WHERE rs.anio = ? AND rs.mes = ? AND rs.empresa_id = ? ORDER BY rs.instalaciones DESC");
$stmt->bind_param("iii", $anio, $mes, $empresa_id);
$stmt->execute();
$sedes_rendimiento = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt = $conn->prepare("SELECT a.nombre, ra.cierres, ra.meta_cierres FROM rendimiento_agentes ra JOIN agentes a ON ra.agente_id = a.id WHERE ra.anio = ? AND ra.mes = ? AND ra.empresa_id = ?");
$stmt->bind_param("iii", $anio, $mes, $empresa_id);
$stmt->execute();
$agentes_rendimiento = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt = $conn->prepare("SELECT c.nombre, rc.cierres, rc.meta_cierres FROM rendimiento_closers rc JOIN closers c ON rc.closer_id = c.id WHERE rc.anio = ? AND rc.mes = ? AND rc.empresa_id = ?");
$stmt->bind_param("iii", $anio, $mes, $empresa_id);
$stmt->execute();
$closers_rendimiento = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- FUNCIONES DE ANÁLISIS Y FORMATO ---
function get_tendencia($actual, $anterior, $es_moneda = false) {
    if ($anterior == 0) {
        return $actual > 0 ? '<span class="badge bg-success-subtle text-success-emphasis">Nuevo</span>' : '';
    }
    $cambio = (($actual - $anterior) / $anterior) * 100;
    $valor_formateado = $es_moneda ? '$' . number_format(abs($actual - $anterior), 2) : number_format(abs($actual - $anterior));

    if ($cambio > 2) {
        return sprintf('<span class="badge bg-success-subtle text-success-emphasis">↑ %s (%.1f%%)</span>', $valor_formateado, $cambio);
    } elseif ($cambio < -2) {
        return sprintf('<span class="badge bg-danger-subtle text-danger-emphasis">↓ %s (%.1f%%)</span>', $valor_formateado, $cambio);
    } else {
        return '<span class="badge bg-secondary-subtle text-secondary-emphasis">Estable</span>';
    }
}

// --- CÁLCULOS CLAVE ---
$arpu_actual = ($financiero_actual['facturas_cobradas'] > 0) ? ($financiero_actual['facturacion_cobrada'] / $financiero_actual['facturas_cobradas']) : 0;
$arpu_anterior = ($financiero_anterior['facturas_cobradas'] > 0) ? ($financiero_anterior['facturacion_cobrada'] / $financiero_anterior['facturas_cobradas']) : 0;
$cumplimiento_instalaciones = ($metas['meta_instalaciones'] > 0) ? ($total_instalaciones / $metas['meta_instalaciones']) * 100 : 0;

?>

<div class="p-3">
    <h4 class="text-primary"><i class="fas fa-file-invoice me-2"></i>Puntos Clave del Mes</h4>
    <ul class="list-group list-group-flush mb-4">
        <li class="list-group-item d-flex justify-content-between align-items-center">Facturación Total: 
            <strong>$<?php echo number_format($financiero_actual['facturacion_cobrada'], 2); ?></strong>
            <?php echo get_tendencia($financiero_actual['facturacion_cobrada'], $financiero_anterior['facturacion_cobrada'], true); ?>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">ARPU (Ingreso Promedio por Usuario):
            <strong>$<?php echo number_format($arpu_actual, 2); ?></strong>
            <?php echo get_tendencia($arpu_actual, $arpu_anterior, true); ?>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">Cumplimiento de Instalaciones:
            <strong><?php echo $total_instalaciones; ?> / <?php echo $metas['meta_instalaciones']; ?></strong>
            <span class="badge bg-info-subtle text-info-emphasis"><?php echo round($cumplimiento_instalaciones); ?>%</span>
        </li>
    </ul>

    <h4 class="text-primary"><i class="fas fa-chart-line me-2"></i>Análisis de Rendimiento</h4>
    <div class="row">
        <div class="col-md-6">
            <h5><i class="fas fa-building me-2"></i>Sedes</h5>
            <?php if (!empty($sedes_rendimiento)): 
                $sede_lider = $sedes_rendimiento[0];
                $sede_rezagada = end($sedes_rendimiento);
            ?>
                <p>La sede líder este mes fue <strong><?php echo htmlspecialchars($sede_lider['nombre']); ?></strong> con <strong><?php echo $sede_lider['instalaciones']; ?></strong> instalaciones, aportando un <strong><?php echo ($total_instalaciones > 0) ? round(($sede_lider['instalaciones'] / $total_instalaciones) * 100) : 0; ?>%</strong> del total.</p>
                <?php if (count($sedes_rendimiento) > 1): ?>
                <p>La sede con menor rendimiento fue <strong><?php echo htmlspecialchars($sede_rezagada['nombre']); ?></strong> con <strong><?php echo $sede_rezagada['instalaciones']; ?></strong> instalaciones.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>No hay datos de rendimiento de sedes para este periodo.</p>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <h5><i class="fas fa-users me-2"></i>Fuerza de Ventas</h5>
            <?php 
            $agente_lider = null;
            $agentes_bajo_meta = [];
            foreach ($agentes_rendimiento as $agente) {
                $cumplimiento = ($agente['meta_cierres'] > 0) ? ($agente['cierres'] / $agente['meta_cierres']) * 100 : 0;
                if ($agente_lider === null || $cumplimiento > $agente_lider['cumplimiento']) {
                    $agente_lider = ['nombre' => $agente['nombre'], 'cumplimiento' => $cumplimiento];
                }
                if ($cumplimiento < 100) {
                    $agentes_bajo_meta[] = $agente['nombre'];
                }
            }
            if ($agente_lider): ?>
                <p>El agente con mejor cumplimiento de meta fue <strong><?php echo htmlspecialchars($agente_lider['nombre']); ?></strong>, alcanzando un <strong><?php echo round($agente_lider['cumplimiento']); ?>%</strong> de su objetivo.</p>
            <?php endif; ?>
            <?php if (!empty($agentes_bajo_meta)): ?>
                <p><strong><?php echo count($agentes_bajo_meta); ?></strong> agente(s) no alcanzaron su meta de cierres: <?php echo htmlspecialchars(implode(', ', $agentes_bajo_meta)); ?>.</p>
            <?php else: ?>
                <p>¡Felicidades! Todos los agentes alcanzaron su meta de cierres este mes.</p>
            <?php endif; ?>
        </div>
    </div>

    <h4 class="text-primary mt-4"><i class="fas fa-lightbulb me-2"></i>Recomendaciones Estratégicas</h4>
    <ul class="list-group list-group-flush">
        <?php if ($cumplimiento_instalaciones < 85): ?>
            <li class="list-group-item list-group-item-warning">El cumplimiento general de instalaciones está por debajo del 85%. Se recomienda revisar los procesos y recursos de las sedes con menor rendimiento.</li>
        <?php endif; ?>
        <?php if ($arpu_actual < $arpu_anterior): ?>
            <li class="list-group-item list-group-item-warning">El ARPU ha disminuido. Analizar la posibilidad de lanzar campañas de up-selling o revisar la estructura de precios de los planes.</li>
        <?php endif; ?>
        <?php if (!empty($sedes_rendimiento) && count($sedes_rendimiento) > 1 && ($sede_lider['instalaciones'] > $sede_rezagada['instalaciones'] * 2)): ?>
            <li class="list-group-item list-group-item-info">Analizar las estrategias y buenas prácticas de la sede <strong><?php echo $sede_lider['nombre']; ?></strong> para replicarlas en <strong><?php echo $sede_rezagada['nombre']; ?></strong>.</li>
        <?php endif; ?>
        <?php if (!empty($agentes_bajo_meta)): ?>
            <li class="list-group-item list-group-item-info">Programar una reunión de seguimiento con los agentes que no alcanzaron la meta para identificar obstáculos y ofrecer apoyo.</li>
        <?php endif; ?>
        <li class="list-group-item">Continuar monitoreando los KPIs clave para asegurar una tendencia positiva en los próximos meses.</li>
    </ul>
</div>
