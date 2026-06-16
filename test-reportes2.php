<?php
session_start();
require 'conexion.php';

// Ver todos los reportes sin filtro de estado
$sql = "SELECT id, tema, estado, estadoSupervisor, estadoGerente, estadoRH, id_usuario, fecha 
        FROM reportes 
        ORDER BY id DESC 
        LIMIT 20";
$result = $conexion->query($sql);

echo "<h3>Todos los reportes (últimos 20):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tema</th><th>Estado</th><th>Estado Supervisor</th><th>Estado Gerente</th><th>Estado RH</th><th>ID Usuario</th><th>Fecha</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['tema']}</td>";
    echo "<td><strong>{$row['estado']}</strong></td>";
    echo "<td>{$row['estadoSupervisor']}</td>";
    echo "<td>{$row['estadoGerente']}</td>";
    echo "<td>{$row['estadoRH']}</td>";
    echo "<td>{$row['id_usuario']}</td>";
    echo "<td>{$row['fecha']}</td>";
    echo "</tr>";
}
echo "</table>";

// Ver estados únicos
$sql2 = "SELECT DISTINCT estado FROM reportes";
$result2 = $conexion->query($sql2);
echo "<br><h3>Estados disponibles en la tabla:</h3>";
while ($row = $result2->fetch_assoc()) {
    echo "- " . ($row['estado'] ?? 'NULL') . "<br>";
}

// Ver participantes
$sql3 = "SELECT rp.id_reporte, rp.id_participante, r.tema, r.estado 
         FROM reporte_participantes rp
         LEFT JOIN reportes r ON r.id = rp.id_reporte
         WHERE CAST(rp.id_participante AS UNSIGNED) IN (4,61,76,238,319,320,335,349,378,419,486,611)
         LIMIT 20";
$result3 = $conexion->query($sql3);
echo "<br><h3>Reportes de tus trabajadores:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID Reporte</th><th>ID Participante</th><th>Tema</th><th>Estado</th></tr>";
while ($row = $result3->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id_reporte']}</td>";
    echo "<td>{$row['id_participante']}</td>";
    echo "<td>{$row['tema']}</td>";
    echo "<td><strong>{$row['estado']}</strong></td>";
    echo "</tr>";
}
echo "</table>";
?>
