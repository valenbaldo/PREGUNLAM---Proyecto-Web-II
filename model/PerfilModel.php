<?php
class PerfilModel
{
    private $conexion;
    public function __construct($conexion) { $this->conexion = $conexion; }

    public function obtenerPerfil(int $idUsuario): array
    {
        $sql = "
            SELECT u.id_usuario,
                   u.nombre,
                   u.apellido,
                   u.usuario    AS username,
                   u.mail       AS email,
                   u.imagen,
                   u.fecha_nacimiento
            FROM usuarios u
            WHERE u.id_usuario = $idUsuario
            LIMIT 1
        ";
        $res = $this->conexion->query($sql);
        return $res ? $res[0] : [];
    }

    public function obtenerStats(int $idUsuario): array
    {
        $sql = "
            SELECT COUNT(*) AS partidas,
                   COALESCE(MAX(puntaje),0)  AS mejor_puntaje,
                   COALESCE(AVG(puntaje),0)  AS promedio_puntaje
            FROM juegos
            WHERE id_usuario = $idUsuario AND estado IN ('finalizado', 'perdido')
        ";
        $a = $this->conexion->query($sql);
        $stats = $a ? $a[0] : ['partidas'=>0,'mejor_puntaje'=>0,'promedio_puntaje'=>0];

        $sql2 = "
            SELECT COALESCE(puntaje,0) AS ultimo_puntaje,
                   finalizado_en
            FROM juegos
            WHERE id_usuario = $idUsuario AND estado IN ('finalizado', 'perdido')
            ORDER BY finalizado_en DESC
            LIMIT 1
        ";
        $b = $this->conexion->query($sql2);
        $stats['ultimo_puntaje'] = $b ? (int)$b[0]['ultimo_puntaje'] : 0;

        return $stats;
    }
}
