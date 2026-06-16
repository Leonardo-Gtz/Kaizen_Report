<?php
// ===================================
// ARCHIVO: obtener-borradores.php (CORREGIDO)
// ===================================
ini_set('display_errors', 0);
error_reporting(0);

session_start();
include 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'trabajador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($conexion) || !$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Error de conexión a la base de datos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

try {
    $idUsuario = strval(intval($_SESSION['usuario']['id']));
    
    if (empty($idUsuario) || $idUsuario === '0') {
        throw new Exception('ID de usuario inválido');
    }

    // Consulta corregida para obtener borradores
    // Usar COALESCE para manejar fechas nulas y ordenar por ID si la fecha no es válida
    $sql = "SELECT DISTINCT r.id, r.tema, r.fecha, r.descripcion_anterior, r.descripcion_mejora, 
                   r.analisis_riesgo, r.imagen_anterior, r.imagen_mejora, r.archivo_riesgo,
                   CASE 
                       WHEN r.fecha_creacion = '0000-00-00 00:00:00' OR r.fecha_creacion IS NULL 
                       THEN NOW() 
                       ELSE r.fecha_creacion 
                   END as fecha_creacion,
                   r.estado
            FROM reportes r 
            INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte 
            WHERE rp.id_participante = ?
            AND (r.estado = 'borrador' OR r.estado LIKE '%borrador%')
            ORDER BY r.id DESC";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conexion->error);
    }
    
    $stmt->bind_param('s', $idUsuario);
    
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    
    $borradores = array();
    while ($fila = $resultado->fetch_assoc()) {
        // Obtener participantes para cada borrador
        $sqlPart = "SELECT id_participante, nombre, departamento 
                    FROM reporte_participantes 
                    WHERE id_reporte = ?";
        $stmtPart = $conexion->prepare($sqlPart);
        
        if ($stmtPart) {
            $stmtPart->bind_param('i', $fila['id']);
            $stmtPart->execute();
            $resultPart = $stmtPart->get_result();
            
            $participantes = array();
            while ($part = $resultPart->fetch_assoc()) {
                $participantes[] = array(
                    'id' => $part['id_participante'],
                    'nombre' => $part['nombre'],
                    'departamento' => $part['departamento']
                );
            }
            $stmtPart->close();
        } else {
            $participantes = array();
        }
        
        $fila['participantes'] = $participantes;
        
        // Limpiar la fecha si es inválida
        if ($fila['fecha_creacion'] == '0000-00-00 00:00:00') {
            $fila['fecha_creacion'] = date('Y-m-d H:i:s');
        }

        // Usar fecha_creacion si fecha está vacía o nula
        $fila['fecha'] = $fila['fecha'] ?: $fila['fecha_creacion'];
        
        $borradores[] = $fila;
    }
    
    $stmt->close();
    
    // Log para debug (remover en producción)
    error_log("Borradores encontrados para usuario $idUsuario: " . count($borradores));
    
    echo json_encode(array(
        'success' => true, 
        'borradores' => $borradores,
        'total' => count($borradores)
    ));


    
} catch (Exception $e) {
    error_log("Error en obtener-borradores.php: " . $e->getMessage());
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
} finally {
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
}
?>