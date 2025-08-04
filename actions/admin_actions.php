<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once '../db.php';

// 1. Verificación de seguridad básica
// Comprobar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Comprobar que el usuario ha iniciado sesión y es superusuario
if (!isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// 2. Verificación del token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Error de validación de seguridad (CSRF).']);
    exit;
}

// 3. Procesamiento de la acción solicitada
$action = $_POST['action'] ?? '';

// Función para enviar una respuesta JSON y terminar el script
function json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

switch ($action) {
    // --- ACCIONES DE EMPRESA ---
    case 'get_companies':
        try {
            $stmt = $conn->prepare("SELECT id, nombre FROM empresas ORDER BY nombre");
            $stmt->execute();
            $result = $stmt->get_result();
            $companies = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'Empresas obtenidas con éxito.', $companies);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener empresas: ' . $e->getMessage());
        }
        break;

    case 'create_company':
        $nombre = $_POST['nombre'] ?? '';
        if (empty($nombre)) {
            json_response(false, 'El nombre de la empresa es obligatorio.');
        }
        $stmt = $conn->prepare("INSERT INTO empresas (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        if ($stmt->execute()) {
            json_response(true, 'Empresa creada con éxito.');
        } else {
            json_response(false, 'Error al crear la empresa.');
        }
        break;

    // --- ACCIONES DE USUARIO ---
    case 'get_users':
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT u.id, u.username, u.is_superuser FROM usuarios u JOIN usuario_empresa ue ON u.id = ue.usuario_id WHERE ue.empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'Usuarios obtenidos', $users);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener usuarios: ' . $e->getMessage());
        }
        break;

    case 'get_user':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT id, username, is_superuser FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if ($user) {
            json_response(true, 'Usuario obtenido', $user);
        } else {
            json_response(false, 'Usuario no encontrado.');
        }
        break;

    case 'create_user':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $is_superuser = isset($_POST['is_superuser']) ? 1 : 0;
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if (empty($username) || empty($password) || $empresa_id === 0) {
            json_response(false, 'Nombre de usuario, contraseña y empresa son obligatorios.');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password, is_superuser) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $username, $hashed_password, $is_superuser);
            $stmt->execute();
            $user_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO usuario_empresa (usuario_id, empresa_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $empresa_id);
            $stmt->execute();

            $conn->commit();
            json_response(true, 'Usuario creado con éxito.');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            json_response(false, 'Error al crear el usuario: ' . $e->getMessage());
        }
        break;

    case 'update_user':
        $id = (int)($_POST['id'] ?? 0);
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $is_superuser = isset($_POST['is_superuser']) ? 1 : 0;

        if ($id === 0 || empty($username)) {
            json_response(false, 'ID de usuario y nombre de usuario son obligatorios.');
        }

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET username = ?, password = ?, is_superuser = ? WHERE id = ?");
            $stmt->bind_param("ssii", $username, $hashed_password, $is_superuser, $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET username = ?, is_superuser = ? WHERE id = ?");
            $stmt->bind_param("sii", $username, $is_superuser, $id);
        }

        if ($stmt->execute()) {
            json_response(true, 'Usuario actualizado con éxito.');
        } else {
            json_response(false, 'Error al actualizar el usuario.');
        }
        break;

    case 'delete_user':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            json_response(false, 'ID de usuario no proporcionado.');
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM usuario_empresa WHERE usuario_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $conn->commit();
            json_response(true, 'Usuario eliminado con éxito.');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            json_response(false, 'Error al eliminar el usuario: ' . $e->getMessage());
        }
        break;

    // --- ACCIONES DE AGENTES ---
    case 'get_agents':
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT a.id, a.nombre, a.sede_id, s.nombre AS sede_nombre, a.empresa_id, e.nombre AS empresa_nombre FROM agentes a LEFT JOIN sedes s ON a.sede_id = s.id LEFT JOIN empresas e ON a.empresa_id = e.id WHERE a.empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $agents = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'Agentes obtenidos', $agents);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener agentes: ' . $e->getMessage());
        }
        break;

    case 'get_agent':
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT id, nombre, sede_id, empresa_id FROM agentes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $agent = $result->fetch_assoc();
            if ($agent) {
                json_response(true, 'Agente obtenido', $agent);
            } else {
                json_response(false, 'Agente no encontrado.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener agente: ' . $e->getMessage());
        }
        break;

    case 'create_agent':
        $nombre = $_POST['nombre'] ?? '';
        $sede_id = (int)($_POST['sede_id'] ?? 0);
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if (empty($nombre) || $sede_id === 0 || $empresa_id === 0) {
            json_response(false, 'Nombre del agente, sede y empresa son obligatorios.');
        }

        try {
            $stmt = $conn->prepare("INSERT INTO agentes (nombre, sede_id, empresa_id) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $nombre, $sede_id, $empresa_id);
            if ($stmt->execute()) {
                json_response(true, 'Agente creado con éxito.');
            } else {
                json_response(false, 'Error al crear el agente.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al crear el agente: ' . $e->getMessage());
        }
        break;

    case 'update_agent':
        $id = (int)($_POST['id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $sede_id = (int)($_POST['sede_id'] ?? 0);
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if ($id === 0 || empty($nombre) || $sede_id === 0 || $empresa_id === 0) {
            json_response(false, 'ID de agente, nombre, sede y empresa son obligatorios.');
        }

        try {
            $stmt = $conn->prepare("UPDATE agentes SET nombre = ?, sede_id = ?, empresa_id = ? WHERE id = ?");
            $stmt->bind_param("siii", $nombre, $sede_id, $empresa_id, $id);
            if ($stmt->execute()) {
                json_response(true, 'Agente actualizado con éxito.');
            } else {
                json_response(false, 'Error al actualizar el agente.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al actualizar el agente: ' . $e->getMessage());
        }
        break;

    case 'delete_agent':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            json_response(false, 'ID de agente no proporcionado.');
        }

        try {
            $stmt = $conn->prepare("DELETE FROM agentes WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                json_response(true, 'Agente eliminado con éxito.');
            } else {
                json_response(false, 'Error al eliminar el agente.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al eliminar el agente: ' . $e->getMessage());
        }
        break;

    // --- ACCIONES DE CLOSERS ---
    case 'get_closers':
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT c.id, c.nombre, c.empresa_id, e.nombre AS empresa_nombre FROM closers c LEFT JOIN empresas e ON c.empresa_id = e.id WHERE c.empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $closers = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'Closers obtenidos', $closers);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener closers: ' . $e->getMessage());
        }
        break;

    case 'get_closer':
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT id, nombre, empresa_id FROM closers WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $closer = $result->fetch_assoc();
            if ($closer) {
                json_response(true, 'Closer obtenido', $closer);
            } else {
                json_response(false, 'Closer no encontrado.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener closer: ' . $e->getMessage());
        }
        break;

    case 'create_closer':
        $nombre = $_POST['nombre'] ?? '';
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if (empty($nombre) || $empresa_id === 0) {
            json_response(false, 'Nombre del closer y empresa son obligatorios.');
        }

        try {
            $stmt = $conn->prepare("INSERT INTO closers (nombre, empresa_id) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $empresa_id);
            if ($stmt->execute()) {
                json_response(true, 'Closer creado con éxito.');
            } else {
                json_response(false, 'Error al crear el closer.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al crear el closer: ' . $e->getMessage());
        }
        break;

    case 'update_closer':
        $id = (int)($_POST['id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if ($id === 0 || empty($nombre) || $empresa_id === 0) {
            json_response(false, 'ID de closer, nombre y empresa son obligatorios.');
        }

        try {
            $stmt = $conn->prepare("UPDATE closers SET nombre = ?, empresa_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $nombre, $empresa_id, $id);
            if ($stmt->execute()) {
                json_response(true, 'Closer actualizado con éxito.');
            } else {
                json_response(false, 'Error al actualizar el closer.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al actualizar el closer: ' . $e->getMessage());
        }
        break;

    case 'delete_closer':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            json_response(false, 'ID de closer no proporcionado.');
        }

        try {
            $stmt = $conn->prepare("DELETE FROM closers WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                json_response(true, 'Closer eliminado con éxito.');
            } else {
                json_response(false, 'Error al eliminar el closer.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al eliminar el closer: ' . $e->getMessage());
        }
        break;

    // --- ACCIONES DE SEDES ---
    case 'get_locations':
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT s.id, s.nombre, s.empresa_id, e.nombre AS empresa_nombre FROM sedes s LEFT JOIN empresas e ON s.empresa_id = e.id WHERE s.empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $locations = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'Sedes obtenidas', $locations);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener sedes: ' . $e->getMessage());
        }
        break;

    case 'get_location':
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT id, nombre, empresa_id FROM sedes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $location = $result->fetch_assoc();
            if ($location) {
                json_response(true, 'Sede obtenida', $location);
            } else {
                json_response(false, 'Sede no encontrada.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener sede: ' . $e->getMessage());
        }
        break;

    case 'create_location':
        $nombre = $_POST['nombre'] ?? '';
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if (empty($nombre) || $empresa_id === 0) {
            json_response(false, 'Nombre de la sede y empresa son obligatorios.');
        }

        try {
            $stmt = $conn->prepare("INSERT INTO sedes (nombre, empresa_id) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $empresa_id);
            if ($stmt->execute()) {
                json_response(true, 'Sede creada con éxito.');
            } else {
                json_response(false, 'Error al crear la sede.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al crear la sede: ' . $e->getMessage());
        }
        break;

    case 'update_location':
        $id = (int)($_POST['id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if ($id === 0 || empty($nombre) || $empresa_id === 0) {
            json_response(false, 'ID de sede, nombre y empresa son obligatorios.');
        }

        try {
            $stmt = $conn->prepare("UPDATE sedes SET nombre = ?, empresa_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $nombre, $empresa_id, $id);
            if ($stmt->execute()) {
                json_response(true, 'Sede actualizada con éxito.');
            } else {
                json_response(false, 'Error al actualizar la sede.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al actualizar la sede: ' . $e->getMessage());
        }
        break;

    case 'delete_location':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            json_response(false, 'ID de sede no proporcionado.');
        }

        try {
            $stmt = $conn->prepare("DELETE FROM sedes WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                json_response(true, 'Sede eliminada con éxito.');
            } else {
                json_response(false, 'Error al eliminar la sede.');
            }
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al eliminar la sede: ' . $e->getMessage());
        }
        break;

    case 'get_locations_by_company':
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);
        if ($empresa_id === 0) {
            json_response(false, 'ID de empresa no proporcionado.');
        }
        try {
            $stmt = $conn->prepare("SELECT id, nombre FROM sedes WHERE empresa_id = ? ORDER BY nombre");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $locations = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'Sedes obtenidas con éxito.', $locations);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener sedes por empresa: ' . $e->getMessage());
        }
        break;

    // --- ACCIONES DE KPI ---
    case 'get_kpis':
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT id, nombre, descripcion FROM kpis WHERE empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $kpis = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, 'KPIs obtenidos', $kpis);
        } catch (mysqli_sql_exception $e) {
            json_response(false, 'Error al obtener KPIs: ' . $e->getMessage());
        }
        break;

    case 'get_kpi':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT id, nombre, descripcion FROM kpis WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $kpi = $result->fetch_assoc();
        if ($kpi) {
            json_response(true, 'KPI obtenido', $kpi);
        } else {
            json_response(false, 'KPI no encontrado.');
        }
        break;

    case 'create_kpi':
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $empresa_id = (int)($_POST['empresa_id'] ?? 0);

        if (empty($nombre) || $empresa_id === 0) {
            json_response(false, 'Nombre del KPI y empresa son obligatorios.');
        }

        $stmt = $conn->prepare("INSERT INTO kpis (nombre, descripcion, empresa_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nombre, $descripcion, $empresa_id);
        if ($stmt->execute()) {
            json_response(true, 'KPI creado con éxito.');
        } else {
            json_response(false, 'Error al crear el KPI.');
        }
        break;

    case 'update_kpi':
        $id = (int)($_POST['id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';

        if ($id === 0 || empty($nombre)) {
            json_response(false, 'ID de KPI y nombre son obligatorios.');
        }

        $stmt = $conn->prepare("UPDATE kpis SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
        if ($stmt->execute()) {
            json_response(true, 'KPI actualizado con éxito.');
        } else {
            json_response(false, 'Error al actualizar el KPI.');
        }
        break;

    case 'delete_kpi':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            json_response(false, 'ID de KPI no proporcionado.');
        }

        $stmt = $conn->prepare("DELETE FROM kpis WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            json_response(true, 'KPI eliminado con éxito.');
        } else {
            json_response(false, 'Error al eliminar el KPI.');
        }
        break;

    default:
        json_response(false, 'Acción no reconocida.');
        break;
}