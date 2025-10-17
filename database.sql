CREATE DATABASE IF NOT EXISTS pregunlam;
USE pregunlam;

DROP TABLE IF EXISTS usuarios;


CREATE TABLE usuarios (
                          id_usuario INT AUTO_INCREMENT PRIMARY KEY,
                          nombre VARCHAR(100) NOT NULL,
                          apellido VARCHAR(100) NOT NULL,
                          mail VARCHAR(255) NOT NULL UNIQUE,
                          pais VARCHAR(100) NULL,
                          ciudad VARCHAR(100) NULL,
                          contraseña VARCHAR(255) NOT NULL,
                          token VARCHAR(255) NULL,
                          verificado TINYINT(1) NOT NULL DEFAULT 0,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
