<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Validación CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Error de seguridad: Token CSRF inválido.']);
    exit;
}

$mes = filter_var($_POST['mes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
$anio = filter_var($_POST['anio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]); // Rango de años razonable
$form_type = $_POST['form_type'] ?? null;
$empresa_id = $_SESSION['empresa_id'];

if ($mes === false || $anio === false || !$form_type) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan o son inválidos los parámetros esenciales (mes, año, tipo de formulario).']);
    exit;
}

$success = false;

switch ($form_type) {
    case 'financiero':
        $facturacion = filter_var($_POST['facturacion_cobrada'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        $facturas = filter_var($_POST['facturas_cobradas'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $meta_instalaciones = filter_var($_POST['meta_instalaciones'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $meta_clientes = filter_var($_POST['meta_clientes'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($facturacion === false || $facturas === false || $meta_instalaciones === false || $meta_clientes === false) {
            echo json_encode(['status' => 'error', 'message' => 'Datos financieros o de metas inválidos.']);
            exit;
        }

        // Guardar datos financieros
        $stmt_financiero = $conn->prepare("INSERT INTO datos_financieros (mes, anio, facturacion_cobrada, facturas_cobradas, empresa_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE facturacion_cobrada = VALUES(facturacion_cobrada), facturas_cobradas = VALUES(facturas_cobradas)");
        if ($stmt_financiero === false) { echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta financiera: ' . $conn->error]); exit; }
        if (!$stmt_financiero->bind_param("iiddi", $mes, $anio, $facturacion, $facturas, $empresa_id)) { echo json_encode(['status' => 'error', 'message' => 'Error al enlazar parámetros financieros: ' . $stmt_financiero->error]); exit; }
        if (!$stmt_financiero->execute()) { echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta financiera: ' . $stmt_financiero->error]); exit; }

        // Guardar metas generales
        $stmt_metas = $conn->prepare("INSERT INTO metas_generales (mes, anio, meta_instalaciones, meta_clientes, empresa_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE meta_instalaciones = VALUES(meta_instalaciones), meta_clientes = VALUES(meta_clientes)");
        if ($stmt_metas === false) { echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de metas: ' . $conn->error]); exit; }
        if (!$stmt_metas->bind_param("iiiii", $mes, $anio, $meta_instalaciones, $meta_clientes, $empresa_id)) { echo json_encode(['status' => 'error', 'message' => 'Error al enlazar parámetros de metas: ' . $stmt_metas->error]); exit; }
        $success = $stmt_metas->execute();
        if (!$success) { echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta de metas: ' . $stmt_metas->error]); exit; }
        break;

    case 'sedes':
        if (isset($_POST['sedes']) && is_array($_POST['sedes'])) {
            foreach ($_POST['sedes'] as $sede_id => $instalaciones) {
                $sede_id = filter_var($sede_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $instalaciones = filter_var($instalaciones, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

                if ($sede_id === false || $instalaciones === false) {
                    echo json_encode(['status' => 'error', 'message' => 'Datos de sede inválidos.']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO rendimiento_sedes (sede_id, mes, anio, instalaciones, empresa_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE instalaciones = VALUES(instalaciones)");
                if ($stmt === false) { echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de sedes: ' . $conn->error]); exit; }
                if (!$stmt->bind_param("iiiii", $sede_id, $mes, $anio, $instalaciones, $empresa_id)) { echo json_encode(['status' => 'error', 'message' => 'Error al enlazar parámetros de sedes: ' . $stmt->error]); exit; }
                if (!$stmt->execute()) { echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta de sedes: ' . $stmt->error]); exit; }
            }
            $success = true;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Datos de sedes no proporcionados o inválidos.']);
            exit;
        }
        break;

    case 'agentes':
        if (isset($_POST['agentes']) && is_array($_POST['agentes'])) {
            foreach ($_POST['agentes'] as $agente_id => $data) {
                $agente_id = filter_var($agente_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $cierres = filter_var($data['cierres'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                $prospectos = filter_var($data['prospectos'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                $meta_cierres = filter_var($data['meta_cierres'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                $meta_prospectos = filter_var($data['meta_prospectos'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

                if ($agente_id === false || $cierres === false || $prospectos === false || $meta_cierres === false || $meta_prospectos === false) {
                    echo json_encode(['status' => 'error', 'message' => 'Datos de agente inválidos.']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO rendimiento_agentes (agente_id, mes, anio, cierres, prospectos, meta_cierres, meta_prospectos, empresa_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cierres = VALUES(cierres), prospectos = VALUES(prospectos), meta_cierres = VALUES(meta_cierres), meta_prospectos = VALUES(meta_prospectos)");
                if ($stmt === false) { echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de agentes: ' . $conn->error]); exit; }
                if (!$stmt->bind_param("iiiiiiii", $agente_id, $mes, $anio, $cierres, $prospectos, $meta_cierres, $meta_prospectos, $empresa_id)) { echo json_encode(['status' => 'error', 'message' => 'Error al enlazar parámetros de agentes: ' . $stmt->error]); exit; }
                if (!$stmt->execute()) { echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta de agentes: ' . $stmt->error]); exit; }
            }
            $success = true;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Datos de agentes no proporcionados o inválidos.']);
            exit;
        }
        break;

    case 'closers':
        if (isset($_POST['closers']) && is_array($_POST['closers'])) {
            foreach ($_POST['closers'] as $closer_id => $data) {
                $closer_id = filter_var($closer_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $cierres = filter_var($data['cierres'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                $meta_cierres = filter_var($data['meta_cierres'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

                if ($closer_id === false || $cierres === false || $meta_cierres === false) {
                    echo json_encode(['status' => 'error', 'message' => 'Datos de closer inválidos.']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO rendimiento_closers (closer_id, mes, anio, cierres, meta_cierres, empresa_id) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cierres = VALUES(cierres), meta_cierres = VALUES(meta_cierres)");
                if ($stmt === false) { echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de closers: ' . $conn->error]); exit; }
                if (!$stmt->bind_param("iiiiii", $closer_id, $mes, $anio, $cierres, $meta_cierres, $empresa_id)) { echo json_encode(['status' => 'error', 'message' => 'Error al enlazar parámetros de closers: ' . $stmt->error]); exit; }
                if (!$stmt->execute()) { echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta de closers: ' . $stmt->error]); exit; }
            }
            $success = true;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Datos de closers no proporcionados o inválidos.']);
            exit;
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