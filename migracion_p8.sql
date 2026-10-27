USE agenda;
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

SELECT id, nombre, correo, clave_hash FROM usuarios;

ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;

UPDATE eventos SET usuario_id = 1 WHERE id > 0;   -- tus eventos de antes ahora son tuyos

ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
      ON DELETE CASCADE;

DESCRIBE eventos;