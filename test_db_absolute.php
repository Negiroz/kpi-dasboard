<?php
echo "<h2>Prueba de Conexión a Base de Datos (Ruta Absoluta)</h2>";
echo "<p>Iniciando prueba...</p>";

// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivo de conexión usando una ruta absoluta
$db_path = '/var/www/html/kpi-dashboard/db.php';

if (file_exists($db_path)) {
    echo "<p>Archivo db.php encontrado en la ruta absoluta.</p>";
    include $db_path;
    
    if (isset($conn) && $conn->ping()) {
        echo "<p style='color: green;'>✓ Conexión exitosa a la base de datos.</p>";
        echo "<p>Base de datos: " . DB_NAME . "</p>";
        $conn->close();
    } else {
        echo "<p style='color: red;'>✗ Error al conectar a la base de datos.</p>";
        if (isset($conn)) {
            echo "<p>Error: " . $conn->connect_error . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ Archivo db.php no encontrado en la ruta absoluta especificada.</p>";
}

echo "<p>Prueba completada.</p>";
?>