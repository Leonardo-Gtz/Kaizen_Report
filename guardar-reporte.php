<?php
// Deshabilitar la visualización de errores para esta página específica
ini_set('display_errors', 0);
error_reporting(0);

// Incluir conexión
include 'conexion.php';
require_once __DIR__ . '/includes/KaizenUploads.php';
require_once __DIR__ . '/includes/OptimizarImagen.php';

// Asegurar que siempre devolvemos JSON
header('Content-Type: application/json');

// Verificar que la conexión existe
if (!isset($conexion) || !$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $data = $_POST;

    // Validar participantes
    $part = [];
    if (isset($data['participantes']) && !empty($data['participantes'])) {
        $part = json_decode($data['participantes'], true);
        if (!is_array($part)) {
            throw new Exception('Error al decodificar participantes');
        }
    } else {
        throw new Exception('No se enviaron participantes');
    }

    // Escapar datos de manera segura
    $tema = isset($data['tema']) ? $conexion->real_escape_string(trim($data['tema'])) : '';
    $fecha = isset($data['fecha']) ? $conexion->real_escape_string(trim($data['fecha'])) : '';
    $desc_ant = isset($data['descripcion_anterior']) ? $conexion->real_escape_string(trim($data['descripcion_anterior'])) : '';
    $desc_mej = isset($data['descripcion_mejora']) ? $conexion->real_escape_string(trim($data['descripcion_mejora'])) : '';
    $analisis = isset($data['analisis_riesgo']) ? intval($data['analisis_riesgo']) : 0;

    // Validar campos obligatorios
    if (empty($tema) || empty($fecha)) {
        throw new Exception('Los campos tema y fecha son obligatorios');
    }

    if (!is_dir('uploads') && !mkdir('uploads', 0755, true)) {
        throw new Exception('No se pudo crear la carpeta uploads');
    }
    if (!is_writable('uploads')) {
        throw new Exception('No hay permisos de escritura en la carpeta uploads');
    }

    // Función para generar nombres seguros
    function generarNombreSeguro($nombreOriginal, $prefijo = '') {
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $nombreLimpio = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
        return $prefijo . time() . '_' . substr($nombreLimpio, 0, 50) . '.' . $extension;
    }

    // Variables para rutas de archivos
    $imgAnteriorPath = null;
    $imgMejoraPath = null;
    $archivoRiesgoPath = null;

    // Procesar archivo de riesgo
    if (isset($_FILES['archivo_riesgo']) && $_FILES['archivo_riesgo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo_riesgo'];
        
        // Validar tamaño (máximo 1.5MB)
        if ($archivo['size'] > 1.5 * 1024 * 1024) {
            throw new Exception('El archivo de riesgo es demasiado grande (máximo 1.5MB)');
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new Exception('El archivo de riesgo debe ser un PDF');
        }
        
        $nombreSeguro = generarNombreSeguro($archivo['name'], 'riesgo_');
        $archivoRiesgoPath = KaizenUploads::construirRuta($fecha, $nombreSeguro);
        $destinoAbs = KaizenUploads::rutaAbsolutaParaGuardar($fecha, $nombreSeguro);
        
        if (!move_uploaded_file($archivo['tmp_name'], $destinoAbs)) {
            throw new Exception('Error al guardar el archivo de riesgo');
        }
    }

    // Procesar imagen anterior
    if (isset($_FILES['imagen_anterior']) && $_FILES['imagen_anterior']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen_anterior'];
        
        // Validar tamaño (máximo 1MB)
        if ($imagen['size'] > 1 * 1024 * 1024) {
            throw new Exception('La imagen anterior es demasiado grande (máximo 1MB)');
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception('La imagen anterior debe ser JPG, PNG, GIF o WebP');
        }
        
        $nombreSeguro = generarNombreSeguro($imagen['name'], 'anterior_');
        $imgAnteriorPath = KaizenUploads::construirRuta($fecha, $nombreSeguro);
        $destinoAbs = KaizenUploads::rutaAbsolutaParaGuardar($fecha, $nombreSeguro);
        
        if (!move_uploaded_file($imagen['tmp_name'], $destinoAbs)) {
            throw new Exception('Error al guardar imagen anterior');
        }
        OptimizarImagen::despuesDeSubir($imgAnteriorPath, $fecha);
    }

    // Procesar imagen mejora
    if (isset($_FILES['imagen_mejora']) && $_FILES['imagen_mejora']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen_mejora'];
        
        // Validar tamaño (máximo 1MB)
        if ($imagen['size'] > 1 * 1024 * 1024) {
            throw new Exception('La imagen de mejora es demasiado grande (máximo 1MB)');
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception('La imagen de mejora debe ser JPG, PNG, GIF o WebP');
        }
        
        $nombreSeguro = generarNombreSeguro($imagen['name'], 'mejora_');
        $imgMejoraPath = KaizenUploads::construirRuta($fecha, $nombreSeguro);
        $destinoAbs = KaizenUploads::rutaAbsolutaParaGuardar($fecha, $nombreSeguro);
        
        if (!move_uploaded_file($imagen['tmp_name'], $destinoAbs)) {
            throw new Exception('Error al guardar imagen de mejora');
        }
        OptimizarImagen::despuesDeSubir($imgMejoraPath, $fecha);
    }

    // Iniciar transacción
    $conexion->autocommit(false);

    // Preparar statement para insertar reporte
    $sqlRpt = "INSERT INTO reportes 
    (tema, fecha, imagen_anterior, descripcion_anterior, imagen_mejora, descripcion_mejora, analisis_riesgo, archivo_riesgo, estado, fecha_creacion, fecha_finalizacion) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'finalizado', NOW(), NOW())";

    $stmt = $conexion->prepare($sqlRpt);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $conexion->error);
    }

    $stmt->bind_param('ssssssss', $tema, $fecha, $imgAnteriorPath, $desc_ant, $imgMejoraPath, $desc_mej, $analisis, $archivoRiesgoPath);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar el reporte: ' . $stmt->error);
    }

    $idRpt = $conexion->insert_id;
    $stmt->close();

    // Insertar participantes
    if (!empty($part)) {
        $sqlPart = "INSERT INTO reporte_participantes (id_reporte, id_participante, nombre, departamento) VALUES (?, ?, ?, ?)";
        $stmtPart = $conexion->prepare($sqlPart);
        
        if (!$stmtPart) {
            throw new Exception('Error al preparar consulta de participantes: ' . $conexion->error);
        }

        foreach ($part as $p) {
            $idP = isset($p['id']) ? trim($p['id']) : '';
            $nom = isset($p['nombre']) ? trim($p['nombre']) : '';
            $dept = isset($p['departamento']) ? trim($p['departamento']) : '';
            
            // Validar datos mínimos
            if (empty($idP) || empty($nom)) {
                throw new Exception('Datos de participante incompletos');
            }
            
            $stmtPart->bind_param('isss', $idRpt, $idP, $nom, $dept);
            
            if (!$stmtPart->execute()) {
                throw new Exception('Error al guardar participante: ' . $stmtPart->error);
            }
        }
        
        $stmtPart->close();
    }

    // Confirmar transacción
    $conexion->commit();

    require_once __DIR__ . '/includes/NotificacionesParticipantes.php';
    $idsParticipantes = NotificacionesParticipantes::idsParticipantesReporte($conexion, (int) $idRpt);
    NotificacionesParticipantes::notificarReporteEnviado($conexion, (int) $idRpt, $tema, $idsParticipantes);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Reporte guardado exitosamente', 
        'id_reporte' => $idRpt,
        'archivos_guardados' => [
            'imagen_anterior' => $imgAnteriorPath ? basename($imgAnteriorPath) : null,
            'imagen_mejora' => $imgMejoraPath ? basename($imgMejoraPath) : null,
            'archivo_riesgo' => $archivoRiesgoPath ? basename($archivoRiesgoPath) : null
        ]
    ]);

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conexion) && $conexion) {
        $conexion->rollback();
    }
    
    // Limpiar archivos subidos en caso de error
    if (isset($imgAnteriorPath) && file_exists($imgAnteriorPath)) {
        unlink($imgAnteriorPath);
    }
    if (isset($imgMejoraPath) && file_exists($imgMejoraPath)) {
        unlink($imgMejoraPath);
    }
    if (isset($archivoRiesgoPath) && file_exists($archivoRiesgoPath)) {
        unlink($archivoRiesgoPath);
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Restaurar autocommit y cerrar conexión
    if (isset($conexion) && $conexion) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}
?>