-- Tabla de eventos de AgendaWeb.
-- Se importa DENTRO de una base que ya existe (no la crea), porque en DOM Cloud
-- la base la crea el hosting y no se llama "agenda".
--
-- En tu computadora, primero crea la base y luego importa este archivo:
--   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS agenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -u root -p agenda < db.sql

CREATE TABLE IF NOT EXISTS eventos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  titulo      VARCHAR(120) NOT NULL,
  fecha       DATE NOT NULL,
  hora        TIME NULL,
  categoria   VARCHAR(20) NOT NULL,
  descripcion VARCHAR(500) NULL,
  creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
