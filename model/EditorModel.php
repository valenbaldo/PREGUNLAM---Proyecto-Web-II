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
        SELECT DISTINCT p.id_pregunta, p.pregunta, c.nombre AS categoria, r.es_correcta
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

    public function guardar($datos, $id_usuario)
    {
        $pregunta = addslashes($datos['pregunta']);
        $id_categoria = (int)$datos['id_categoria'];
        $correcta = strtoupper(substr($datos['respuesta_correcta'], 0, 1));

        $sql_pregunta = "
            INSERT INTO preguntas (pregunta, id_usuario, id_categoria, veces_respondida, veces_acertada)
            VALUES ('$pregunta', $id_usuario, $id_categoria, 0, 0)
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
        $this->conexion->execute($sql_respuestas);

        return true;
    }

    public function actualizar($datos)
    {
        $id = $datos['id_pregunta'];
        $pregunta = addslashes($datos['pregunta']);
        $id_categoria = $datos['id_categoria'];
        $correcta = strtoupper(substr($datos['respuesta_correcta'], 0, 1));

        try{
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
            $this->conexion->execute($sql_respuestas);
            return true;
        }
        catch(Exception $e){
            return false;
        }


    }


    public function eliminar($id_pregunta)
    {
        $id = $id_pregunta;

        try{
            $this->conexion->execute("DELETE FROM juego_preguntas WHERE id_pregunta = $id");

            $this->conexion->execute("DELETE FROM respuestas WHERE id_pregunta = $id");

            $this->conexion->execute("DELETE FROM preguntas WHERE id_pregunta = $id");

            return true;
        }
        catch(Exception $e){
            return false;
        }
    }
    public function obtenerReportesPendientes()
    {
        $sql = "SELECT 
                r.id_reporte,
                r.id_pregunta,
                r.descripcion,
                r.estado,
                r.created_at,
                p.pregunta AS nombre_pregunta,
                u.usuario AS reportado_por 
            FROM 
                reportes r
            JOIN 
                preguntas p ON r.id_pregunta = p.id_pregunta
            JOIN 
                usuarios u ON r.id_usuario_reporta = u.id_usuario
            WHERE 
                r.estado = 'pendiente'
            ORDER BY 
                r.created_at DESC";

        try{
            $this->conexion->query($sql);
            return true;
        }
        catch(Exception $e){
            return false;
        }
    }
    public function actualizarEstadoReporte(int $id_reporte, string $nuevo_estado)
    {
        $id = intval($id_reporte);
        $estado = $nuevo_estado;

        try{
            $sql = "UPDATE reportes SET estado = '$estado', resuelto_en = NOW() WHERE id_reporte = $id";

            $this->conexion->execute($sql);

            return true;
        }
        catch(Exception $e){
            return false;
        }

    }
    public function contarReportesPendientes()
    {
        try{
            $sql = "SELECT COUNT(*) AS total FROM reportes WHERE estado = 'pendiente'";
            $resultado = $this->conexion->query($sql);

            return $resultado[0]['total'] ?? 0;
        }
        catch(Exception $e){
            return null;
        }

    }
}