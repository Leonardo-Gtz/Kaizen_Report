<?php
require 'conexion.php';

// Agregar campo razon_rechazo si no existe
$sql = "ALTER TABLE reportes ADD COLUMN IF NOT EXISTS razon_rechazo TEXT NULL AFTER estadoSupervisor";

if ($conexion->query($sql)) {
    echo "Campo 'razon_rechazo' agregado exitosamente (o ya existía)";
} else {
    echo "Error: " . $conexion->error;
}
?>
