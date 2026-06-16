<?php
header('Content-Type: application/json');
require 'conexion.php';

$tema = $_POST['tema'];
$fecha = $_POST['fecha'];
$descripcion_anterior = $_POST['descripcion_anterior'];
$descripcion_mejora = $_POST['descripcion_mejora'];
$analisis_riesgo = $_POST['analisis_riesgo'];
$participantes = $_POST['participantes']; // JSON string

// Manejo de archivos
function guardarImagen($archivo, $nombreCampo) {
    if (isset($_FILES[$nombreCampo]) && $_FILES[$nombreCampo]['error'] === 0) {
        $ruta = 'uploads/' . uniqid() . '_' . basename($_FILES[$nombreCampo]['name']);
        if (move_uploaded_file($_FILES[$nombreCampo]['tmp_name'], $ruta)) {
            return $ruta;
        }
    }
    return null;
}

$img_anterior = guardarImagen($_FILES, 'imagen_anterior');
$img_mejora = guardarImagen($_FILES, 'imagen_mejora');

$sql = "INSERT INTO reportes 
        (tema, fecha, imagen_anterior, descripcion_anterior, imagen_mejora, descripcion_mejora, analisis_riesgo, participantes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "ssssssis", 
    $tema, $fecha, $img_anterior, $descripcion_anterior, $img_mejora, $descripcion_mejora, $analisis_riesgo, $participantes
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
