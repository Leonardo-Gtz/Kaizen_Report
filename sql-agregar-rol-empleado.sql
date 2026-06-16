-- Agregar columna de puesto/rol editable para empleados
-- Ejecutar en la base de datos antes de usar la edición de puesto

ALTER TABLE bd_ntn
ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NULL COMMENT 'Puesto: rh, gerente, supervisor, trabajador';

CREATE INDEX IF NOT EXISTS idx_bd_ntn_rol ON bd_ntn(rol);
