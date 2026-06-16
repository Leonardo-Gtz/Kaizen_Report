<?php
require 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';

MetasDepartamento::asegurarEsquema($conexion);
MetasDepartamento::sembrarHr($conexion);

echo "Migración de metas por departamento completada.\n";
echo "HR sembrado con meta " . MetasDepartamento::META_HR_DEFECTO . " (un registro por departamento).\n";

$conexion->close();
