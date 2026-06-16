<?php
header('Content-Type: text/html; charset=utf-8');
require 'conexion.php';

echo "<h1>Estructura de la Base de Datos: empleados_ntn</h1>";

// Listar todas las tablas
$result = $conexion->query("SHOW TABLES");

echo "<h2>Tablas en la base de datos:</h2>";
echo "<ul>";
while ($row = $result->fetch_array()) {
    $tabla = $row[0];
    echo "<li><strong>$tabla</strong></li>";
}
echo "</ul>";

// Mostrar estructura de cada tabla
$result = $conexion->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tabla = $row[0];
    
    echo "<hr>";
    echo "<h2>Tabla: $tabla</h2>";
    
    // Mostrar columnas
    $columns = $conexion->query("DESCRIBE $tabla");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar registros
    $count = $conexion->query("SELECT COUNT(*) as total FROM $tabla");
    $total = $count->fetch_assoc()['total'];
    echo "<p><strong>Total de registros:</strong> $total</p>";
}

$conexion->close();
?>
