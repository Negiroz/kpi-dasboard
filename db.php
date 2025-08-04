<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'kpi_dashboard');

// Crear conexión
$conn = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    // No usar die() aquí, ya que podría generar HTML inesperado.
    // En su lugar, loguear el error y manejarlo de forma más elegante en la aplicación.
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    // Podrías redirigir a una página de error o mostrar un mensaje amigable.
    // Por ahora, simplemente salimos para evitar más errores.
    exit;
}

// Establecer el charset a utf8mb4
$conn->set_charset("utf8mb4");
?>