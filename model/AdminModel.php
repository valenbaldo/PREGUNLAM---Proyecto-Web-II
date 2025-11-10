<?php

class AdminModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function contarUsuarios()
    {
        $sql = "SELECT COUNT(*) AS total FROM usuarios";
        return $this->conexion->query($sql)[0]['total'] ?? 0;
    }

    public function contarPartidas()
    {
        $sql = "SELECT COUNT(*) AS total FROM juegos";
        return $this->conexion->query($sql)[0]['total'] ?? 0;
    }

    public function contarPreguntas()
    {
        $sql = "SELECT COUNT(*) AS total FROM preguntas";
        return $this->conexion->query($sql)[0]['total'] ?? 0;
    }

    public function contarPreguntasCreadas($desde, $hasta)
    {
        $sql = "SELECT COUNT(*) AS total FROM preguntas WHERE DATE(preguntas.id_pregunta) BETWEEN '$desde' AND '$hasta'";
        return $this->conexion->query($sql)[0]['total'] ?? 0;
    }

    public function contarUsuariosNuevos($desde, $hasta)
    {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE DATE(created_at) BETWEEN '$desde' AND '$hasta'";
        return $this->conexion->query($sql)[0]['total'] ?? 0;
    }

    public function aciertoPorUsuario()
    {
        $sql = "
            SELECT u.usuario, 
                   COUNT(jp.id_juego_pregunta) AS respondidas,
                   SUM(jp.es_correcta) AS aciertos,
                   ROUND(SUM(jp.es_correcta)/COUNT(jp.id_juego_pregunta)*100, 1) AS porcentaje
            FROM juego_preguntas jp
            JOIN usuarios u ON jp.id_usuario = u.id_usuario
            GROUP BY u.id_usuario
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function usuariosPorPais()
    {
        $sql = "
            SELECT pais, COUNT(*) AS cantidad
            FROM ubicacion
            GROUP BY pais
            ORDER BY cantidad DESC
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function usuariosPorSexo()
    {
        $sql = "
            SELECT sexo, COUNT(*) AS cantidad
            FROM sexo
            GROUP BY sexo
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function usuariosPorGrupoEdad()
    {
        $sql = "
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18 THEN 'Menores'
                    WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 59 THEN 'Medio'
                    ELSE 'Jubilados'
                END AS grupo,
                COUNT(*) AS cantidad
            FROM usuarios
            GROUP BY grupo
        ";
        return $this->conexion->query($sql) ?? [];
    }
}
