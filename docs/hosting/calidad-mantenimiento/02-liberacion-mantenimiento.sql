-- Ejecutar UNA SOLA VEZ en la base propia de Producción.
ALTER TABLE mantenimiento_ordenes
 ADD COLUMN estado_liberacion VARCHAR(20) NULL,
 ADD COLUMN prueba_funcionamiento TEXT NULL,
 ADD COLUMN liberado_en DATE NULL,
 ADD COLUMN liberado_por BIGINT UNSIGNED NULL,
 ADD COLUMN liberado_nombre VARCHAR(255) NULL,
 ADD INDEX mantenimiento_ordenes_estado_liberacion_index (estado_liberacion);
