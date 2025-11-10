<?php

class JuegoModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function iniciarJuego($id_usuario)
    {
        $id_usuario = intval($id_usuario);
        $this->conexion->execute("INSERT INTO juegos (id_usuario, puntaje, estado, iniciado_en) VALUES ($id_usuario, 0, 'activo', NOW())");
        $result = $this->conexion->query("SELECT LAST_INSERT_ID() as id_juego");
        return $result[0]['id_juego'];
    }
    public function obtenerPreguntaPorNivel($id_usuario, $nivel_usuario, $id_juego = null)
    {
        $id_usuario = intval($id_usuario);
        if (is_string($nivel_usuario)) {
            switch($nivel_usuario) {
                case 'facil': $nivel_usuario = 1; break;
                case 'intermedia': $nivel_usuario = 4; break;
                case 'dificil': $nivel_usuario = 7; break;
                default: $nivel_usuario = 1;
            }
        } else {
            $nivel_usuario = intval($nivel_usuario);
        }
        $filtro_excluir_historicas = "
        SELECT jp.id_pregunta 
        FROM juego_preguntas jp 
        WHERE jp.id_usuario = $id_usuario AND jp.id_respuesta_elegida IS NOT NULL
    ";

        $sql = "SELECT p.id_pregunta, p.pregunta, p.id_categoria, r.a, r.b, r.c, r.d, r.es_correcta, c.nombre as categoria,
            COALESCE(
                (SELECT COUNT(*) FROM juego_preguntas jp2 WHERE jp2.id_pregunta = p.id_pregunta AND jp2.es_correcta = 1), 0
            ) as aciertos,
            COALESCE(
                (SELECT COUNT(*) FROM juego_preguntas jp3 WHERE jp3.id_pregunta = p.id_pregunta AND jp3.id_respuesta_elegida IS NOT NULL), 0
            ) as total_respuestas,
            CASE 
                WHEN (SELECT COUNT(*) FROM juego_preguntas jp4 WHERE jp4.id_pregunta = p.id_pregunta AND jp4.id_respuesta_elegida IS NOT NULL) = 0 THEN 0.5
                ELSE (SELECT COUNT(*) FROM juego_preguntas jp5 WHERE jp5.id_pregunta = p.id_pregunta AND jp5.es_correcta = 1) / 
                     (SELECT COUNT(*) FROM juego_preguntas jp6 WHERE jp6.id_pregunta = p.id_pregunta AND jp6.id_respuesta_elegida IS NOT NULL)
            END as dificultad_ratio
            FROM preguntas p
            JOIN respuestas r ON r.id_pregunta = p.id_pregunta
            JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.id_pregunta NOT IN ($filtro_excluir_historicas)
            AND p.id_pregunta NOT IN (
                SELECT jp2.id_pregunta 
                FROM juego_preguntas jp2 
                WHERE jp2.id_juego = $id_juego AND jp2.id_respuesta_elegida IS NULL
            )

            HAVING (
                (dificultad_ratio >= 0.7 AND $nivel_usuario <= 2) OR
                (dificultad_ratio >= 0.4 AND dificultad_ratio < 0.7 AND $nivel_usuario BETWEEN 3 AND 5) OR
                (dificultad_ratio < 0.4 AND $nivel_usuario >= 6) OR
                (total_respuestas < 3)
            )
            ORDER BY RAND() LIMIT 1";

        $resultado = $this->conexion->query($sql);

        if (!$resultado || !is_array($resultado) || count($resultado) === 0) {

            $sql_fallback = "
            SELECT p.id_pregunta, p.pregunta, p.id_categoria, r.a, r.b, r.c, r.d, r.es_correcta, c.nombre as categoria
            FROM preguntas p
            JOIN respuestas r ON r.id_pregunta = p.id_pregunta
            JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.id_pregunta NOT IN (
                SELECT jp3.id_pregunta 
                FROM juego_preguntas jp3 
                WHERE jp3.id_juego = $id_juego AND jp3.id_respuesta_elegida IS NULL
            )
            ORDER BY RAND() LIMIT 1";

            $resultado = $this->conexion->query($sql_fallback);

            if (!$resultado || !is_array($resultado) || count($resultado) === 0) {
                return null;
            }
        }

        $pregunta = $resultado[0];

        if ($id_juego) {
            $this->registrarPreguntaMostrada($id_juego, $id_usuario, $pregunta['id_pregunta']);
        }

        return [
            'id_pregunta' => $pregunta['id_pregunta'],
            'pregunta' => $pregunta['pregunta'],
            'categoria' => $pregunta['categoria'],
            'opciones' => [
                'A' => $pregunta['a'],
                'B' => $pregunta['b'],
                'C' => $pregunta['c'],
                'D' => $pregunta['d']
            ],
            'respuesta_correcta' => $pregunta['es_correcta']
        ];
    }


    public function girarRuleta(int $id_juego, int $id_usuario, string $nivel_usuario = 'facil')
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);

        $g = $this->conexion->query("SELECT * FROM juegos WHERE id_juego = $id_juego LIMIT 1");
        if (!$g || !isset($g[0])) {
            return ['error' => 'Partida no encontrada'];
        }
        if ((int)$g[0]['id_usuario'] !== $id_usuario) {
            return ['error' => 'No autorizado para jugar esta partida'];
        }

        $pregunta = $this->obtenerPreguntaPorNivel($id_usuario, $nivel_usuario, $id_juego);
        
        if (!$pregunta) {
            return ['error' => '🎉 ¡Felicitaciones! Has visto todas las preguntas disponibles para tu nivel.'];
        }

        return ['pregunta' => $pregunta];
    }

    public function procesarRespuesta(int $id_juego, int $id_usuario, int $id_pregunta, string $opcion, int $tiempo_respuesta = 0)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        $id_pregunta = intval($id_pregunta);
        $tiempo_respuesta = intval($tiempo_respuesta);
        
        if ($tiempo_respuesta > 10) {
            return ['error' => 'Tiempo de respuesta excedido'];
        }
        
        $esTimeout = empty($opcion);
        if (!$esTimeout) {
            $opcion = strtoupper(substr($opcion, 0, 1));
        }

        $rows = $this->conexion->query("SELECT * FROM juego_preguntas WHERE id_juego = $id_juego AND id_pregunta = $id_pregunta AND id_usuario = $id_usuario ORDER BY id_juego_pregunta DESC LIMIT 1");
        if (!$rows || count($rows) === 0) {
            return ['error' => 'Pregunta no autorizada para esta partida'];
        }
        $jp = $rows[0];

        if ($jp['id_respuesta_elegida'] !== null) {
            return ['error' => 'La pregunta ya fue respondida'];
        }

        $r = $this->conexion->query("SELECT id_respuesta, es_correcta FROM respuestas WHERE id_pregunta = $id_pregunta");
        if (!$r || !isset($r[0]['es_correcta'])) {
            return ['error' => 'No se encontró la respuesta correcta para la pregunta'];
        }
        $correct = strtoupper($r[0]['es_correcta']);
        $id_respuesta = $r[0]['id_respuesta'];
        
        if ($esTimeout) {
            $isCorrect = 0;
            $opcion_final = 'TIMEOUT';
        } else {
            $isCorrect = ($opcion === $correct) ? 1 : 0;
            $opcion_final = $opcion;
        }
        
        $checkOpc = $this->conexion->query("SHOW COLUMNS FROM juego_preguntas LIKE 'opcion_elegida'");
        $checkTiempo = $this->conexion->query("SHOW COLUMNS FROM juego_preguntas LIKE 'tiempo_respuesta'");

        $updateQuery = "UPDATE juego_preguntas SET es_correcta = $isCorrect, id_respuesta_elegida = $id_respuesta";
        
        if ($checkOpc && count($checkOpc)) {
            $updateQuery .= ", opcion_elegida = '" . addslashes($opcion_final) . "'";
        }
        
        if ($checkTiempo && count($checkTiempo)) {
            $updateQuery .= ", tiempo_respuesta = $tiempo_respuesta";
        }
        
        $updateQuery .= " WHERE id_juego = $id_juego AND id_pregunta = $id_pregunta AND id_usuario = $id_usuario";
        
        $this->conexion->execute($updateQuery);
        $sql_actualizar_respondida = "UPDATE preguntas SET veces_respondida = COALESCE(veces_respondida, 0) + 1 WHERE id_pregunta = $id_pregunta";
        $this->conexion->execute($sql_actualizar_respondida);

        if ($isCorrect) {
            $sql_actualizar_acertada = "UPDATE preguntas SET veces_acertada = COALESCE(veces_acertada, 0) + 1 WHERE id_pregunta = $id_pregunta";
            $this->conexion->execute($sql_actualizar_acertada);
            $this->conexion->execute("UPDATE juegos SET puntaje = COALESCE(puntaje,0) + 1 WHERE id_juego = $id_juego");
        }

        $puntaje = $this->obtenerPuntajeJuego($id_juego);

        return [
            'correct' => (bool)$isCorrect,
            'correcta' => $correct,
            'puntaje' => (int)$puntaje
        ];
    }

    public function obtenerJuegoActivo($id_usuario)
    {
        $id = intval($id_usuario);
        $res = $this->conexion->query("SELECT * FROM juegos WHERE id_usuario = $id AND estado = 'activo' ORDER BY iniciado_en DESC LIMIT 1");
        return (isset($res[0]) && is_array($res[0])) ? $res[0] : null;
    }

    public function obtenerJuego($id_juego)
    {
        $id = intval($id_juego);
        $res = $this->conexion->query("SELECT * FROM juegos WHERE id_juego = $id LIMIT 1");
        return (isset($res[0]) && is_array($res[0])) ? [$res[0]] : null;
    }

    public function obtenerPuntajeJuego($id_juego)
    {
        $id = intval($id_juego);
        $res = $this->conexion->query("SELECT puntaje FROM juegos WHERE id_juego = $id LIMIT 1");
        if ($res && isset($res[0]['puntaje'])) {
            return (int)$res[0]['puntaje'];
        }
        return 0;
    }

    public function guardarPartida($puntajeFinal, $id_juego)
    {
        $p = intval($puntajeFinal);
        $id = intval($id_juego);
        $this->conexion->execute("UPDATE juegos SET puntaje = $p, estado = 'finalizado', finalizado_en = NOW() WHERE id_juego = $id");
    }

    public function marcarPartidaPerdida($id_juego)
    {
        $id = intval($id_juego);
        $puntajeActual = $this->obtenerPuntajeJuego($id);
        $this->conexion->execute("UPDATE juegos SET puntaje = $puntajeActual, estado = 'perdido', finalizado_en = NOW() WHERE id_juego = $id");
    }

    public function registrarPreguntaMostrada($id_juego, $id_usuario, $id_pregunta)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        $id_pregunta = intval($id_pregunta);
        
        $exists = $this->conexion->query("SELECT COUNT(*) as count FROM juego_preguntas WHERE id_juego = $id_juego AND id_pregunta = $id_pregunta AND id_usuario = $id_usuario");
        
        if ($exists && isset($exists[0]['count']) && $exists[0]['count'] > 0) {
            return;
        }
        
        $sql = "INSERT INTO juego_preguntas (id_juego, id_pregunta, id_usuario, id_respuesta_elegida, es_correcta, usada_trampita, creado_en) 
                VALUES ($id_juego, $id_pregunta, $id_usuario, NULL, 0, 0, NOW())";
        
        $this->conexion->execute($sql);
    }

    public function resetearHistorialUsuario($id_usuario)
    {
        $id_usuario = intval($id_usuario);
        $sql = "DELETE FROM juego_preguntas WHERE id_usuario = $id_usuario";
        $this->conexion->execute($sql);
    }

    public function obtenerEstadoJuego($id_juego, $id_usuario)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        
        $preguntasPendientes = $this->conexion->query("
            SELECT jp.id_pregunta, p.pregunta, c.nombre as categoria,
                   r.a, r.b, r.c, r.d, r.es_correcta
            FROM juego_preguntas jp
            JOIN preguntas p ON p.id_pregunta = jp.id_pregunta
            JOIN categorias c ON c.id_categoria = p.id_categoria
            JOIN respuestas r ON r.id_pregunta = p.id_pregunta
            WHERE jp.id_juego = $id_juego 
            AND jp.id_usuario = $id_usuario
            AND jp.id_respuesta_elegida IS NULL
            ORDER BY jp.id_juego_pregunta DESC 
            LIMIT 1
        ");
        
        if ($preguntasPendientes && count($preguntasPendientes) > 0) {
            $p = $preguntasPendientes[0];
            return [
                'pregunta_pendiente' => [
                    'id_pregunta' => $p['id_pregunta'],
                    'pregunta' => $p['pregunta'],
                    'categoria' => $p['categoria'],
                    'opciones' => [
                        'A' => $p['a'],
                        'B' => $p['b'],
                        'C' => $p['c'],
                        'D' => $p['d']
                    ],
                    'respuesta_correcta' => $p['es_correcta']
                ]
            ];
        }
        
        return ['pregunta_pendiente' => null];
    }

    public function tienePreguntasPendientes($id_juego, $id_usuario)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        
        $resultado = $this->conexion->query("
            SELECT COUNT(*) as count 
            FROM juego_preguntas 
            WHERE id_juego = $id_juego 
            AND id_usuario = $id_usuario
            AND id_respuesta_elegida IS NULL
        ");
        
        return $resultado && isset($resultado[0]['count']) && $resultado[0]['count'] > 0;
    }

    public function obtenerDebugPreguntasPendientes($id_juego, $id_usuario)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        
        $resultado = $this->conexion->query("
            SELECT jp.id_juego_pregunta, jp.id_pregunta, jp.id_respuesta_elegida, 
                   jp.es_correcta, p.pregunta, jp.creado_en
            FROM juego_preguntas jp
            JOIN preguntas p ON p.id_pregunta = jp.id_pregunta
            WHERE jp.id_juego = $id_juego 
            AND jp.id_usuario = $id_usuario
            ORDER BY jp.id_juego_pregunta DESC
            LIMIT 5
        ");
        
        return $resultado ?: [];
    }

    public function obtenerEstadisticasPartidaActual($id_juego, $id_usuario)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        
        $resultado = $this->conexion->query("
            SELECT 
                COUNT(*) as total_respondidas,
                SUM(CASE WHEN es_correcta = 1 THEN 1 ELSE 0 END) as total_aciertos
            FROM juego_preguntas 
            WHERE id_juego = $id_juego 
            AND id_usuario = $id_usuario
            AND id_respuesta_elegida IS NOT NULL
        ");
        
        if (!$resultado || !$resultado[0]) {
            return ['aciertos' => 0, 'total' => 0];
        }
        
        return [
            'aciertos' => intval($resultado[0]['total_aciertos']),
            'total' => intval($resultado[0]['total_respondidas'])
        ];
    }
    
    public function resetearPreguntasVistas($id_juego, $id_usuario)
    {
        return true;
    }
}
?>