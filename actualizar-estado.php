<?php
session_start();

ini_set('display_errors', 0);
error_reporting(0);

// Headers CORS completos
header('Content-Type: application/json; charset=UTF-8');
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
//     file_put_contents('estado_rh_debug.log', $logEntry, FILE_APPEND | LOCK_EX);
// }

try {
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    // Incluir conexión
    include 'conexion.php';
    require_once 'flujo-reporte-rh.php';
    require_once __DIR__ . '/includes/PlazoRevision.php';
    
    // Verificar conexión
    if (!isset($conexion) || !$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Se requiere POST');
    }

    // Leer y decodificar el input
    $inputJSON = file_get_contents('php://input');
    // logDebug("Datos crudos recibidos", $inputJSON);

    if (empty($inputJSON)) {
        throw new Exception('No se recibieron datos en el cuerpo de la petición');
    }

    $data = json_decode($inputJSON, true); // Usar array asociativo
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Los datos JSON están vacíos o son inválidos');
    }

    // logDebug("Datos decodificados", $data);

    // Obtener y validar parámetros (según lo que envía el TypeScript)
    $idReporte = isset($data['id']) ? trim($data['id']) : '';
    $estado = isset($data['estadoRH']) ? strtolower(trim($data['estadoRH'])) : '';
    $razonRechazo = isset($data['razonRechazoRH']) ? trim($data['razonRechazoRH']) : '';

    // logDebug("Parámetros extraídos", [
    //     'id' => $idReporte,
    //     'estadoRH' => $estado,
    //     'razonRechazoRH' => $razonRechazo
    // ]);

    // Validaciones
    if (empty($idReporte)) {
        throw new Exception('ID de reporte no proporcionado o vacío');
    }

    if (!is_numeric($idReporte) || intval($idReporte) <= 0) {
        throw new Exception('ID de reporte debe ser un número positivo');
    }

    if (!in_array($estado, ['aceptado', 'rechazado'])) {
        throw new Exception('Estado no válido. Debe ser "aceptado" o "rechazado"');
    }

    // Si es rechazado, validar que tenga razón
    if ($estado === 'rechazado') {
        if (empty($razonRechazo)) {
            throw new Exception('La razón de rechazo es obligatoria cuando se rechaza un reporte');
        }
        if (strlen($razonRechazo) < 10) {
            throw new Exception('La razón de rechazo debe tener al menos 10 caracteres');
        }
    }

    // Verificar que el reporte existe y está pendiente
    $checkStmt = $conexion->prepare("SELECT id, fecha, estadoRH, estadoSupervisor, estadoGerente, fecha_limite_revision FROM reportes WHERE id = ?");
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
    if (!empty($reporteCheck['estadoRH']) && $reporteCheck['estadoRH'] !== 'pendiente') {
        throw new Exception('Este reporte ya fue ' . $reporteCheck['estadoRH'] . ' por RH');
    }
    $checkStmt->close();

    validarAccionRhReporte($conexion, $reporteCheck, $estado === 'aceptado' ? 'aceptar' : 'rechazar');

    PlazoRevision::asegurarEsquema($conexion);
    $mesEfectivo = null;
    $fueraTiempo = 0;
    if ($estado === 'aceptado') {
        $cierre = date('Y-m-d H:i:s');
        $resuelto = PlazoRevision::resolverMesEfectivo(
            $reporteCheck['fecha'],
            $cierre,
            $reporteCheck['fecha_limite_revision'] ?? null
        );
        $mesEfectivo = $resuelto['mes_efectivo'];
        $fueraTiempo = $resuelto['fuera_tiempo'];
    }

    $revisorRhId = (int) ($_SESSION['usuario']['id'] ?? 0);

    // Preparar consulta según el estado
    if ($estado === 'rechazado') {
        $sql = "UPDATE reportes SET estadoRH = ?, razon_rechazo_rh = ?, fecha_aprobacion_rh = NOW(), revisado_por_rh_id = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("ssii", $estado, $razonRechazo, $revisorRhId, $idReporte);
    } else {
        $sql = "UPDATE reportes SET estadoRH = ?, razon_rechazo_rh = NULL, fecha_aprobacion_rh = NOW(), mes_efectivo = ?, fuera_tiempo = ?, revisado_por_rh_id = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("ssiii", $estado, $mesEfectivo, $fueraTiempo, $revisorRhId, $idReporte);
    }

    // Ejecutar consulta
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }

    // Verificar que se actualizó al menos un registro
    if ($stmt->affected_rows === 0) {
        throw new Exception("No se pudo actualizar el reporte. Verifica que el ID sea correcto");
    }

    $stmt->close();

    require_once __DIR__ . '/includes/NotificacionesParticipantes.php';
    $tema = NotificacionesParticipantes::temaReporte($conexion, $idReporte);
    $revisor = NotificacionesParticipantes::revisorDesdeSesion($_SESSION['usuario']);
    NotificacionesParticipantes::notificarAccionRh($conexion, $idReporte, $tema, $estado, $revisor);

    // Respuesta exitosa
    $mensaje = $estado === 'aceptado' ? 'Reporte aceptado correctamente' : 'Reporte rechazado correctamente';
    
    $respuesta = [
        'success' => true,
        'message' => $mensaje,
        'data' => [
            'id_reporte' => $idReporte,
            'estado' => $estado,
            'mes_efectivo' => $estado === 'aceptado' ? $mesEfectivo : null,
            'fuera_tiempo' => $estado === 'aceptado' ? (bool) $fueraTiempo : false,
            'razon_rechazo' => $estado === 'rechazado' ? $razonRechazo : null,
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
    http_response_code(400);
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);

} finally {
    // Cerrar conexión
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
    // logDebug("=== FIN ACTUALIZACIÓN ESTADO RH ===");
}
?>