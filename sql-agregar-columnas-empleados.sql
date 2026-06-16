-- Script para agregar columnas de gestión de empleados a la tabla bd_ntn
-- Ejecutar este script en la base de datos

-- Agregar columna activo (1 = activo, 0 = inactivo)
ALTER TABLE bd_ntn 
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1 COMMENT 'Estado del empleado: 1=Activo, 0=Inactivo';

-- Agregar columna fecha_baja
ALTER TABLE bd_ntn 
ADD COLUMN IF NOT EXISTS fecha_baja DATETIME NULL COMMENT 'Fecha en que se dio de baja al empleado';

-- Agregar columna motivo_baja
ALTER TABLE bd_ntn 
ADD COLUMN IF NOT EXISTS motivo_baja TEXT NULL COMMENT 'Motivo de la baja del empleado';

-- Actualizar todos los empleados existentes como activos
UPDATE bd_ntn SET activo = 1 WHERE activo IS NULL;

-- Crear índice para mejorar consultas por estado
CREATE INDEX IF NOT EXISTS idx_activo ON bd_ntn(activo);

-- Verificar cambios
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'bd_ntn' 
AND COLUMN_NAME IN ('activo', 'fecha_baja', 'motivo_baja');
