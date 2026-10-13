-- migracion_p5.sql · Normalizar categorías (P5)
-- Ejecuta cada bloque UNA sola vez, en orden, y revisa el resultado antes de seguir.
-- EN DOMCLOUD: borra o comenta la línea "USE agenda;" y elige tu base en phpMyAdmin
-- (allá la base no se llama "agenda").

USE agenda;

-- =====================================================================
-- PASO 0 · Respaldo
-- (LIKE + INSERT copia también la llave primaria y el AUTO_INCREMENT;
--  "CREATE TABLE ... AS SELECT" copia solo los datos.)
-- =====================================================================
CREATE TABLE eventos_respaldo LIKE eventos;
INSERT INTO eventos_respaldo SELECT * FROM eventos;

-- Ambos números deben ser iguales:
SELECT COUNT(*) AS en_eventos   FROM eventos;
SELECT COUNT(*) AS en_respaldo  FROM eventos_respaldo;

-- Si algo sale muy mal (descomenta y ejecuta):
-- DROP TABLE eventos;
-- RENAME TABLE eventos_respaldo TO eventos;

-- =====================================================================
-- PASO 1 · Tabla de categorías
-- =====================================================================
CREATE TABLE categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'),
  (2, 'Personal'),
  (3, 'Estudio'),
  (4, 'Ocio / Deporte');

SELECT * FROM categorias;

-- =====================================================================
-- PASO 2 · Migrar los eventos sin perder datos
-- =====================================================================

-- a) Nueva columna, vacía por ahora
ALTER TABLE eventos ADD COLUMN categoria_id INT NULL AFTER categoria;

-- b) Traducir el texto viejo a su número
UPDATE eventos
   SET categoria_id = CASE categoria
         WHEN 'trabajo'  THEN 1
         WHEN 'personal' THEN 2
         WHEN 'estudio'  THEN 3
         WHEN 'ocio'     THEN 4
       END
 WHERE id > 0;

-- c) ¿Quedó alguno sin traducir? Debe salir VACÍO.
--    Si no sale vacío, corrige a mano (ej. UPDATE eventos SET categoria_id = 2 WHERE id = 7;)
--    y repite este SELECT. NO sigas al paso d) hasta que salga vacío.
SELECT id, titulo, categoria FROM eventos WHERE categoria_id IS NULL;

-- d) Obligatoria y conectada con categorias (llave foránea)
ALTER TABLE eventos
  MODIFY categoria_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_categoria
      FOREIGN KEY (categoria_id) REFERENCES categorias(id)
      ON DELETE RESTRICT;

-- e) La columna de texto ya no hace falta
ALTER TABLE eventos DROP COLUMN categoria;

DESCRIBE eventos;