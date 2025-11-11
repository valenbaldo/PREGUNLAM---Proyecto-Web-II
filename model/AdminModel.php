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
        $sql = "SELECT COUNT(*) AS total FROM preguntas WHERE DATE(created_at) BETWEEN '$desde' AND '$hasta'";
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
            HAVING respondidas > 0
            ORDER BY porcentaje DESC
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function usuariosPorPais()
    {
        $sql = "
            SELECT pais, COUNT(*) AS cantidad
            FROM ubicacion
            WHERE pais IS NOT NULL AND pais != ''
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
        ORDER BY grupo DESC
    ";
        return $this->conexion->query($sql) ?? [];
    }
    public function obtenerUsuariosConRol()
    {
        $sql = "
        SELECT u.id_usuario, u.usuario, u.mail, r.nombre AS rol, u.id_rol
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id_rol
        ORDER BY u.id_rol DESC, u.usuario ASC
    ";
        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerRolesDisponibles()
    {
        $sql = "SELECT id_rol, nombre FROM roles WHERE id_rol IN (1, 2, 3)";
        return $this->conexion->query($sql) ?? [];
    }

    public function cambiarRolUsuario($id_usuario, $id_rol_nuevo)
    {
        $id_usuario_target = $id_usuario;
        $id_rol_a_asignar = $id_rol_nuevo;
        $ID_ROL_ADMIN = 3;

        if ($id_rol_a_asignar !== $ID_ROL_ADMIN && $id_usuario_target === ($_SESSION['id_usuario'] ?? null)) {
            return false;
        }
        $sql = "UPDATE usuarios SET id_rol = $id_rol_a_asignar WHERE id_usuario = $id_usuario_target";

        return $this->conexion->execute($sql);
    }
}