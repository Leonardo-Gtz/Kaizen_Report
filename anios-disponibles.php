<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include_once 'conexion.php';

try {
    $sql = "SELECT DISTINCT YEAR(STR_TO_DATE(fecha, '%Y-%m-%d')) as anio 
            FROM reportes 
            ORDER BY anio DESC";
    
    $resultado = $conexion->query($sql);
    $anios = array();
    
    while ($fila = $resultado->fetch_assoc()) {
        $anios[] = intval($fila['anio']);
    }
    
    // Si no hay años, agregar el año actual
    if (empty($anios)) {
        $anios[] = intval(date('Y'));
    }
    
    echo json_encode($anios);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("error" => "Error al obtener años disponibles: " . $e->getMessage()));
}

$conexion->close();
?>