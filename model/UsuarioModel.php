<?php

class UsuarioModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    public function obtenerPartidas(int $id_usuario): array
    {
        $sql = "SELECT * FROM juegos
                WHERE id_usuario = $id_usuario
                ORDER BY iniciado_en DESC";

        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerPuntajeTotal(int $id_usuario): int
    {
        $sql = "SELECT SUM(puntaje) as total FROM juegos WHERE id_usuario = $id_usuario";
        $data = $this->conexion->query($sql);
        return $data ? (int)$data[0]['total'] : 0;
    }


}
