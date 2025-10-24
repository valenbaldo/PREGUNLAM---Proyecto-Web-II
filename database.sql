CREATE DATABASE IF NOT EXISTS pregunlam;
USE pregunlam;

DROP TABLE IF EXISTS usuarios;


CREATE TABLE usuarios (
                          id_usuario INT AUTO_INCREMENT PRIMARY KEY,
                          nombre VARCHAR(100) NOT NULL,
                          apellido VARCHAR(100) NOT NULL,
                          usuario VARCHAR(100) NOT NULL,
                          mail VARCHAR(255) NOT NULL UNIQUE,
                          imagen VARCHAR(100) NOT NULL,
                          fecha_nacimiento DATE NOT NULL,
                          contraseña VARCHAR(255) NOT NULL,
                          token VARCHAR(255) NULL,
                          verificado TINYINT(1) NOT NULL DEFAULT 0,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS ubicacion;

CREATE TABLE ubicacion (
                           id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
                           pais VARCHAR(100) NULL,
                           ciudad VARCHAR(100) NULL,
                           id_usuario INT NOT NULL UNIQUE,
                           FOREIGN KEY (id_usuario)
                               REFERENCES usuarios(id_usuario)
                               ON DELETE CASCADE
);

DROP TABLE IF EXISTS sexo;

CREATE TABLE sexo (
                           id_sexo    INT AUTO_INCREMENT PRIMARY KEY,
                           sexo       VARCHAR(12) NOT NULL,
                           id_usuario INT         NOT NULL UNIQUE,
                           FOREIGN KEY (id_usuario)
                               REFERENCES usuarios (id_usuario)
                               ON DELETE CASCADE
);

CREATE TABLE preguntas (
                      id_pregunta    INT AUTO_INCREMENT PRIMARY KEY,
                      pregunta       VARCHAR(150) NOT NULL,
                      id_usuario INT         NOT NULL UNIQUE,
                      FOREIGN KEY (id_usuario)
                          REFERENCES usuarios (id_usuario)
                          ON DELETE CASCADE
);

CREATE TABLE respuestas (
                      id_respuesta    INT AUTO_INCREMENT PRIMARY KEY,
                      a VARCHAR(150) NOT NULL,
                      b VARCHAR(150) NOT NULL,
                      c VARCHAR(150) NOT NULL,
                      d VARCHAR(150) NOT NULL,
                      es_correcta CHAR(1) NOT NULL,
                      id_pregunta INT NOT NULL UNIQUE,
                      FOREIGN KEY (id_pregunta)
                          REFERENCES preguntas (id_pregunta)
                          ON DELETE CASCADE
);


