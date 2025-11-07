<?php

class UsuarioModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    public function obtenerPartidas($id_usuario)
    {
        $sql = "SELECT * FROM juegos
                WHERE id_usuario = $id_usuario
                ORDER BY iniciado_en DESC";

        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerDatosPorId(int $id_usuario): ?array
    {
        $id = (int)$id_usuario;
        $sql = "
        SELECT
            id_usuario,
            nombre,
            apellido,
            usuario   AS username,
            mail      AS email,
            imagen,
            fecha_nacimiento
        FROM usuarios
        WHERE id_usuario = $id
        LIMIT 1
    ";
        $res = $this->conexion->query($sql);
        return ($res && isset($res[0])) ? $res[0] : null;
    }

    public function obtenerStats(int $id_usuario): array
    {
        $id = (int)$id_usuario;

        $a = $this->conexion->query("
        SELECT
            COUNT(*)                AS partidas,
            COALESCE(MAX(puntaje),0)  AS mejor_puntaje,
            COALESCE(AVG(puntaje),0)  AS promedio_puntaje
        FROM juegos
        WHERE id_usuario = $id AND estado IN ('finalizado', 'perdido')
    ");
        $stats = $a ? $a[0] : ['partidas'=>0,'mejor_puntaje'=>0,'promedio_puntaje'=>0];

        $b = $this->conexion->query("
        SELECT COALESCE(puntaje,0) AS ultimo_puntaje
        FROM juegos
        WHERE id_usuario = $id AND estado IN ('finalizado', 'perdido')
        ORDER BY finalizado_en DESC
        LIMIT 1
    ");
        $stats['ultimo_puntaje'] = $b ? (int)$b[0]['ultimo_puntaje'] : 0;

        return $stats;
    }


    public function obtenerPuntajeTotal($id_usuario)
    {
        $sql = "SELECT SUM(puntaje) as total FROM juegos WHERE id_usuario = $id_usuario";
        $data = $this->conexion->query($sql);
        return $data ? (int)$data[0]['total'] : 0;
    }
    public function obtenerNivelUsuario($id_usuario)
    {
        $sql = "SELECT SUM(es_correcta) AS total_aciertos, COUNT(id_juego_pregunta) AS total_respondidas
            FROM juego_preguntas
            WHERE id_usuario = $id_usuario";

        $data = $this->conexion->query($sql);

        if (!$data || $data[0]['total_respondidas'] == 0) {
            return 'facil';
        }

        $aciertos = $data[0]['total_aciertos'];
        $respondidas = $data[0]['total_respondidas'];

        $porcentaje_acierto = ($aciertos / $respondidas) * 100;

        if ($porcentaje_acierto > 70) {
            return 'dificil';
        } elseif ($porcentaje_acierto < 30) {
            return 'facil';
        } else {
            return 'intermedia';
        }
    }

    public function obtenerInfoCompleteNivel($id_usuario)
    {
        $sql = "SELECT SUM(es_correcta) AS total_aciertos, COUNT(id_juego_pregunta) AS total_respondidas
            FROM juego_preguntas
            WHERE id_usuario = $id_usuario AND id_respuesta_elegida IS NOT NULL";

        $data = $this->conexion->query($sql);

        if (!$data || $data[0]['total_respondidas'] == 0) {
            return [
                'nivel' => 'facil',
                'porcentaje' => 0,
                'aciertos' => 0,
                'total' => 0,
                'mensaje' => 'Principiante - ¡Empezá a jugar!'
            ];
        }

        $aciertos = $data[0]['total_aciertos'];
        $total = $data[0]['total_respondidas'];
        $ratio = $aciertos / $total;

        // Algoritmo simple basado en ratio
        if ($ratio >= 0.7) {
            $nivel = 'dificil';
            $mensaje = 'Experto - Preguntas desafiantes';
        } elseif ($ratio >= 0.4) {
            $nivel = 'intermedia';
            $mensaje = 'Intermedio - ¡Vas bien!';
        } else {
            $nivel = 'facil';
            $mensaje = 'Principiante - Seguí practicando';
        }

        return [
            'nivel' => $nivel,
            'porcentaje' => round($ratio * 100, 1),
            'aciertos' => $aciertos,
            'total' => $total,
            'mensaje' => $mensaje,
            'ratio' => $ratio
        ];
    }

    public function obtenerInfoCompleteNivelExcluyendoJuego($id_usuario, $id_juego_excluir)
    {
        $sql = "SELECT SUM(es_correcta) AS total_aciertos, COUNT(id_juego_pregunta) AS total_respondidas
            FROM juego_preguntas
            WHERE id_usuario = $id_usuario AND id_respuesta_elegida IS NOT NULL AND id_juego != $id_juego_excluir";

        $data = $this->conexion->query($sql);

        if (!$data || $data[0]['total_respondidas'] == 0) {
            return [
                'nivel' => 'facil',
                'porcentaje' => 0,
                'aciertos' => 0,
                'total' => 0,
                'mensaje' => 'Principiante - ¡Empezá a jugar!'
            ];
        }

        $aciertos = $data[0]['total_aciertos'];
        $total = $data[0]['total_respondidas'];
        $ratio = $aciertos / $total;

        // Algoritmo simple basado en ratio
        if ($ratio >= 0.7) {
            $nivel = 'dificil';
            $mensaje = 'Experto - Preguntas desafiantes';
        } elseif ($ratio >= 0.4) {
            $nivel = 'intermedia';
            $mensaje = 'Intermedio - ¡Vas bien!';
        } else {
            $nivel = 'facil';
            $mensaje = 'Principiante - Seguí practicando';
        }

        return [
            'nivel' => $nivel,
            'porcentaje' => round($ratio * 100, 1),
            'aciertos' => $aciertos,
            'total' => $total,
            'mensaje' => $mensaje,
            'ratio' => $ratio
        ];
    }

    public function obtenerRankingPorAverage($limite)
    {
        $sql = "
        SELECT u.usuario, 
               u.imagen,
               COUNT(DISTINCT j.id_juego) AS partidas_jugadas,
               SUM(jp.es_correcta) AS total_aciertos,
               COALESCE(
                   SUM(jp.es_correcta) / COUNT(DISTINCT j.id_juego), 
                   0
               ) AS average_aciertos_por_partida,
               SUM(j.puntaje) AS puntaje_total_acumulado
        FROM usuarios u
        JOIN juegos j ON u.id_usuario = j.id_usuario
        JOIN juego_preguntas jp ON j.id_juego = jp.id_juego
        WHERE j.estado IN ('finalizado', 'perdido') 
          AND jp.id_respuesta_elegida IS NOT NULL
        GROUP BY u.id_usuario, u.usuario, u.imagen
        ORDER BY average_aciertos_por_partida DESC, puntaje_total_acumulado DESC 
        
        LIMIT {$limite}";

        return $this->conexion->query($sql) ?? [];
    }
}
