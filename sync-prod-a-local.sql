-- Sincronización incremental: empleados_ntn_prod -> empleados_ntn
-- Solo registros que no existen en local (por PK)

USE empleados_ntn;

START TRANSACTION;

-- 1. Empleados faltantes
INSERT INTO bd_ntn (
    EmpId, FIrstName, LastName, SurName, Department,
    Pass, cambiar_contrasena, pass_encriptada, activo
)
SELECT
    p.EmpId, p.FIrstName, p.LastName, p.SurName, p.Department,
    p.Pass, p.cambiar_contrasena, p.pass_encriptada, 1
FROM empleados_ntn_prod.bd_ntn p
WHERE NOT EXISTS (
    SELECT 1 FROM bd_ntn l WHERE l.EmpId = p.EmpId
);

-- 2. Reportes faltantes
INSERT INTO reportes (
    id, tema, fecha, imagen_anterior, descripcion_anterior,
    imagen_mejora, descripcion_mejora, analisis_riesgo,
    estadoRH, estadoSupervisor, estadoGerente, archivo_riesgo,
    razon_rechazo_rh, estado, fecha_creacion, fecha_finalizacion, exportado
)
SELECT
    p.id, p.tema, p.fecha, p.imagen_anterior, p.descripcion_anterior,
    p.imagen_mejora, p.descripcion_mejora, p.analisis_riesgo,
    p.estadoRH, p.estadoSupervisor, p.estadoGerente, p.archivo_riesgo,
    p.razon_rechazo_rh, p.estado, p.fecha_creacion, p.fecha_finalizacion, p.exportado
FROM empleados_ntn_prod.reportes p
WHERE NOT EXISTS (
    SELECT 1 FROM reportes l WHERE l.id = p.id
);

-- 3. Participantes faltantes
INSERT INTO reporte_participantes (
    id, id_reporte, id_participante, nombre, departamento
)
SELECT
    p.id, p.id_reporte, p.id_participante, p.nombre, p.departamento
FROM empleados_ntn_prod.reporte_participantes p
WHERE NOT EXISTS (
    SELECT 1 FROM reporte_participantes l WHERE l.id = p.id
);

-- 4. Evaluaciones faltantes
INSERT INTO evaluaciones (
    id, id_reporte, clasificacion, aspectos_evaluados, fecha
)
SELECT
    p.id, p.id_reporte, p.clasificacion, p.aspectos_evaluados, p.fecha
FROM empleados_ntn_prod.evaluaciones p
WHERE NOT EXISTS (
    SELECT 1 FROM evaluaciones l WHERE l.id = p.id
);

-- 5. Tokens de reset faltantes
INSERT INTO tokens_reset (
    id, EmpId, token, expiracion, usado, creado_en
)
SELECT
    p.id, p.EmpId, p.token, p.expiracion, p.usado, p.creado_en
FROM empleados_ntn_prod.tokens_reset p
WHERE NOT EXISTS (
    SELECT 1 FROM tokens_reset l WHERE l.id = p.id
);

-- Ajustar AUTO_INCREMENT
SET @max_reportes = (SELECT IFNULL(MAX(id), 0) + 1 FROM reportes);
SET @sql = CONCAT('ALTER TABLE reportes AUTO_INCREMENT = ', @max_reportes);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @max_part = (SELECT IFNULL(MAX(id), 0) + 1 FROM reporte_participantes);
SET @sql = CONCAT('ALTER TABLE reporte_participantes AUTO_INCREMENT = ', @max_part);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @max_eval = (SELECT IFNULL(MAX(id), 0) + 1 FROM evaluaciones);
SET @sql = CONCAT('ALTER TABLE evaluaciones AUTO_INCREMENT = ', @max_eval);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @max_tok = (SELECT IFNULL(MAX(id), 0) + 1 FROM tokens_reset);
SET @sql = CONCAT('ALTER TABLE tokens_reset AUTO_INCREMENT = ', @max_tok);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
