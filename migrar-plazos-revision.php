<?php
require 'conexion.php';
require_once __DIR__ . '/includes/PlazoRevision.php';

PlazoRevision::instalar($conexion);
PlazoRevision::retroalimentar($conexion);

echo "Migración de plazos de revisión completada.\n";

$conexion->close();
