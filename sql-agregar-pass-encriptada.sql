-- Agregar columna para indicar si la contraseña está encriptada
ALTER TABLE bd_ntn 
ADD COLUMN IF NOT EXISTS pass_encriptada TINYINT(1) DEFAULT 0 COMMENT 'Indica si Pass está encriptada con password_hash';

-- Crear índice
CREATE INDEX IF NOT EXISTS idx_pass_encriptada ON bd_ntn(pass_encriptada);
