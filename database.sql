CREATE DATABASE IF NOT EXISTS pregunlam;
USE pregunlam;


DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
                       id_rol INT PRIMARY KEY,
                       nombre VARCHAR(50) NOT NULL UNIQUE
);
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
                          id_rol INT NOT NULL DEFAULT 1,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
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
                           veces_respondida INT DEFAULT 0,
                           veces_acertada INT DEFAULT 0,
                           FOREIGN KEY (id_usuario)
                               REFERENCES usuarios (id_usuario)
                               ON DELETE CASCADE,
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
DROP TABLE IF EXISTS reportes;
CREATE TABLE reportes (
                          id_reporte INT AUTO_INCREMENT PRIMARY KEY,
                          id_pregunta INT NOT NULL,
                          id_usuario_reporta INT NOT NULL,
                          descripcion TEXT NOT NULL,
                          estado ENUM('pendiente', 'revisado', 'desestimado') DEFAULT 'pendiente',
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                          FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta) ON DELETE CASCADE,
                          FOREIGN KEY (id_usuario_reporta) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

------INSERTS------

INSERT INTO roles (id_rol, nombre) VALUES
                                       (1, 'Jugador'),
                                       (2, 'Editor'),
                                       (3, 'Administrador');

INSERT INTO categorias (nombre) VALUES
                                    ('Ingeniería e Investigaciones Tecnológicas'),
                                    ('Humanidades y Ciencias Sociales'),
                                    ('Ciencias Económicas'),
                                    ('Derecho y Ciencia Política'),
                                    ('Ciencias de la Salud');


----INGENIERIA-----

-- Pregunta 1
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué lenguaje se utiliza principalmente para desarrollar aplicaciones Android?', 1, 1);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Python', 'Java', 'C#', 'Swift', 'b', LAST_INSERT_ID());

-- Pregunta 2
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué significa HTML?', 1, 1);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('HyperText Markup Language', 'HighText Machine Language', 'Hyper Transfer Main Link', 'HyperText Manage Logic', 'a', LAST_INSERT_ID());

-- Pregunta 3
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Cuál es la unidad básica de almacenamiento en una base de datos relacional?', 1, 1);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Campo', 'Tabla', 'Registro', 'Clave primaria', 'b', LAST_INSERT_ID());

-- Pregunta 4
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué tipo de estructura de datos utiliza el formato JSON?', 1, 1);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Gráfico', 'Texto plano', 'Árbol de pares clave-valor', 'Tabla relacional', 'c', LAST_INSERT_ID());

-- Pregunta 5
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué protocolo se usa para transferir datos de una página web?', 1, 1);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('HTTP', 'FTP', 'SMTP', 'SSH', 'a', LAST_INSERT_ID());


----HUMANIDADES-----

-- Pregunta 1
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Quién es considerado el padre del psicoanálisis?', 1, 2);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Carl Jung', 'Sigmund Freud', 'Jean Piaget', 'Erik Erikson', 'b', LAST_INSERT_ID());

-- Pregunta 2
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué estudia la sociología?', 1, 2);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('El comportamiento biológico', 'Las relaciones humanas y la sociedad', 'El lenguaje y la comunicación', 'Los fenómenos geológicos', 'b', LAST_INSERT_ID());

-- Pregunta 3
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué filósofo escribió “La República”?', 1, 2);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Aristóteles', 'Platón', 'Socrátes', 'Descartes', 'b', LAST_INSERT_ID());

-- Pregunta 4
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué disciplina analiza los significados de las palabras?', 1, 2);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Semántica', 'Pragmática', 'Sintaxis', 'Morfología', 'a', LAST_INSERT_ID());

-- Pregunta 5
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué corriente artística impulsó Salvador Dalí?', 1, 2);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Cubismo', 'Surrealismo', 'Impresionismo', 'Expresionismo', 'b', LAST_INSERT_ID());


----CIENCIAS ECONOMICAS----

-- Pregunta 1
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué es el PIB?', 1, 3);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Producto Interno Bruto', 'Precio Industrial Básico', 'Programa de Inversión Bancaria', 'Promedio Interbancario Base', 'a', LAST_INSERT_ID());

-- Pregunta 2
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué estudia la microeconomía?', 1, 3);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('El comportamiento de los mercados individuales', 'El crecimiento global de un país', 'La economía internacional', 'El gasto público', 'a', LAST_INSERT_ID());

-- Pregunta 3
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué es un activo?', 1, 3);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Un bien o derecho con valor económico', 'Una deuda financiera', 'Un gasto corriente', 'Una obligación contable', 'a', LAST_INSERT_ID());

-- Pregunta 4
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Cuál de las siguientes es una variable macroeconómica?', 1, 3);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Demanda individual', 'Inflación', 'Precio de un producto', 'Costo marginal', 'b', LAST_INSERT_ID());

-- Pregunta 5
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué representa la oferta y la demanda?', 1, 3);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('La relación entre producción y consumo', 'El equilibrio del mercado', 'El comportamiento del Estado', 'Los ingresos públicos', 'b', LAST_INSERT_ID());



----DERECHO-----

-- Pregunta 1
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué poder del Estado se encarga de hacer las leyes?', 1, 4);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Ejecutivo', 'Legislativo', 'Judicial', 'Constitucional', 'b', LAST_INSERT_ID());

-- Pregunta 2
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué es la Constitución Nacional?', 1, 4);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Un código penal', 'Una ley provincial', 'La norma fundamental del Estado', 'Un reglamento interno', 'c', LAST_INSERT_ID());

-- Pregunta 3
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Quién redacta los fallos judiciales?', 1, 4);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('El Congreso', 'El Presidente', 'El Juez', 'El Senado', 'c', LAST_INSERT_ID());

-- Pregunta 4
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué sistema político tiene Argentina?', 1, 4);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Monarquía parlamentaria', 'República federal', 'Dictadura presidencial', 'Estado unitario', 'b', LAST_INSERT_ID());

-- Pregunta 5
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué función cumple el Poder Judicial?', 1, 4);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Interpretar y aplicar las leyes', 'Crear normas', 'Administrar los ministerios', 'Aprobar el presupuesto', 'a', LAST_INSERT_ID());


----CIENCIAS DE LA SALUD-----

-- Pregunta 1
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Cuál es el órgano encargado de bombear la sangre?', 1, 5);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Pulmones', 'Corazón', 'Hígado', 'Riñones', 'b', LAST_INSERT_ID());

-- Pregunta 2
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué especialidad médica estudia el cerebro?', 1, 5);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Cardiología', 'Neurología', 'Dermatología', 'Endocrinología', 'b', LAST_INSERT_ID());

-- Pregunta 3
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué vitamina se obtiene principalmente del sol?', 1, 5);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('Vitamina A', 'Vitamina D', 'Vitamina C', 'Vitamina B12', 'b', LAST_INSERT_ID());

-- Pregunta 4
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Qué tipo de sangre es el donante universal?', 1, 5);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('A+', 'O-', 'B-', 'AB+', 'b', LAST_INSERT_ID());

-- Pregunta 5
INSERT INTO preguntas (pregunta, id_usuario, id_categoria)
VALUES ('¿Cuántos huesos tiene el cuerpo humano adulto?', 1, 5);
INSERT INTO respuestas (a, b, c, d, es_correcta, id_pregunta)
VALUES ('206', '208', '210', '202', 'a', LAST_INSERT_ID());

ALTER TABLE juego_preguntas
    ADD COLUMN opcion_elegida VARCHAR(10) DEFAULT NULL COMMENT 'Opción elegida por el usuario (A,B,C,D,TIMEOUT)';

ALTER TABLE juego_preguntas
    ADD COLUMN tiempo_respuesta INT DEFAULT 0 COMMENT 'Tiempo en segundos que tardó el usuario en responder';

ALTER TABLE preguntas
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;