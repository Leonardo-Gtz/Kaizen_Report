<?php
require 'conexion.php';

$idSupervisor = 27;
$ids = [4,61,76,238,319,320,335,349,378,419,486,611];
$idsStr = implode(',', $ids);

echo "<h2>Debug Reportes Rechazados - Supervisor 27</h2>";

// Query 1: Contar rechazados (como en dashboard)
$sql1 = "SELECT COUNT(DISTINCT r.id) as total
         FROM reportes r 
         INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
         WHERE r.estadoSupervisor = 'rechazado'
           AND CAST(rp.id_participante AS UNSIGNED) IN ($idsStr)";

$result1 = $conexion->query($sql1);
$total = $result1->fetch_assoc()['total'];

echo "<p><strong>Total según dashboard:</strong> $total</p>";

// Query 2: Listar reportes rechazados (como en la API)
$sql2 = "SELECT DISTINCT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha,
                r.razon_rechazo, rp.nombre AS nombre_trabajador
         FROM reportes r
         INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
         WHERE r.estadoSupervisor = 'rechazado'
           AND CAST(rp.id_participante AS UNSIGNED) IN ($idsStr)
         ORDER BY r.fecha DESC";

$result2 = $conexion->query($sql2);

echo "<p><strong>Total según API:</strong> " . $result2->num_rows . "</p>";

echo "<h3>Reportes encontrados:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tema</th><th>Fecha</th><th>Trabajador</th><th>Razón</th></tr>";

while ($row = $result2->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['tema']}</td>";
    echo "<td>{$row['fecha']}</td>";
    echo "<td>{$row['nombre_trabajador']}</td>";
    echo "<td>" . ($row['razon_rechazo'] ?: 'Sin razón') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Query 3: Ver todos los rechazados sin filtro de participantes
$sql3 = "SELECT r.id, r.tema, r.fecha, r.estadoSupervisor,
                GROUP_CONCAT(DISTINCT rp.id_participante) as participantes
         FROM reportes r
         LEFT JOIN reporte_participantes rp ON rp.id_reporte = r.id
         WHERE r.estadoSupervisor = 'rechazado'
         GROUP BY r.id
         LIMIT 20";

$result3 = $conexion->query($sql3);

echo "<br><h3>Todos los reportes rechazados (primeros 20):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tema</th><th>Fecha</th><th>Participantes IDs</th></tr>";

while ($row = $result3->fetch_assoc()) {
    $highlight = '';
    $participantesArray = explode(',', $row['participantes']);
    foreach ($ids as $idTrabajador) {
        if (in_array($idTrabajador, $participantesArray)) {
            $highlight = 'background: #ffeb3b;';
            break;
        }
    }
    
    echo "<tr style='$highlight'>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['tema']}</td>";
    echo "<td>{$row['fecha']}</td>";
    echo "<td>{$row['participantes']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p><em>Los reportes en amarillo tienen participantes del supervisor 27</em></p>";
?>
