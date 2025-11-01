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


}
