<?php
// ===================================
// ARCHIVO: actualizar-borrador.php (CORREGIDO + LOGS DETALLADOS)
// ===================================
ini_set('display_errors', 1); // Activar temporalmente para debug
error_reporting(E_ALL);

// --- Configuración de logs específicos para este script ---
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/actualizar-borrador.log';

// Asegurar que PHP también use este archivo para error_log global
ini_set('log_errors', '1');
ini_set('error_log', $logFile);

// Helper para escribir logs consistentes
function app_log($message, $level = 'INFO') {
    global $logFile;
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI';
    $when = date('Y-m-d H:i:s');
    $line = "[$when] [$level] [$ip] $message" . PHP_EOL;
    // usar FILE_APPEND y LOCK_EX para reducir colisiones
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// Helper para volcar arrays en log de forma segura (limitar tamaño)
function dump_context($data, $max = 2000) {
    $s = @json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($s === false) {
        $s = print_r($data, true);
    }
    if (strlen($s) > $max) {
        $s = substr($s, 0, $max) . '... (truncated)';
    }
    return $s;
}

app_log('Inicio de ejecución de actualizar-borrador.php', 'INFO');

// ------------------ Handlers globales para logging detallado ------------------

// Convertir warnings/notices en excepciones (serán capturadas por try/catch)
set_error_handler(function($severity, $message, $file, $line) {
    // Respetar error_reporting
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $errMsg = "PHP Error: {$message} in {$file}:{$line} (severity={$severity})";
    app_log($errMsg, 'ERROR');
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Manejador para excepciones no capturadas
set_exception_handler(function($e) {
    // Clase, mensaje, archivo, linea y stacktrace
    $class = get_class($e);
    $msg = "Uncaught Exception ({$class}): " . $e->getMessage()
         . " in " . $e->getFile() . ":" . $e->getLine()
         . " | Trace: " . $e->getTraceAsString();
    app_log($msg, 'FATAL');

    // Contexto de la petición (limitar tamaño)
    $ctx = [
        'GET' => $_GET,
        'POST' => $_POST,
        'FILES' => array_map(function($f){
            return [
                'name' => isset($f['name']) ? $f['name'] : null,
                'type' => isset($f['type']) ? $f['type'] : null,
                'size' => isset($f['size']) ? $f['size'] : null,
                'error' => isset($f['error']) ? $f['error'] : null
            ];
        }, $_FILES),
        'SERVER' => [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ],
        'SESSION' => isset($_SESSION) ? $_SESSION : null
    ];
    app_log("Request Context: " . dump_context($ctx), 'DEBUG');

    // Responder al cliente con un mensaje genérico (no exponer detalles)
    if (!headers_sent()) {
        header('Content-Type: application/json', true, 500);
    }
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno.']);
    exit(1);
});

// Capturar errores fatales en shutdown
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $msg = "Fatal shutdown error: {$err['message']} in {$err['file']}:{$err['line']}";
        app_log($msg, 'FATAL');
        // También volcar algo de contexto pequeño
        $ctx = [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        app_log("Shutdown Request Context: " . dump_context($ctx), 'DEBUG');
    }
});

// ------------------------------------------------------------------------------

include 'conexion.php';
require_once __DIR__ . '/includes/KaizenUploads.php';
require_once __DIR__ . '/includes/OptimizarImagen.php';
header('Content-Type: application/json');

if (!isset($conexion) || !$conexion) {
    app_log('Error de conexión a la base de datos', 'ERROR');
    echo json_encode(array('success' => false, 'message' => 'Error de conexión a la base de datos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_log('Método no permitido: ' . $_SERVER['REQUEST_METHOD'], 'WARN');
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

try {
    $data = $_POST;

    // Validar que se envió el ID del reporte
    if (!isset($data['id_reporte']) || empty($data['id_reporte'])) {
        throw new Exception('ID de reporte requerido para actualizar');
    }

    $idReporte = intval($data['id_reporte']);
    app_log("Solicitud de actualización recibida - id_reporte={$idReporte}", 'INFO');

    // Validar que el reporte existe y es un borrador (condición más flexible)
    $sqlCheck = "SELECT id, estado 
             FROM reportes 
             WHERE id = ? 
             AND estado = 'borrador'";
    $stmtCheck = $conexion->prepare($sqlCheck);
    if (!$stmtCheck) {
        // Log detallado antes de lanzar excepción
        $err = "Error preparando consulta de verificación: " . $conexion->error;
        app_log($err, 'ERROR');
        throw new Exception('Error preparando consulta de verificación: ' . $conexion->error);
    }
    
    $stmtCheck->bind_param('i', $idReporte);
    if (!$stmtCheck->execute()) {
        app_log("Error ejecutando stmtCheck: " . $stmtCheck->error, 'ERROR');
        throw new Exception('Error ejecutando consulta de verificación: ' . $stmtCheck->error);
    }
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Borrador no encontrado o ya finalizado');
    }
    $stmtCheck->close();

    // Validar participantes
    $part = array();
    if (isset($data['participantes']) && !empty($data['participantes'])) {
        $part = json_decode($data['participantes'], true);
        if (!is_array($part)) {
            throw new Exception('Error al decodificar participantes');
        }
    }

    // Escapar datos
    $tema = isset($data['tema']) ? $conexion->real_escape_string(trim($data['tema'])) : '';
    $fecha = isset($data['fecha']) ? $conexion->real_escape_string(trim($data['fecha'])) : '';
    $desc_ant = isset($data['descripcion_anterior']) ? $conexion->real_escape_string(trim($data['descripcion_anterior'])) : '';
    $desc_mej = isset($data['descripcion_mejora']) ? $conexion->real_escape_string(trim($data['descripcion_mejora'])) : '';
    $analisis = isset($data['analisis_riesgo']) ? intval($data['analisis_riesgo']) : 0;

    if (empty($tema) || empty($fecha)) {
        throw new Exception('Los campos tema y fecha son obligatorios');
    }

    if (!is_dir('uploads') && !mkdir('uploads', 0755, true)) {
        throw new Exception('No se pudo crear la carpeta uploads');
    }

    function generarNombreSeguro($nombreOriginal, $prefijo = '') {
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $nombreLimpio = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
        return $prefijo . time() . '_' . substr($nombreLimpio, 0, 50) . '.' . $extension;
    }

    $conexion->autocommit(false);

    // Obtener rutas de archivos actuales
    $sqlFiles = "SELECT imagen_anterior, imagen_mejora, archivo_riesgo FROM reportes WHERE id = ?";
    $stmtFiles = $conexion->prepare($sqlFiles);
    if (!$stmtFiles) {
        $err = "Error preparando consulta de archivos: " . $conexion->error;
        app_log($err, 'ERROR');
        throw new Exception('Error preparando consulta de archivos: ' . $conexion->error);
    }
    
    $stmtFiles->bind_param('i', $idReporte);
    if (!$stmtFiles->execute()) {
        app_log("Error ejecutando stmtFiles: " . $stmtFiles->error, 'ERROR');
        throw new Exception('Error ejecutando consulta de archivos: ' . $stmtFiles->error);
    }
    $resultFiles = $stmtFiles->get_result();
    $currentFiles = $resultFiles->fetch_assoc();
    $stmtFiles->close();

    $imgAnteriorPath = $currentFiles['imagen_anterior'] ?? '';
    $imgMejoraPath = $currentFiles['imagen_mejora'] ?? '';
    $archivoRiesgoPath = $currentFiles['archivo_riesgo'] ?? '';

    // Procesar nuevos archivos si se enviaron
    if (isset($_FILES['archivo_riesgo']) && $_FILES['archivo_riesgo']['error'] === UPLOAD_ERR_OK) {
        // Eliminar archivo anterior
        if ($archivoRiesgoPath && file_exists($archivoRiesgoPath)) {
            KaizenUploads::eliminarArchivoSeguro($archivoRiesgoPath);
            app_log("Archivo de riesgo anterior eliminado: {$archivoRiesgoPath}", 'DEBUG');
        }
        
        $archivo = $_FILES['archivo_riesgo'];
        if ($archivo['size'] > 10 * 1024 * 1024) {
            throw new Exception('El archivo de riesgo es demasiado grande (máximo 10MB)');
        }
        
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new Exception('El archivo de riesgo debe ser un PDF');
        }
        
        $nombreSeguro = generarNombreSeguro($archivo['name'], 'borrador_riesgo_');
        $archivoRiesgoPath = KaizenUploads::construirRuta($fecha, $nombreSeguro);
        $destinoAbs = KaizenUploads::rutaAbsolutaParaGuardar($fecha, $nombreSeguro);
        
        if (!move_uploaded_file($archivo['tmp_name'], $destinoAbs)) {
            throw new Exception('Error al guardar el archivo de riesgo');
        }
        app_log("Archivo de riesgo guardado: {$archivoRiesgoPath}", 'DEBUG');
    }

    if (isset($_FILES['imagen_anterior']) && $_FILES['imagen_anterior']['error'] === UPLOAD_ERR_OK) {
        // Eliminar imagen anterior
        if ($imgAnteriorPath && file_exists($imgAnteriorPath)) {
            KaizenUploads::eliminarArchivoSeguro($imgAnteriorPath);
            app_log("Imagen anterior eliminada: {$imgAnteriorPath}", 'DEBUG');
        }
        
        $imagen = $_FILES['imagen_anterior'];
        if ($imagen['size'] > 5 * 1024 * 1024) {
            throw new Exception('La imagen anterior es demasiado grande (máximo 5MB)');
        }
        
        $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception('La imagen anterior debe ser JPG, PNG, GIF o WebP');
        }
        
        $nombreSeguro = generarNombreSeguro($imagen['name'], 'borrador_anterior_');
        $imgAnteriorPath = KaizenUploads::construirRuta($fecha, $nombreSeguro);
        $destinoAbs = KaizenUploads::rutaAbsolutaParaGuardar($fecha, $nombreSeguro);
        
        if (!move_uploaded_file($imagen['tmp_name'], $destinoAbs)) {
            throw new Exception('Error al guardar imagen anterior');
        }
        OptimizarImagen::despuesDeSubir($imgAnteriorPath, $fecha);
        app_log("Imagen anterior guardada: {$imgAnteriorPath}", 'DEBUG');
    }

    if (isset($_FILES['imagen_mejora']) && $_FILES['imagen_mejora']['error'] === UPLOAD_ERR_OK) {
        // Eliminar imagen anterior
        if ($imgMejoraPath && file_exists($imgMejoraPath)) {
            KaizenUploads::eliminarArchivoSeguro($imgMejoraPath);
            app_log("Imagen de mejora anterior eliminada: {$imgMejoraPath}", 'DEBUG');
        }
        
        $imagen = $_FILES['imagen_mejora'];
        if ($imagen['size'] > 5 * 1024 * 1024) {
            throw new Exception('La imagen de mejora es demasiado grande (máximo 5MB)');
        }
        
        $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception('La imagen de mejora debe ser JPG, PNG, GIF o WebP');
        }
        
        $nombreSeguro = generarNombreSeguro($imagen['name'], 'borrador_mejora_');
        $imgMejoraPath = KaizenUploads::construirRuta($fecha, $nombreSeguro);
        $destinoAbs = KaizenUploads::rutaAbsolutaParaGuardar($fecha, $nombreSeguro);
        
        if (!move_uploaded_file($imagen['tmp_name'], $destinoAbs)) {
            throw new Exception('Error al guardar imagen de mejora');
        }
        OptimizarImagen::despuesDeSubir($imgMejoraPath, $fecha);
        app_log("Imagen de mejora guardada: {$imgMejoraPath}", 'DEBUG');
    }

    // Actualizar el reporte - también actualizar la fecha de modificación
    $sqlUpdate = "UPDATE reportes SET 
    tema = ?, fecha = ?, imagen_anterior = ?, descripcion_anterior = ?, 
    imagen_mejora = ?, descripcion_mejora = ?, analisis_riesgo = ?, archivo_riesgo = ?,
    fecha_creacion = CASE 
        WHEN fecha_creacion = '0000-00-00 00:00:00' OR fecha_creacion IS NULL 
        THEN NOW() 
        ELSE fecha_creacion 
    END
    WHERE id = ? 
    AND (estado = 'borrador' OR estado LIKE '%borrador%')";

    
    $stmt = $conexion->prepare($sqlUpdate);
    if (!$stmt) {
        app_log("Error preparando actualización: " . $conexion->error, 'ERROR');
        throw new Exception('Error preparando actualización: ' . $conexion->error);
    }
    
    $stmt->bind_param('ssssssssi', $tema, $fecha, $imgAnteriorPath, $desc_ant, 
                      $imgMejoraPath, $desc_mej, $analisis, $archivoRiesgoPath, $idReporte);
    
    if (!$stmt->execute()) {
        app_log("Error al ejecutar actualización (stmt): " . $stmt->error, 'ERROR');
        throw new Exception('Error al actualizar el borrador: ' . $stmt->error);
    }

    $stmt->close();

    // Verificar que el borrador sigue existiendo (no usar affected_rows: MySQL devuelve 0 si los valores no cambiaron)
    $sqlVerify = "SELECT id FROM reportes WHERE id = ? AND (estado = 'borrador' OR estado LIKE '%borrador%') LIMIT 1";
    $stmtVerify = $conexion->prepare($sqlVerify);
    if (!$stmtVerify) {
        throw new Exception('Error al verificar el borrador actualizado');
    }
    $stmtVerify->bind_param('i', $idReporte);
    $stmtVerify->execute();
    $verifyResult = $stmtVerify->get_result();
    if ($verifyResult->num_rows === 0) {
        $stmtVerify->close();
        throw new Exception('No se pudo actualizar el borrador. Posiblemente ya fue finalizado.');
    }
    $stmtVerify->close();

    // Actualizar participantes
    $idsParticipantesAnteriores = [];
    $stmtOldPart = $conexion->prepare(
        'SELECT CAST(id_participante AS UNSIGNED) AS uid FROM reporte_participantes WHERE id_reporte = ?'
    );
    if ($stmtOldPart) {
        $stmtOldPart->bind_param('i', $idReporte);
        $stmtOldPart->execute();
        $resOldPart = $stmtOldPart->get_result();
        while ($rowOld = $resOldPart->fetch_assoc()) {
            $uidOld = (int) ($rowOld['uid'] ?? 0);
            if ($uidOld > 0) {
                $idsParticipantesAnteriores[] = $uidOld;
            }
        }
        $stmtOldPart->close();
    }
    if (!empty($part)) {
        // Eliminar participantes actuales
        $sqlDelPart = "DELETE FROM reporte_participantes WHERE id_reporte = ?";
        $stmtDelPart = $conexion->prepare($sqlDelPart);
        if (!$stmtDelPart) {
            app_log('Error preparando eliminación de participantes: ' . $conexion->error, 'ERROR');
            throw new Exception('Error preparando eliminación de participantes: ' . $conexion->error);
        }
        
        $stmtDelPart->bind_param('i', $idReporte);
        if (!$stmtDelPart->execute()) {
            app_log('Error ejecutando eliminación de participantes: ' . $stmtDelPart->error, 'ERROR');
            throw new Exception('Error eliminando participantes: ' . $stmtDelPart->error);
        }
        $stmtDelPart->close();

        // Insertar nuevos participantes
        $sqlPart = "INSERT INTO reporte_participantes (id_reporte, id_participante, nombre, departamento) VALUES (?, ?, ?, ?)";
        $stmtPart = $conexion->prepare($sqlPart);
        if (!$stmtPart) {
            app_log('Error preparando inserción de participantes: ' . $conexion->error, 'ERROR');
            throw new Exception('Error preparando inserción de participantes: ' . $conexion->error);
        }
        
        foreach ($part as $p) {
            $idP = isset($p['id']) ? trim($p['id']) : '';
            $nom = isset($p['nombre']) ? trim($p['nombre']) : '';
            $dept = isset($p['departamento']) ? trim($p['departamento']) : '';
            // Usar el ID del participante también como EmpId si no se especifica otro
            $empId = isset($p['EmpId']) ? trim($p['EmpId']) : $idP;
            
            if (empty($idP)) {
                throw new Exception('ID de participante es obligatorio');
            }
            
            $stmtPart->bind_param('isss', $idReporte, $idP, $nom, $dept);
            
            if (!$stmtPart->execute()) {
                app_log('Error al ejecutar inserción de participante: ' . $stmtPart->error, 'ERROR');
                throw new Exception('Error al guardar participante: ' . $stmtPart->error);
            }
        }
        
        $stmtPart->close();
    }

    $conexion->commit();

    require_once __DIR__ . '/includes/NotificacionesParticipantes.php';
    $idsActuales = NotificacionesParticipantes::idsParticipantesReporte($conexion, $idReporte);
    $idsNuevos = array_values(array_diff($idsActuales, $idsParticipantesAnteriores));
    if ($idsNuevos !== []) {
        NotificacionesParticipantes::notificarInclusionBorrador(
            $conexion,
            $idReporte,
            $tema,
            $idsNuevos,
            null
        );
    }
    
    // Log para debug (ahora al archivo específico)
    app_log("Borrador actualizado exitosamente - ID: $idReporte", 'INFO');
    
    echo json_encode(array(
        'success' => true, 
        'message' => 'Borrador actualizado exitosamente', 
        'id_reporte' => $idReporte
    ));

} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        $conexion->rollback();
    }
    
    // Log detallado con trace, clase, archivo, linea y contexto
    $class = get_class($e);
    $msg = "Error actualizando borrador ({$class}): " . $e->getMessage()
         . " in " . $e->getFile() . ":" . $e->getLine()
         . " | Trace: " . $e->getTraceAsString();
    app_log($msg, 'ERROR');

    // Volcar contexto relevante (POST/GET/FILES/REMOTE_ADDR) - truncado
    $context = [
        'POST' => $_POST,
        'GET' => $_GET,
        'FILES' => array_map(function($f){
            return [
                'name' => isset($f['name']) ? $f['name'] : null,
                'size' => isset($f['size']) ? $f['size'] : null,
                'error' => isset($f['error']) ? $f['error'] : null
            ];
        }, $_FILES),
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    app_log("Context at error: " . dump_context($context), 'DEBUG');

    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
} finally {
    if (isset($conexion) && $conexion) {
        $conexion->autocommit(true);
        $conexion->close();
    }
    app_log('Fin de ejecución de actualizar-borrador.php', 'INFO');
}
?>
