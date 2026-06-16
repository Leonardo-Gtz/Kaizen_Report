<?php
require 'conexion.php';

$grupos = [
    2113 => 'Supervisor ID 2113',
    181  => 'Supervisor ID 181',
    171  => 'Supervisor ID 171',
    73   => 'Supervisor ID 73',
    7    => 'Supervisor ID 7',
    14   => 'Supervisor ID 14',
    216  => 'Supervisor ID 216',
    135  => 'Supervisor ID 135',
    249  => 'Supervisor ID 249',
    62   => 'Supervisor ID 62',
    45   => 'Supervisor ID 45',
    32   => 'Supervisor ID 32',
    44   => 'Supervisor ID 44',
    9    => 'Supervisor ID 9',
    244  => 'Supervisor ID 244',
    133  => 'Supervisor ID 133',
    71   => 'Supervisor ID 71',
    27   => 'Supervisor ID 27',
    1022 => 'Supervisor ID 1022',
];

echo "<h2>Análisis de Reportes por Supervisor</h2>";
echo "<p>Verificando qué supervisores tienen reportes propios (donde ellos son participantes)...</p><br>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID Supervisor</th>";
echo "<th>Nombre</th>";
echo "<th>Total Reportes</th>";
echo "<th>Pendientes</th>";
echo "<th>Aprobados</th>";
echo "<th>Rechazados</th>";
echo "</tr>";

foreach ($grupos as $idSupervisor => $nombre) {
    // Obtener nombre real del supervisor
    $sqlNombre = "SELECT FIrstName, LastName, SurName FROM bd_ntn WHERE CAST(EmpId AS UNSIGNED) = ?";
    $stmtNombre = $conexion->prepare($sqlNombre);
    $stmtNombre->bind_param('i', $idSupervisor);
    $stmtNombre->execute();
    $resultNombre = $stmtNombre->get_result();
    
    $nombreReal = $nombre;
    if ($resultNombre && $resultNombre->num_rows > 0) {
        $rowNombre = $resultNombre->fetch_assoc();
        $nombreReal = trim($rowNombre['FIrstName'] . ' ' . $rowNombre['LastName'] . ' ' . $rowNombre['SurName']);
    }
    
    // Contar reportes totales
    $sqlTotal = "SELECT COUNT(DISTINCT r.id) as total
                 FROM reportes r
                 INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                 WHERE CAST(rp.id_participante AS UNSIGNED) = ?
                   AND r.estado != 'borrador'";
    $stmtTotal = $conexion->prepare($sqlTotal);
    $stmtTotal->bind_param('i', $idSupervisor);
    $stmtTotal->execute();
    $total = $stmtTotal->get_result()->fetch_assoc()['total'];
    
    // Contar pendientes
    $sqlPend = "SELECT COUNT(DISTINCT r.id) as total
                FROM reportes r
                INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                WHERE CAST(rp.id_participante AS UNSIGNED) = ?
                  AND r.estado != 'borrador'
                  AND (r.estadoSupervisor IS NULL OR r.estadoSupervisor = 'pendiente')";
    $stmtPend = $conexion->prepare($sqlPend);
    $stmtPend->bind_param('i', $idSupervisor);
    $stmtPend->execute();
    $pendientes = $stmtPend->get_result()->fetch_assoc()['total'];
    
    // Contar aprobados
    $sqlAprob = "SELECT COUNT(DISTINCT r.id) as total
                 FROM reportes r
                 INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                 WHERE CAST(rp.id_participante AS UNSIGNED) = ?
                   AND r.estado != 'borrador'
                   AND r.estadoSupervisor = 'aprobado'";
    $stmtAprob = $conexion->prepare($sqlAprob);
    $stmtAprob->bind_param('i', $idSupervisor);
    $stmtAprob->execute();
    $aprobados = $stmtAprob->get_result()->fetch_assoc()['total'];
    
    // Contar rechazados
    $sqlRech = "SELECT COUNT(DISTINCT r.id) as total
                FROM reportes r
                INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                WHERE CAST(rp.id_participante AS UNSIGNED) = ?
                  AND r.estado != 'borrador'
                  AND r.estadoSupervisor = 'rechazado'";
    $stmtRech = $conexion->prepare($sqlRech);
    $stmtRech->bind_param('i', $idSupervisor);
    $stmtRech->execute();
    $rechazados = $stmtRech->get_result()->fetch_assoc()['total'];
    
    // Solo mostrar supervisores con reportes
    if ($total > 0) {
        $bgColor = $total > 0 ? '#e8f5e9' : '';
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>$idSupervisor</strong></td>";
        echo "<td>$nombreReal</td>";
        echo "<td style='text-align: center;'><strong>$total</strong></td>";
        echo "<td style='text-align: center;'>$pendientes</td>";
        echo "<td style='text-align: center;'>$aprobados</td>";
        echo "<td style='text-align: center;'>$rechazados</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<br><h3>Resumen:</h3>";
echo "<p>Los supervisores listados arriba (con fondo verde) tienen reportes propios y verán datos en la sección 'Mis Reportes'.</p>";
echo "<p>Los supervisores que NO aparecen en la lista no tienen reportes donde ellos sean participantes.</p>";
?>
