<?php
session_start();
// Configuración de errores para debugging
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

ini_set('display_errors', 0);
error_reporting(0);

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función de logging
// function logDebug($mensaje, $datos = null) {
//     $timestamp = date('Y-m-d H:i:s');
//     $logEntry = "[$timestamp] $mensaje";
//     if ($datos !== null) {
//         $logEntry .= "\nDatos: " . print_r($datos, true);
//     }
//     $logEntry .= "\n" . str_repeat('-', 50) . "\n";
//     file_put_contents('gerente_debug.log', $logEntry, FILE_APPEND | LOCK_EX);
// }

try {
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    // logDebug("=== INICIO CAMBIO ESTADO GERENTE ===");
    // logDebug("Método HTTP", $_SERVER['REQUEST_METHOD']);

    // incluir conexión
    include 'conexion.php';
    require_once __DIR__ . '/includes/PlazoRevision.php';
    require_once __DIR__ . '/flujo-reporte-gerente.php';

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

    $data = json_decode($dataRaw, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Los datos JSON están vacíos o son inválidos');
    }

    // logDebug("Datos decodificados", $data);

    // Validar datos requeridos - el frontend envía 'idReporte' y 'estado'
    if (!isset($data['idReporte']) || empty($data['idReporte'])) {
        throw new Exception('ID de reporte no proporcionado');
    }

    if (!isset($data['estado']) || empty($data['estado'])) {
        throw new Exception('Estado del gerente no proporcionado');
    }

    $id = trim($data['idReporte']);
    $estado = trim($data['estado']);

    // Validar ID
    if (!is_numeric($id) || intval($id) <= 0) {
        throw new Exception('ID de reporte debe ser un número positivo');
    }

    // Validar estado
    $estadosValidos = ['pendiente', 'autorizado', 'rechazado'];
    if (!in_array($estado, $estadosValidos)) {
        throw new Exception('Estado inválido. Debe ser: ' . implode(', ', $estadosValidos));
    }

    // logDebug("Datos validados", ['id' => $id, 'estado' => $estado]);

    $razonRechazo = isset($data['razonRechazo']) ? trim($data['razonRechazo']) : '';

    if ($estado === 'rechazado' && mb_strlen($razonRechazo) < 10) {
        throw new Exception('La razón de rechazo es obligatoria (mínimo 10 caracteres)');
    }

    // Verificar que el reporte existe
    $checkStmt = $conexion->prepare("SELECT id, estadoGerente, estadoSupervisor FROM reportes WHERE id = ?");
    if (!$checkStmt) {
        throw new Exception('Error al preparar consulta de verificación: ' . $conexion->error);
    }

    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('El reporte con ID ' . $id . ' no existe');
    }

    $reporteActual = $result->fetch_assoc();
    // logDebug("Reporte encontrado", $reporteActual);
    $checkStmt->close();

    $estadoGerenteActual = $reporteActual['estadoGerente'] ?? 'pendiente';
    if (!in_array($estadoGerenteActual, ['', 'pendiente'], true)) {
        throw new Exception('Este reporte ya fue procesado por el gerente');
    }

    if (in_array($estado, ['autorizado', 'rechazado'], true)) {
        $estadoSupervisor = $reporteActual['estadoSupervisor'] ?? 'pendiente';
        if ($estadoSupervisor !== 'aprobado') {
            throw new Exception('El supervisor debe aprobar el reporte antes de que el gerente pueda actuar');
        }
    }

    if ($estado === 'autorizado') {
        if (!reporteTieneEvaluacionGerente($conexion, (int) $id)) {
            throw new Exception('Debes calificar el reporte (letra y aspectos) antes de autorizarlo');
        }
        validarAccionGerenteReporte($conexion, array_merge($reporteActual, ['id' => (int) $id]), 'autorizar');
    }

    if ($estado === 'rechazado') {
        validarAccionGerenteReporte($conexion, array_merge($reporteActual, ['id' => (int) $id]), 'rechazar');
    }

    $revisorId = (int) ($_SESSION['usuario']['id'] ?? 0);

    // Preparar y ejecutar la actualización
    if ($estado === 'rechazado') {
        $stmt = $conexion->prepare(
            "UPDATE reportes SET estadoGerente = ?, razon_rechazo = ?, fecha_aprobacion_gerente = NOW(), revisado_por_gerente_id = ? WHERE id = ?"
        );
        if (!$stmt) {
            throw new Exception('Error al preparar consulta de actualización: ' . $conexion->error);
        }
        $stmt->bind_param("ssii", $estado, $razonRechazo, $revisorId, $id);
    } else {
        $stmt = $conexion->prepare(
            "UPDATE reportes SET estadoGerente = ?, fecha_aprobacion_gerente = NOW(), revisado_por_gerente_id = ? WHERE id = ?"
        );
        if (!$stmt) {
            throw new Exception('Error al preparar consulta de actualización: ' . $conexion->error);
        }
        $stmt->bind_param("sii", $estado, $revisorId, $id);
    }

    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar actualización: ' . $stmt->error);
    }

    $filasAfectadas = $stmt->affected_rows;
    // logDebug("Actualización ejecutada", ['filas_afectadas' => $filasAfectadas]);

    if ($filasAfectadas === 0) {
        throw new Exception('No se pudo actualizar el reporte. Es posible que ya tenga ese estado.');
    }

    $stmt->close();

    require_once __DIR__ . '/includes/NotificacionesParticipantes.php';
    $tema = NotificacionesParticipantes::temaReporte($conexion, (int) $id);
    $revisor = NotificacionesParticipantes::revisorDesdeSesion($_SESSION['usuario']);
    NotificacionesParticipantes::notificarAccionGerente($conexion, (int) $id, $tema, $estado, $revisor);

    // Respuesta exitosa
    $respuesta = [
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'data' => [
            'id' => $id,
            'estadoAnterior' => $reporteActual['estadoGerente'],
            'estadoNuevo' => $estado,
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

    //logDebug("ERROR", $errorResponse);
    
    http_response_code(400); // Bad Request
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);

} finally {
    // Cerrar conexión
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
    // logDebug("=== FIN CAMBIO ESTADO GERENTE ===");
}
?>