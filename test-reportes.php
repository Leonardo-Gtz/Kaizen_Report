<?php
session_start();
header('Content-Type: application/json');

require 'conexion.php';

// Verificar sesión
echo "Usuario en sesión: " . json_encode($_SESSION['usuario']) . "\n\n";

$grupos = [
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

$idSupervisor = intval($_SESSION['usuario']['id']);
$ids = isset($grupos[$idSupervisor]) ? $grupos[$idSupervisor] : [];

echo "ID Supervisor: $idSupervisor\n";
echo "IDs trabajadores asignados: " . json_encode($ids) . "\n\n";

if (empty($ids)) {
    echo "No hay trabajadores asignados a este supervisor\n";
    exit();
}

$idsStr = implode(',', $ids);

// Ver todos los reportes finalizados
$sql1 = "SELECT id, tema, estado, estadoSupervisor, id_usuario FROM reportes WHERE estado = 'finalizado' LIMIT 10";
$result1 = $conexion->query($sql1);
echo "Reportes finalizados (primeros 10):\n";
while ($row = $result1->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

echo "\n\n";

// Ver participantes de reportes
$sql2 = "SELECT id_reporte, id_participante FROM reporte_participantes LIMIT 20";
$result2 = $conexion->query($sql2);
echo "Participantes (primeros 20):\n";
while ($row = $result2->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

echo "\n\n";

// Probar la query completa
$sql3 = "SELECT DISTINCT r.id, r.tema, r.estado, r.estadoSupervisor, rp.id_participante
         FROM reportes r
         INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
         WHERE r.estado = 'finalizado'
           AND (r.estadoSupervisor IS NULL OR r.estadoSupervisor = 'pendiente')
           AND CAST(rp.id_participante AS UNSIGNED) IN ($idsStr)
         LIMIT 10";
$result3 = $conexion->query($sql3);
echo "Reportes que deberían aparecer:\n";
if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
} else {
    echo "No hay reportes que cumplan las condiciones\n";
}
?>
