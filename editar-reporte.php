<?php
include 'conexion.php';
header('Content-Type: application/json');

$data = $_POST;

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$idReporte = isset($data['id']) ? intval($data['id']) : 0;
if ($idReporte <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de reporte no válido']);
    exit;
}

// Obtener rutas actuales de la BD para conservar si no hay archivo nuevo
$sqlActual = "SELECT imagen_anterior, imagen_mejora, archivo_riesgo FROM reportes WHERE id = $idReporte";
$resActual = $conexion->query($sqlActual);

if ($resActual && $resActual->num_rows > 0) {
    $row = $resActual->fetch_assoc();
    if (!isset($_FILES['imagen_anterior']) || $_FILES['imagen_anterior']['error'] !== 0) {
        $imagenAnterior = $row['imagen_anterior'];
    }
    if (!isset($_FILES['imagen_mejora']) || $_FILES['imagen_mejora']['error'] !== 0) {
        $imagenMejora = $row['imagen_mejora'];
    }
    if (!isset($_FILES['archivo_riesgo']) || $_FILES['archivo_riesgo']['error'] !== 0) {
        $archivoRiesgo = $row['archivo_riesgo'];
    }
}


// Procesar campos comunes
$tema = isset($data['tema']) ? $conexion->real_escape_string($data['tema']) : '';
$fecha = isset($data['fecha']) ? $conexion->real_escape_string($data['fecha']) : '';
$descAnt = isset($data['descripcion_anterior']) ? $conexion->real_escape_string($data['descripcion_anterior']) : '';
$descMej = isset($data['descripcion_mejora']) ? $conexion->real_escape_string($data['descripcion_mejora']) : '';
$analisis = isset($data['analisis_riesgo']) ? intval($data['analisis_riesgo']) : 0;

// Procesar nuevas imágenes si se subieron
if (isset($_FILES['imagen_anterior']) && $_FILES['imagen_anterior']['error'] === 0) {
    $ruta = $uploadDir . basename($_FILES['imagen_anterior']['name']);
    if (move_uploaded_file($_FILES['imagen_anterior']['tmp_name'], $ruta)) {
        $imagenAnterior = $ruta;
    }
}

if (isset($_FILES['imagen_mejora']) && $_FILES['imagen_mejora']['error'] === 0) {
    $ruta = $uploadDir . basename($_FILES['imagen_mejora']['name']);
    if (move_uploaded_file($_FILES['imagen_mejora']['tmp_name'], $ruta)) {
        $imagenMejora = $ruta;
    }
}

if (isset($_FILES['archivo_riesgo']) && $_FILES['archivo_riesgo']['error'] === 0) {
    $tipo = mime_content_type($_FILES['archivo_riesgo']['tmp_name']);
    if ($tipo === 'application/pdf') {
        $ruta = $uploadDir . basename($_FILES['archivo_riesgo']['name']);
        if (move_uploaded_file($_FILES['archivo_riesgo']['tmp_name'], $ruta)) {
            $archivoRiesgo = $ruta;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Archivo de riesgo no es PDF']);
        exit;
    }
}

// Actualizar reporte
$sql = "UPDATE reportes SET
    tema = '$tema',
    fecha = '$fecha',
    descripcion_anterior = '$descAnt',
    descripcion_mejora = '$descMej',
    analisis_riesgo = $analisis,
    imagen_anterior = " . ($imagenAnterior ? "'$imagenAnterior'" : "NULL") . ",
    imagen_mejora = " . ($imagenMejora ? "'$imagenMejora'" : "NULL") . ",
    archivo_riesgo = " . ($archivoRiesgo ? "'$archivoRiesgo'" : "NULL") . "
    WHERE id = $idReporte";

if (!$conexion->query($sql)) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el reporte: ' . $conexion->error]);
    exit;
}

// Actualizar participantes si vienen
if (isset($data['participantes']) && $data['participantes']) {
    $participantes = json_decode($data['participantes'], true);

    if (is_array($participantes)) {
        // Primero eliminar los existentes
        $conexion->query("DELETE FROM reporte_participantes WHERE id_reporte = $idReporte");

        // Insertar nuevos
        foreach ($participantes as $p) {
            $idP = $conexion->real_escape_string($p['id']);
            $nom = $conexion->real_escape_string($p['nombre']);
            $dept = $conexion->real_escape_string($p['departamento']);

            // Validar campos no vacíos
            if (empty($idP) || empty($nom) || empty($dept)) {
                continue; 
            }

            $sqlP = "INSERT INTO reporte_participantes (id_reporte, id_participante, nombre, departamento)
                     VALUES ($idReporte, '$idP', '$nom', '$dept')";
            if (!$conexion->query($sqlP)) {
                echo json_encode(['success' => false, 'message' => 'Error al guardar participante: ' . $conexion->error]);
                exit;
            }
        }
    }
}

$conexion->close();
echo json_encode(['success' => true, 'message' => 'Reporte actualizado']);
