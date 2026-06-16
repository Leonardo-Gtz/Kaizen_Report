<?php
require 'conexion.php';

// Verificar si la columna existe
$sql = "SHOW COLUMNS FROM reportes LIKE 'razon_rechazo'";
$result = $conexion->query($sql);

if ($result->num_rows == 0) {
    // La columna no existe, agregarla
    $sqlAdd = "ALTER TABLE reportes ADD COLUMN razon_rechazo TEXT NULL AFTER estadoSupervisor";
    
    if ($conexion->query($sqlAdd)) {
        echo "✓ Campo 'razon_rechazo' agregado exitosamente";
    } else {
        echo "✗ Error al agregar campo: " . $conexion->error;
    }
} else {
    echo "✓ El campo 'razon_rechazo' ya existe en la tabla";
}

// Mostrar estructura actual
echo "<br><br><h3>Estructura actual de la tabla reportes:</h3>";
$sql2 = "DESCRIBE reportes";
$result2 = $conexion->query($sql2);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
while ($row = $result2->fetch_assoc()) {
    $highlight = $row['Field'] == 'razon_rechazo' ? 'background: #90ee90;' : '';
    echo "<tr style='$highlight'>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
