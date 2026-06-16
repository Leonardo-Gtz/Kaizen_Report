<?php
require 'conexion.php';

// Ver estructura de la tabla
$sql = "DESCRIBE reportes";
$result = $conexion->query($sql);

echo "<h3>Estructura de la tabla 'reportes':</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Ver un reporte de ejemplo
$sql2 = "SELECT * FROM reportes LIMIT 1";
$result2 = $conexion->query($sql2);
if ($result2 && $result2->num_rows > 0) {
    echo "<br><h3>Ejemplo de un reporte:</h3>";
    $ejemplo = $result2->fetch_assoc();
    echo "<pre>" . print_r($ejemplo, true) . "</pre>";
}
?>
