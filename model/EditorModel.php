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

    public function obtenerTodasCategorias()
    {
        $sql = "SELECT c.id_categoria, c.nombre, COUNT(p.id_pregunta) as total_preguntas 
                FROM categorias c 
                LEFT JOIN preguntas p ON c.id_categoria = p.id_categoria 
                GROUP BY c.id_categoria, c.nombre 
                ORDER BY c.nombre ASC";
        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerCategoriaPorId($id_categoria)
    {
        $id = intval($id_categoria);
        $sql = "SELECT id_categoria, nombre FROM categorias WHERE id_categoria = $id LIMIT 1";
        $resultado = $this->conexion->query($sql);
        return $resultado ? $resultado[0] : null;
    }

    public function crearCategoria($nombre)
    {
        $nombre_escapado = addslashes($nombre);

        try {
            $sql = "INSERT INTO categorias (nombre) VALUES ('$nombre_escapado')";
            $this->conexion->execute($sql);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function actualizarCategoria($id_categoria, $nombre)
    {
        $id = intval($id_categoria);
        $nombre_escapado = addslashes($nombre);

        try {
            $sql = "UPDATE categorias SET nombre = '$nombre_escapado' WHERE id_categoria = $id";
            $this->conexion->execute($sql);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function eliminarCategoria($id_categoria)
    {
        $id = intval($id_categoria);

        try {
            $sql_check = "SELECT COUNT(*) as total FROM preguntas WHERE id_categoria = $id";
            $resultado = $this->conexion->query($sql_check);

            if ($resultado && $resultado[0]['total'] > 0) {
                return 'restriccion';
            }

            $sql = "DELETE FROM categorias WHERE id_categoria = $id";
            $this->conexion->execute($sql);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    public function obtenerSugerenciasPendientes()
    {
        $sql = "
            SELECT
                sp.id_sugerencia,
                sp.pregunta,
                sp.opcion_a,
                sp.opcion_b,
                sp.opcion_c,
                sp.opcion_d,
                sp.respuesta_correcta,
                sp.fecha_sugerencia,
                u.usuario AS sugerida_por,
                COALESCE(c.nombre, 'Sin Categoría') AS categoria,
                sp.id_categoria
            FROM
                sugerencias_preguntas sp
            JOIN
                usuarios u ON sp.id_usuario_sugiere = u.id_usuario
            LEFT JOIN
                categorias c ON sp.id_categoria = c.id_categoria
            WHERE
                sp.estado = 'pendiente'
            ORDER BY
                sp.fecha_sugerencia DESC
        ";

        return $this->conexion->query($sql) ?? [];
    }

    public function contarSugerenciasPendientes()
    {
        try{
            $sql = "SELECT COUNT(*) AS total FROM sugerencias_preguntas WHERE estado = 'pendiente'";
            $resultado = $this->conexion->query($sql);

            return $resultado[0]['total'] ?? 0;
        }
        catch(Exception $e){
            error_log("Error al contar sugerencias pendientes: " . $e->getMessage());
            return 0;
        }
    }

    public function rechazarSugerencia(int $id_sugerencia): bool
    {
        $id = intval($id_sugerencia);
        $estado = 'rechazada';

        try{
            $sql = "UPDATE sugerencias_preguntas SET estado = '$estado' WHERE id_sugerencia = $id";
            $this->conexion->execute($sql);
            return true;
        }
        catch(Exception $e){
            error_log("Error al rechazar sugerencia: " . $e->getMessage());
            return false;
        }
    }

    public function aceptarSugerencia(int $id_sugerencia, int $id_usuario_editor): bool
    {
        $id = intval($id_sugerencia);
        $id_pregunta_insertada = null;

        try {
            $sql_fetch = "
                SELECT pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, id_categoria
                FROM sugerencias_preguntas
                WHERE id_sugerencia = $id AND estado = 'pendiente'
                LIMIT 1
            ";
            $sugerencia = $this->conexion->query($sql_fetch);

            if (empty($sugerencia)) {
                throw new Exception("Sugerencia no encontrada o ya procesada.");
            }

            $datos = $sugerencia[0];
            $pregunta = addslashes($datos['pregunta']);
            $id_categoria = (int)($datos['id_categoria'] ?? 0);
            $correcta = strtoupper(substr($datos['respuesta_correcta'], 0, 1));

            $sql_pregunta = "
                INSERT INTO preguntas (pregunta, id_usuario, id_categoria, veces_respondida, veces_acertada)
                VALUES ('$pregunta', $id_usuario_editor, $id_categoria, 0, 0)
            ";
            $this->conexion->execute($sql_pregunta);

            $id_pregunta_query = $this->conexion->query("SELECT LAST_INSERT_ID() as id");
            $id_pregunta_insertada = $id_pregunta_query[0]['id'] ?? null;

            if (!$id_pregunta_insertada) {
                throw new Exception("Falló la obtención del ID después de insertar la pregunta.");
            }

            $sql_respuestas = "
                INSERT INTO respuestas (id_pregunta, a, b, c, d, es_correcta)
                VALUES (
                    $id_pregunta_insertada,
                    '" . addslashes($datos['opcion_a']) . "',
                    '" . addslashes($datos['opcion_b']) . "',
                    '" . addslashes($datos['opcion_c']) . "',
                    '" . addslashes($datos['opcion_d']) . "',
                    '$correcta'
                )
            ";
            $this->conexion->execute($sql_respuestas);

            $sql_update_sugerencia = "
                UPDATE sugerencias_preguntas
                SET estado = 'aceptada', id_pregunta_asociada = $id_pregunta_insertada
                WHERE id_sugerencia = $id
            ";
            $this->conexion->execute($sql_update_sugerencia);

            return true;

        } catch (Exception $e) {
            if ($id_pregunta_insertada) {
                $this->eliminar($id_pregunta_insertada);
            }

            error_log("Error al aceptar sugerencia (FALLO DE SQL/LIMPIEZA): " . $e->getMessage());
            return false;
        }
    }
}