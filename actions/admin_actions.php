<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? 0;
    $nombre = $_POST['nombre'] ?? '';
    $sede_id = $_POST['sede_id'] ?? 0;
    $tab = $_POST['tab'] ?? 'sedes'; // Capturar la pestaña activa

    $table_map = [
        'sede' => 'sedes',
        'agente' => 'agentes',
        'closer' => 'closers'
    ];

    if (!isset($table_map[$type])) {
        die("Tipo de entidad no válido.");
    }

    $table_name = $table_map[$type];

    switch ($action) {
        case 'create':
            if (!empty($nombre)) {
                if ($type === 'agente' && !empty($sede_id)) {
                    $stmt = $conn->prepare("INSERT INTO agentes (nombre, sede_id) VALUES (?, ?)");
                    $stmt->bind_param("si", $nombre, $sede_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO $table_name (nombre) VALUES (?)");
                    $stmt->bind_param("s", $nombre);
                }
                $stmt->execute();
            }
            break;

        case 'delete':
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM $table_name WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
            break;

        case 'update':
            if ($id > 0 && !empty($nombre)) {
                if ($type === 'agente' && !empty($sede_id)) {
                    $stmt = $conn->prepare("UPDATE agentes SET nombre = ?, sede_id = ? WHERE id = ?");
                    $stmt->bind_param("sii", $nombre, $sede_id, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE $table_name SET nombre = ? WHERE id = ?");
                    $stmt->bind_param("si", $nombre, $id);
                }
                $stmt->execute();
            }
            break;
    }

    // Redirigir de vuelta a la página de administración, manteniendo la pestaña activa
    header("Location: ../index.php?view=admin&tab=$tab");
    exit;
}
?>