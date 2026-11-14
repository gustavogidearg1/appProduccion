-- Ejecutar una sola vez en la base propia de Producción, antes de subir los archivos.
ALTER TABLE calidad_hallazgos
 ADD COLUMN criterio_eficacia TEXT NULL,
 ADD COLUMN fecha_verificacion_prevista DATE NULL,
 ADD COLUMN resultado_eficacia TEXT NULL,
 ADD COLUMN verificado_en TIMESTAMP NULL DEFAULT NULL,
 ADD COLUMN verificado_por BIGINT UNSIGNED NULL;
