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
        WHERE id_usuario = $id AND estado = 'finalizado'
    ");
        $stats = $a ? $a[0] : ['partidas'=>0,'mejor_puntaje'=>0,'promedio_puntaje'=>0];

        $b = $this->conexion->query("
        SELECT COALESCE(puntaje,0) AS ultimo_puntaje
        FROM juegos
        WHERE id_usuario = $id AND estado = 'finalizado'
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
            return 'dificil';   // si el usuario acierta mas del 70% quiere decir que es de categoria dificil por el nivel de inteligencia
        } elseif ($porcentaje_acierto < 30) {
            return 'facil';     // si el usuario acierta menos del 30% quiere decir que es de categoria facil por el nivel de inteligencia
        } else {
            return 'intermedia';  //por default entre 30% y 70% son intermedios los usuarios
        }
    }
    public function obtenerRankingAcumulado($limite)
    {
        $sql = "
        SELECT u.usuario, 
               u.imagen,
               SUM(j.puntaje) AS puntaje_total_acumulado,
               COUNT(j.id_juego) AS partidas_jugadas
        FROM usuarios u
        JOIN juegos j ON u.id_usuario = j.id_usuario
        WHERE j.estado = 'finalizado'
        GROUP BY u.id_usuario
        ORDER BY puntaje_total_acumulado DESC
        LIMIT {$limite}";

        // Suma todos los puntajes finales de las partidas de cada usuario.

        return $this->conexion->query($sql) ?? [];
    }


}
