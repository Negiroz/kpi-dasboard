<?php
echo "<h2>Prueba de Conexión a Base de Datos</h2>";
echo "<p>Iniciando prueba...</p>";

// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivo de conexión
if (file_exists('db.php')) {
    echo "<p>Archivo db.php encontrado</p>";
    include 'db.php';
    
    echo "<p style='color: green;'>✓ Conexión exitosa a la base de datos</p>";
    echo "<p>Base de datos: kpi_dashboard</p>";
} else {
    echo "<p style='color: red;'>✗ Archivo db.php no encontrado</p>";
}

echo "<p>Prueba completada</p>";
?>
