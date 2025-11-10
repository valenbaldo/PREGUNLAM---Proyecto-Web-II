<?php

class EditorModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerCategorias()
    {
        $sql = "SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC";
        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerTodasLasPreguntas(){
        $sql = "
            SELECT p.id_pregunta, p.pregunta, c.nombre AS categoria, r.es_correcta
            FROM preguntas p
            JOIN categorias c ON p.id_categoria = c.id_categoria
            JOIN respuestas r ON p.id_pregunta = r.id_pregunta
            ORDER BY p.id_pregunta DESC
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerPreguntaCompleta($id_pregunta)
    {
        $id = $id_pregunta;

        $sql = "
            SELECT p.id_pregunta, p.pregunta, p.id_categoria, 
                   r.a, r.b, r.c, r.d, r.es_correcta
            FROM preguntas p
            JOIN respuestas r ON p.id_pregunta = r.id_pregunta
            WHERE p.id_pregunta = $id
            LIMIT 1
        ";
        $resultado = $this->conexion->query($sql);

        return ($resultado && isset($resultado[0])) ? $resultado[0] : null;
    }

    public function guardar($datos)
    {
        $pregunta = addslashes($datos['pregunta']);
        $id_categoria = (int)$datos['id_categoria'];
        $correcta = strtoupper(substr($datos['respuesta_correcta'], 0, 1));

        $sql_pregunta = "
            INSERT INTO preguntas (pregunta, id_categoria, veces_respondida, veces_acertada)
            VALUES ('$pregunta', $id_categoria, 0, 0)
        ";
        $this->conexion->execute($sql_pregunta);

        $id_pregunta_insertada = $this->conexion->query("SELECT LAST_INSERT_ID() as id")[0]['id'];

        if (!$id_pregunta_insertada) {
            return false;
        }

        $sql_respuestas = "
            INSERT INTO respuestas (id_pregunta, a, b, c, d, es_correcta)
            VALUES (
                $id_pregunta_insertada,
                '" . addslashes($datos['a']) . "',
                '" . addslashes($datos['b']) . "',
                '" . addslashes($datos['c']) . "',
                '" . addslashes($datos['d']) . "',
                '$correcta'
            )
        ";
        return $this->conexion->execute($sql_respuestas);
    }

    public function actualizar($datos)
    {
        $id = $datos['id_pregunta'];
        $pregunta = addslashes($datos['pregunta']);
        $id_categoria = $datos['id_categoria'];
        $correcta = strtoupper(substr($datos['respuesta_correcta'], 0, 1));

        $sql_pregunta = "
            UPDATE preguntas 
            SET pregunta = '$pregunta', id_categoria = $id_categoria
            WHERE id_pregunta = $id
        ";
        $this->conexion->execute($sql_pregunta);

        $sql_respuestas = "
            UPDATE respuestas 
            SET a = '" . addslashes($datos['a']) . "',
                b = '" . addslashes($datos['b']) . "',
                c = '" . addslashes($datos['c']) . "',
                d = '" . addslashes($datos['d']) . "',
                es_correcta = '$correcta'
            WHERE id_pregunta = $id
        ";
        return $this->conexion->execute($sql_respuestas);
    }


    public function eliminar($id_pregunta)
    {
        $id = $id_pregunta;

        $this->conexion->execute("DELETE FROM juego_preguntas WHERE id_pregunta = $id");

        $this->conexion->execute("DELETE FROM respuestas WHERE id_pregunta = $id");

        $result = $this->conexion->execute("DELETE FROM preguntas WHERE id_pregunta = $id");

        return $result;
    }
}