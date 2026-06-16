<?php
require 'conexion.php';
require_once __DIR__ . '/clasificacion-empleado.php';

instalarColumnaClasificacion($conexion);
$limpiados = limpiarClasificacionNoTrabajadores($conexion);

echo "Columna clasificacion agregada a bd_ntn.\n";
echo "Valores: staff, operativo, inspector (solo trabajadores).\n";
echo "Registros no trabajador limpiados: {$limpiados}.\n";

$conexion->close();
