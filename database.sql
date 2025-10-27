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

DROP TABLE IF EXISTS categorias;
CREATE TABLE categorias (
                        id_categoria INT AUTO_INCREMENT PRIMARY KEY,
                        nombre VARCHAR(50) NOT NULL
);  

DROP TABLE IF EXISTS preguntas;
CREATE TABLE preguntas (
                      id_pregunta    INT AUTO_INCREMENT PRIMARY KEY,
                      pregunta       VARCHAR(150) NOT NULL,
                      id_usuario INT         NOT NULL ,
                      id_categoria INT NOT NULL,
                      FOREIGN KEY (id_usuario)
                          REFERENCES usuarios (id_usuario)
                          ON DELETE CASCADE
                          FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON DELETE RESTRICT
);

DROP TABLE IF EXISTS respuestas;
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


DROP TABLE IF EXISTS juegos;
CREATE TABLE juegos (
    id_juego INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    puntaje INT DEFAULT 0,
    estado ENUM('activo','perdido','finalizado') DEFAULT 'activo',
    iniciado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finalizado_en TIMESTAMP NULL,
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
);

DROP TABLE IF EXISTS juego_preguntas;
CREATE TABLE juego_preguntas (
    id_juego_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    id_juego INT NOT NULL,
    id_pregunta INT NOT NULL,
    id_usuario INT NOT NULL,
    id_respuesta_elegida INT NULL,
    es_correcta TINYINT(1) DEFAULT 0,
    usada_trampita TINYINT(1) DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_juego) REFERENCES juegos(id_juego) ON DELETE CASCADE,
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_respuesta_elegida) REFERENCES respuestas(id_respuesta) ON DELETE SET NULL
);

