<?php

class JuegoModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    public function iniciarJuego()
    {
        if (!isset($_SESSION['id_usuario']))
            return false;

        $idUsuario = $_SESSION['id_usuario'];

        $this->conexion->query("INSERT INTO juegos (id_usuario, puntaje, estado, iniciado_en) VALUES ($idUsuario, 0, 'activo', NOW())");
        $result = $this->conexion->query("SELECT LAST_INSERT_ID() AS id_juego");
        $_SESSION['id_juego'] = $result[0]['id_juego'];
        $_SESSION['puntaje'] = 0;
        $_SESSION['preguntas_respondidas'] = [];
    }

    public function obtenerPreguntaParaMostrar()
    {
        $preguntasRespondidas = $_SESSION['preguntas_respondidas'] ?? [];

        $sql = "SELECT p.id_pregunta, p.pregunta, r.a, r.b, r.c, r.d, r.es_correcta
                FROM preguntas p
                JOIN respuestas r ON r.id_pregunta = p.id_pregunta
                WHERE p.id_pregunta NOT IN (" . (count($preguntasRespondidas) ? implode(",", $preguntasRespondidas) : "0") . ")
                ORDER BY RAND()
                LIMIT 1";

        $data = $this->conexion->query($sql);

        if (!$data)
            return null;

        $pregunta = $data[0];
        $_SESSION['preguntas_respondidas'][] = $pregunta['id_pregunta'];

        return [
            'id_pregunta' => $pregunta['id_pregunta'],
            'pregunta' => $pregunta['pregunta'],
            'opciones' => [
                'A' => $pregunta['a'],
                'B' => $pregunta['b'],
                'C' => $pregunta['c'],
                'D' => $pregunta['d']
            ],
            'es_correcta' => $pregunta['es_correcta']
        ];
    }

    public function validarRespuesta($id_pregunta, $respuestaElegida)
    {
        $sql = "SELECT es_correcta FROM respuestas WHERE id_pregunta = $id_pregunta";
        $data = $this->conexion->query($sql);
        $correcta = strtoupper($data[0]['es_correcta']);
        $esCorrecta = (strtoupper($respuestaElegida) === $correcta) ? true : false;
        $puntosGanados = $esCorrecta ? 1 : 0;

        return [
            'esCorrecta' => $esCorrecta,
            'puntos_ganados' => $puntosGanados,
            'opcion_correcta' => $correcta
        ];
    }

    public function actualizarPuntaje($puntos)
    {
        $idJuego = $_SESSION['id_juego'] ?? null;
        if (!$idJuego)
            return;

        $_SESSION['puntaje'] += $puntos;
        $this->conexion->query("UPDATE juegos SET puntaje = {$_SESSION['puntaje']} WHERE id_juego = $idJuego");
    }

    public function guardarPartida($puntajeFinal)
    {
        $idJuego = $_SESSION['id_juego'] ?? null;
        if (!$idJuego)
            return;

        $this->conexion->query("UPDATE juegos SET puntaje = $puntajeFinal, estado = 'finalizado', finalizado_en = NOW() WHERE id_juego = $idJuego");
    }
    
}
