<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$mes = $_POST['mes'] ?? null;
$anio = $_POST['anio'] ?? null;
$form_type = $_POST['form_type'] ?? null;

if (!$mes || !$anio || !$form_type) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros esenciales.']);
    exit;
}

$success = false;

switch ($form_type) {
    case 'financiero':
        // Guardar datos financieros
        $facturacion = $_POST['facturacion_cobrada'] ?? 0;
        $facturas = $_POST['facturas_cobradas'] ?? 0;
        $stmt_financiero = $conn->prepare("INSERT INTO datos_financieros (mes, anio, facturacion_cobrada, facturas_cobradas) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE facturacion_cobrada = VALUES(facturacion_cobrada), facturas_cobradas = VALUES(facturas_cobradas)");
        $stmt_financiero->bind_param("iidd", $mes, $anio, $facturacion, $facturas);
        $stmt_financiero->execute();

        // Guardar metas generales
        $meta_instalaciones = $_POST['meta_instalaciones'] ?? 0;
        $meta_clientes = $_POST['meta_clientes'] ?? 0;
        $stmt_metas = $conn->prepare("INSERT INTO metas_generales (mes, anio, meta_instalaciones, meta_clientes) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE meta_instalaciones = VALUES(meta_instalaciones), meta_clientes = VALUES(meta_clientes)");
        $stmt_metas->bind_param("iiii", $mes, $anio, $meta_instalaciones, $meta_clientes);
        $success = $stmt_metas->execute();
        break;

    case 'sedes':
        if (isset($_POST['sedes'])) {
            foreach ($_POST['sedes'] as $sede_id => $instalaciones) {
                $instalaciones = empty($instalaciones) ? 0 : $instalaciones;
                $stmt = $conn->prepare("INSERT INTO rendimiento_sedes (sede_id, mes, anio, instalaciones) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE instalaciones = VALUES(instalaciones)");
                $stmt->bind_param("iiii", $sede_id, $mes, $anio, $instalaciones);
                $stmt->execute();
            }
            $success = true;
        }
        break;

    case 'agentes':
        if (isset($_POST['agentes'])) {
            foreach ($_POST['agentes'] as $agente_id => $data) {
                $cierres = empty($data['cierres']) ? 0 : $data['cierres'];
                $prospectos = empty($data['prospectos']) ? 0 : $data['prospectos'];
                $meta_cierres = empty($data['meta_cierres']) ? 0 : $data['meta_cierres'];
                $meta_prospectos = empty($data['meta_prospectos']) ? 0 : $data['meta_prospectos'];
                $stmt = $conn->prepare("INSERT INTO rendimiento_agentes (agente_id, mes, anio, cierres, prospectos, meta_cierres, meta_prospectos) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cierres = VALUES(cierres), prospectos = VALUES(prospectos), meta_cierres = VALUES(meta_cierres), meta_prospectos = VALUES(meta_prospectos)");
                $stmt->bind_param("iiiiiii", $agente_id, $mes, $anio, $cierres, $prospectos, $meta_cierres, $meta_prospectos);
                $stmt->execute();
            }
            $success = true;
        }
        break;

    case 'closers':
        if (isset($_POST['closers'])) {
            foreach ($_POST['closers'] as $closer_id => $data) {
                $cierres = empty($data['cierres']) ? 0 : $data['cierres'];
                $meta_cierres = empty($data['meta_cierres']) ? 0 : $data['meta_cierres'];
                $stmt = $conn->prepare("INSERT INTO rendimiento_closers (closer_id, mes, anio, cierres, meta_cierres) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cierres = VALUES(cierres), meta_cierres = VALUES(meta_cierres)");
                $stmt->bind_param("iiiii", $closer_id, $mes, $anio, $cierres, $meta_cierres);
                $stmt->execute();
            }
            $success = true;
        }
        break;
}

if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'Datos guardados correctamente.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar la información.']);
}

$conn->close();
?>