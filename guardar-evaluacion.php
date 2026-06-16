<?php
session_start();

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'conexion.php';
require_once 'flujo-reporte-gerente.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Función de logging mejorada
// function logDebug($mensaje, $datos = null) {
//     $timestamp = date('Y-m-d H:i:s');
//     $logEntry = "[$timestamp] $mensaje";
//     if ($datos !== null) {
//         $logEntry .= "\nDatos: " . print_r($datos, true);
//     }
//     $logEntry .= "\n" . str_repeat('-', 50) . "\n";
//     file_put_contents('evaluacion_debug.log', $logEntry, FILE_APPEND | LOCK_EX);
// }

try {
    // Log inicial
    // logDebug("=== INICIO EVALUACIÓN GERENTE ===");
    // logDebug("Método HTTP", $_SERVER['REQUEST_METHOD']);
    // logDebug("Headers recibidos", getallheaders());

    // Verificar conexión
    if (!isset($conexion) || !$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Se requiere POST');
    }

    // Leer datos JSON
    $dataRaw = file_get_contents('php://input');
    // logDebug("Datos crudos recibidos", $dataRaw);

    if (empty($dataRaw)) {
        throw new Exception('No se recibieron datos en el cuerpo de la petición');
    }

    // Decodificar JSON
    $data = json_decode($dataRaw, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Los datos JSON están vacíos o son inválidos');
    }

    // logDebug("Datos decodificados", $data);

    // Extraer y validar datos
    $idReporte = isset($data['idReporte']) ? trim($data['idReporte']) : '';
    $clasificacion = isset($data['clasificacion']) ? trim($data['clasificacion']) : '';
    $aspectos = isset($data['aspectos']) ? $data['aspectos'] : [];

    // logDebug("Datos extraídos", [
    //     'idReporte' => $idReporte,
    //     'clasificacion' => $clasificacion,
    //     'aspectos' => $aspectos
    // ]);

    // Validaciones específicas
    if (empty($idReporte)) {
        throw new Exception('ID de reporte no proporcionado o vacío');
    }

    if (!is_numeric($idReporte) || intval($idReporte) <= 0) {
        throw new Exception('ID de reporte debe ser un número positivo');
    }

    if (empty($clasificacion)) {
        throw new Exception('Clasificación no proporcionada o vacía');
    }

    $clasificacionesValidas = ['A', 'B', 'C', 'D', 'E'];
    if (!in_array($clasificacion, $clasificacionesValidas)) {
        throw new Exception('Clasificación inválida. Debe ser: ' . implode(', ', $clasificacionesValidas));
    }

    if (!is_array($aspectos)) {
        throw new Exception('Los aspectos deben ser un array');
    }

    if (empty($aspectos)) {
        throw new Exception('Debe proporcionar al menos un aspecto evaluado');
    }

    $aspectosNormalizados = [];
    foreach ($aspectos as $item) {
        if (is_string($item) && trim($item) !== '') {
            $aspectosNormalizados[] = trim($item);
        } elseif (is_array($item) && !empty($item['aspecto'])) {
            $aspectosNormalizados[] = trim((string) $item['aspecto']);
        } elseif (is_array($item) && isset($item[0]) && is_string($item[0])) {
            $aspectosNormalizados[] = trim($item[0]);
        }
    }
    $aspectosNormalizados = array_values(array_unique($aspectosNormalizados));
    $aliasAspecto = ['Medio Ambiente' => 'Ambiental'];
    $aspectosNormalizados = array_map(function ($a) use ($aliasAspecto) {
        return $aliasAspecto[$a] ?? $a;
    }, $aspectosNormalizados);
    if (empty($aspectosNormalizados)) {
        throw new Exception('Debe proporcionar al menos un aspecto evaluado válido');
    }
    $aspectos = $aspectosNormalizados;

    // Verificar que el reporte existe y puede calificarse
    $checkStmt = $conexion->prepare("SELECT id, estadoRH, estadoSupervisor, estadoGerente FROM reportes WHERE id = ?");
    if (!$checkStmt) {
        throw new Exception('Error al preparar consulta de verificación: ' . $conexion->error);
    }

    $checkStmt->bind_param("i", $idReporte);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('El reporte con ID ' . $idReporte . ' no existe');
    }
    $reporteCheck = $result->fetch_assoc();
    $checkStmt->close();

    validarAccionGerenteReporte($conexion, $reporteCheck, 'calificar');

    // Verificar si ya existe una evaluación para este reporte
    $existeStmt = $conexion->prepare("SELECT id FROM evaluaciones WHERE id_reporte = ?");
    if (!$existeStmt) {
        throw new Exception('Error al preparar consulta de existencia: ' . $conexion->error);
    }

    $existeStmt->bind_param("i", $idReporte);
    $existeStmt->execute();
    $existeResult = $existeStmt->get_result();

    if ($existeResult->num_rows > 0) {
        // Si ya existe, actualizar en lugar de insertar
        $updateStmt = $conexion->prepare("UPDATE evaluaciones SET clasificacion = ?, aspectos_evaluados = ?, fecha = CURRENT_TIMESTAMP WHERE id_reporte = ?");
        if (!$updateStmt) {
            throw new Exception('Error al preparar consulta de actualización: ' . $conexion->error);
        }

        $aspectosJSON = json_encode($aspectos, JSON_UNESCAPED_UNICODE);
        $updateStmt->bind_param("ssi", $clasificacion, $aspectosJSON, $idReporte);

        if (!$updateStmt->execute()) {
            throw new Exception('Error al actualizar la evaluación: ' . $updateStmt->error);
        }

        $updateStmt->close();
        $mensaje = 'Evaluación actualizada exitosamente';
        // logDebug("Evaluación actualizada para reporte ID: $idReporte");

    } else {
        // Insertar nueva evaluación - la columna fecha se llena automáticamente con CURRENT_TIMESTAMP
        $insertStmt = $conexion->prepare("INSERT INTO evaluaciones (id_reporte, clasificacion, aspectos_evaluados) VALUES (?, ?, ?)");
        if (!$insertStmt) {
            throw new Exception('Error al preparar consulta de inserción: ' . $conexion->error);
        }

        $aspectosJSON = json_encode($aspectos, JSON_UNESCAPED_UNICODE);
        $insertStmt->bind_param("iss", $idReporte, $clasificacion, $aspectosJSON);

        if (!$insertStmt->execute()) {
            throw new Exception('Error al insertar la evaluación: ' . $insertStmt->error);
        }

        $insertStmt->close();
        $mensaje = 'Evaluación guardada exitosamente';
        // logDebug("Nueva evaluación creada para reporte ID: $idReporte");
    }

    $existeStmt->close();

    // Respuesta exitosa
    $respuesta = [
        'success' => true,
        'message' => $mensaje,
        'data' => [
            'id_reporte' => $idReporte,
            'clasificacion' => $clasificacion,
            'aspectos_count' => count($aspectos),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    // logDebug("Respuesta exitosa", $respuesta);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $errorResponse = [
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        // 'debug_info' => [
        //     'file' => basename(__FILE__),
        //     'line' => $e->getLine()
        // ]
    ];

    // logDebug("ERROR", $errorResponse);
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);

} finally {
    // Cerrar conexión
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
    // logDebug("=== FIN EVALUACIÓN GERENTE ===");
}
?>