<?php
ini_set('memory_limit', '256M'); 
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

require_once 'conexion.php';

if (!$conexion) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}

$grupos = array(

    // CVJ
    2113 => array(
        340,2148,197,63,128,201,81,155,187,165,123,210,264,300,302,312,325,330,
        332,399,412,267,417,447,452,468,494,495,501,505,508,511,512,517,519,544,
        562,571,574,596,597
    ),
    181 => array(
        167,170,185,54,157,158,246,65,173,177,209,248,253,263,283,289,290,285,
        291,331,364,361,385,407,418,424,433,458,502,507,518,524,526,531,534,575,
        587,586,589,598,599,2167
    ),
    171 => array(
        182,281,2189,156,198,166,122,145,183,221,226,329,304,252,315,411,431,462,
        487,488,500,506,530,545,553,563,567,588,593,594,595,604,200,232,164,172,
        207,227,606,605
    ),

    // HUB
    73 => array(
        2085,337,17,591,485,387,461,213,250,235,59,493,536,236,66,174,35,100,84,87,90,144,247
    ),
    7 => array(
        2067,456,509,537,546,415,580,581,590,442,479,520,266,146,130,99
    ),
    14 => array(
        129,2058,99,97,549,550,572,528,454,240,527,465,140,70,368,234
    ),
    
    // Corporate 1023
    216 => array(
        350,360,609
    ),

    // QA
    135 => array(
        135,49,92,116,132,175,292,339,351,381,439,558,559,560,153,2257,292,2175,2292,2351,401,2153
    ),
    249 => array(
        147,420,584,607
    ), 

    // PC
    62 => array(
        38,2270,272,2106,2057,208,2103,390,2107,463,217,258,323,243,279,275,256,259,2159,274,271,190,341,513
    ),
    45 => array(
        612,613,601,514,41,579,334,273,457,480,450,389,421,423,557,110,435
    ),
    32 => array(
        316
    ),
    44 => array(
        451,602,379,610
    ), 

    // ENG
        9 => array(
        51,112
    ),
    244 => array(
        12,104,215,382,405,406,515,516,614,616
    ),
    133 => array(
        13,105,150,282,428
    ),
    71 => array(
        52,108,124,160,585
    ), 

    // HR
    27 => array(
        4,61,76,238,319,320,335,349,378,419,486,611
    ), 

    // FI
    1022 => array(
        91,193,293,377,380
    )
);

$idSupervisor = isset($_GET['idSupervisor']) ? intval($_GET['idSupervisor']) : 0;
$usuariosPermitidos = isset($grupos[$idSupervisor]) ? $grupos[$idSupervisor] : array();

$sql = "SELECT r.* FROM reportes r WHERE r.estado = 'finalizado' ORDER BY fecha DESC";
$result = $conexion->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en consulta: ' . $conexion->error]);
    exit;
}

$reportes = [];

while ($row = $result->fetch_assoc()) {
    $idReporte = $row['id'];

    // obtener participantes ordenados por id
    $sqlParticipantes = "SELECT id_participante, nombre, departamento FROM reporte_participantes WHERE id_reporte = $idReporte ORDER BY id ASC";
    $resParticipantes = $conexion->query($sqlParticipantes);

    $participantes = [];
    if ($resParticipantes) {
        while ($part = $resParticipantes->fetch_assoc()) {
            $participantes[] = $part;
        }
    }
    $row['participantes'] = $participantes;

    // Solo filtrar por primer participante si es supervisor
    if (!empty($usuariosPermitidos)) {
        $primerParticipanteId = $participantes[0]['id_participante'];
        if (!in_array(intval($primerParticipanteId), $usuariosPermitidos)) {
            continue; // salta este reporte
        }
    }

    // obtener evaluación si existe
    $sqlEvaluacion = "SELECT clasificacion, aspectos_evaluados FROM evaluaciones WHERE id_reporte = $idReporte ORDER BY fecha DESC LIMIT 1";
    $resEvaluacion = $conexion->query($sqlEvaluacion);
    if ($resEvaluacion && $resEvaluacion->num_rows > 0) {
        $evaluacion = $resEvaluacion->fetch_assoc();
        $evaluacion['aspectos_evaluados'] = json_decode($evaluacion['aspectos_evaluados'], true);
        $row['evaluacion'] = $evaluacion;
        $row['evaluado'] = true;
    } else {
        $row['evaluacion'] = null;
        $row['evaluado'] = false;
    }

    $reportes[] = $row;
}

echo json_encode([
    'success' => true,
    'reportes' => $reportes
]);

$conexion->close();
