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

echo "<h2>Análisis de Reportes Rechazados por Supervisor</h2>";
echo "<p>Verificando qué supervisores tienen reportes rechazados de sus trabajadores...</p><br>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID Supervisor</th>";
echo "<th>Nombre</th>";
echo "<th>Total Rechazados</th>";
echo "<th>Con Razón</th>";
echo "<th>Sin Razón</th>";
echo "</tr>";

$totalGeneral = 0;

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
    
    // Obtener IDs de trabajadores asignados
    $trabajadores = [
        2113 => [340,2148,197,63,128,201,81,155,187,165,123,210,264,300,302,312,325,330,332,399,412,267,417,447,452,468,494,495,501,505,508,511,512,517,519,544,562,571,574,596,597],
        181  => [167,170,185,54,157,158,246,65,173,177,209,248,253,263,283,289,290,285,291,331,364,361,385,407,418,424,433,458,502,507,518,524,526,531,534,575,587,586,589,598,599,2167],
        171  => [182,281,2189,156,198,166,122,145,183,221,226,329,304,252,315,411,431,462,487,488,500,506,530,545,553,563,567,588,593,594,595,604,200,232,164,172,207,227,606,605],
        73   => [2085,337,17,591,485,387,461,213,250,235,59,493,536,236,66,174,35,100,84,87,90,144,247],
        7    => [2067,456,509,537,546,415,580,581,590,442,479,520,266,146,130,99],
        14   => [129,2058,99,97,549,550,572,528,454,240,527,465,140,70,368,234],
        216  => [350,360,609],
        135  => [135,49,92,116,132,175,292,339,351,381,439,558,559,560,153,2257,292,2175,2292,2351,401,2153],
        249  => [147,420,584,607],
        62   => [38,2270,272,2106,2057,208,2103,390,2107,463,217,258,323,243,279,275,256,259,2159,274,271,190,341,513],
        45   => [612,613,601,514,41,579,334,273,457,480,450,389,421,423,557,110,435],
        32   => [316],
        44   => [451,602,379,610],
        9    => [51,112],
        244  => [12,104,215,382,405,406,515,516,614,616],
        133  => [13,105,150,282,428],
        71   => [52,108,124,160,585],
        27   => [4,61,76,238,319,320,335,349,378,419,486,611],
        1022 => [91,193,293,377,380],
    ];
    
    $ids = isset($trabajadores[$idSupervisor]) ? $trabajadores[$idSupervisor] : [];
    
    if (empty($ids)) continue;
    
    $idsStr = implode(',', $ids);
    
    // Contar reportes rechazados
    $sqlRechazados = "SELECT COUNT(DISTINCT r.id) as total
                      FROM reportes r
                      INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                      WHERE r.estadoSupervisor = 'rechazado'
                        AND CAST(rp.id_participante AS UNSIGNED) IN ($idsStr)";
    $resultRechazados = $conexion->query($sqlRechazados);
    $totalRechazados = $resultRechazados->fetch_assoc()['total'];
    
    // Contar rechazados con razón
    $sqlConRazon = "SELECT COUNT(DISTINCT r.id) as total
                    FROM reportes r
                    INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                    WHERE r.estadoSupervisor = 'rechazado'
                      AND r.razon_rechazo IS NOT NULL
                      AND r.razon_rechazo != ''
                      AND CAST(rp.id_participante AS UNSIGNED) IN ($idsStr)";
    $resultConRazon = $conexion->query($sqlConRazon);
    $conRazon = $resultConRazon->fetch_assoc()['total'];
    
    $sinRazon = $totalRechazados - $conRazon;
    
    // Solo mostrar supervisores con reportes rechazados
    if ($totalRechazados > 0) {
        $totalGeneral += $totalRechazados;
        $bgColor = '#ffebee';
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>$idSupervisor</strong></td>";
        echo "<td>$nombreReal</td>";
        echo "<td style='text-align: center;'><strong>$totalRechazados</strong></td>";
        echo "<td style='text-align: center; color: green;'>$conRazon</td>";
        echo "<td style='text-align: center; color: red;'>$sinRazon</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<br><h3>Resumen:</h3>";
echo "<p><strong>Total de reportes rechazados:</strong> $totalGeneral</p>";
echo "<p>Los supervisores listados arriba (con fondo rojo claro) tienen reportes rechazados de sus trabajadores.</p>";
echo "<p><span style='color: green;'>Verde</span> = Rechazados con razón especificada</p>";
echo "<p><span style='color: red;'>Rojo</span> = Rechazados sin razón especificada</p>";

// Mostrar detalle de reportes rechazados
echo "<br><hr><br>";
echo "<h3>Detalle de Reportes Rechazados:</h3>";

$sqlDetalle = "SELECT r.id, r.tema, r.fecha, r.estadoSupervisor, r.razon_rechazo,
                      rp.nombre as nombre_trabajador, rp.departamento
               FROM reportes r
               INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
               WHERE r.estadoSupervisor = 'rechazado'
               ORDER BY r.fecha DESC
               LIMIT 50";

$resultDetalle = $conexion->query($sqlDetalle);

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th>";
echo "<th>Tema</th>";
echo "<th>Fecha</th>";
echo "<th>Trabajador</th>";
echo "<th>Departamento</th>";
echo "<th>Razón de Rechazo</th>";
echo "</tr>";

while ($row = $resultDetalle->fetch_assoc()) {
    $razon = $row['razon_rechazo'] ? htmlspecialchars($row['razon_rechazo']) : '<em style="color: red;">Sin razón</em>';
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['tema']}</td>";
    echo "<td>{$row['fecha']}</td>";
    echo "<td>{$row['nombre_trabajador']}</td>";
    echo "<td>{$row['departamento']}</td>";
    echo "<td>$razon</td>";
    echo "</tr>";
}

echo "</table>";
?>
