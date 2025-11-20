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
        try {
            $sql = "UPDATE usuarios SET id_rol = $id_rol_a_asignar WHERE id_usuario = $id_usuario_target";

            $this->conexion->execute($sql);

            return true;
        }
        catch (Exception $e) {
            return false;
        }

    }

    public function partidasPorMes($year = null, $categoria = null)
    {
        $year = $year ?? date('Y');
        $mesActual = (int)date('m');
        $mesInicio = $mesActual - 5;

        if ($mesInicio <= 0) {
            $mesInicio += 12;
            $yearInicio = $year - 1;
        } else {
            $yearInicio = $year;
        }

        $whereClause = "WHERE (YEAR(j.iniciado_en) = $year AND MONTH(j.iniciado_en) >= $mesInicio) 
                   OR (YEAR(j.iniciado_en) = $yearInicio AND MONTH(j.iniciado_en) >= $mesInicio AND YEAR(j.iniciado_en) < $year)";

        if ($categoria) {
            $whereClause .= " AND c.nombre = '$categoria'";
        }

        $sql = "
        SELECT 
            MONTH(j.iniciado_en) as mes,
            MONTHNAME(j.iniciado_en) as nombre_mes,
            COUNT(DISTINCT j.id_juego) as cantidad
        FROM juegos j
        LEFT JOIN juego_preguntas jp ON j.id_juego = jp.id_juego
        LEFT JOIN preguntas p ON jp.id_pregunta = p.id_pregunta
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        $whereClause
        GROUP BY MONTH(j.iniciado_en), MONTHNAME(j.iniciado_en)
        ORDER BY j.iniciado_en
    ";

        $resultado = $this->conexion->query($sql) ?? [];

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $cantidadPorMes = [];
        foreach ($resultado as $fila) {
            $cantidadPorMes[$fila['mes']] = $fila['cantidad'];
        }
        $datos = [];
        for ($i = $mesInicio; $i <= $mesActual; $i++) {
            $mesNumero = ($i <= 0) ? $i + 12 : $i;
            $datos[] = [
                'mes' => $mesNumero,
                'nombre_mes' => $meses[$mesNumero],
                'cantidad' => $cantidadPorMes[$mesNumero] ?? 0
            ];
        }
        return $datos;
    }

    public function partidasPorMesSinAño($categoria = null)
    {
        // Consulta simplificada sin JOINs innecesarios para el filtro básico
        $sql = "
        SELECT 
            MONTH(iniciado_en) as mes,
            MONTHNAME(iniciado_en) as nombre_mes,
            COUNT(*) as cantidad
        FROM juegos
        GROUP BY MONTH(iniciado_en), MONTHNAME(iniciado_en)
        ORDER BY MONTH(iniciado_en)
        ";

        $resultado = $this->conexion->query($sql) ?? [];

        // Normalizar para mostrar todos los 12 meses
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        $cantidadPorMes = [];
        foreach ($resultado as $fila) {
            $cantidadPorMes[$fila['mes']] = $fila['cantidad'];
        }
        
        $datos = [];
        for ($i = 1; $i <= 12; $i++) {
            $datos[] = [
                'mes' => $i,
                'nombre_mes' => $meses[$i],
                'cantidad' => $cantidadPorMes[$i] ?? 0
            ];
        }
        return $datos;
    }

    public function partidasPorDia($month = null, $year = null, $categoria = null)
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');
        
        $sql = "
        SELECT 
            DAY(iniciado_en) as dia,
            COUNT(*) as cantidad
        FROM juegos
        WHERE MONTH(iniciado_en) = $month AND YEAR(iniciado_en) = $year
        GROUP BY DAY(iniciado_en)
        ORDER BY DAY(iniciado_en)
        ";

        $resultado = $this->conexion->query($sql) ?? [];

        // Normalizar para mostrar todos los días del mes
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        $cantidadPorDia = [];
        foreach ($resultado as $fila) {
            $cantidadPorDia[$fila['dia']] = $fila['cantidad'];
        }
        
        $datos = [];
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $datos[] = [
                'dia' => $i,
                'nombre_dia' => "Día $i",
                'cantidad' => $cantidadPorDia[$i] ?? 0
            ];
        }
        return $datos;
    }

    public function preguntasPorCategoria($desde = null, $hasta = null)
    {
        $whereClause = "";
        if ($desde && $hasta) {
            $whereClause = "WHERE DATE(p.created_at) BETWEEN '$desde' AND '$hasta'";
        }

        $sql = "
            SELECT 
                c.nombre as categoria,
                COUNT(*) as cantidad,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM preguntas p2 JOIN categorias c2 ON p2.id_categoria = c2.id_categoria $whereClause), 2) as porcentaje
            FROM preguntas p
            JOIN categorias c ON p.id_categoria = c.id_categoria
            $whereClause
            GROUP BY c.nombre, c.id_categoria
            ORDER BY cantidad DESC
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function rendimientoPorCategoria($limite = 10)
    {
        $sql = "
            SELECT 
                c.nombre as categoria,
                COUNT(jp.id_juego_pregunta) as total_respuestas,
                SUM(jp.es_correcta) as aciertos,
                ROUND(AVG(jp.es_correcta) * 100, 2) as porcentaje_acierto
            FROM juego_preguntas jp
            JOIN preguntas p ON jp.id_pregunta = p.id_pregunta
            JOIN categorias c ON p.id_categoria = c.id_categoria
            GROUP BY c.nombre, c.id_categoria
            HAVING total_respuestas >= 1
            ORDER BY porcentaje_acierto DESC
            LIMIT $limite
        ";
        return $this->conexion->query($sql) ?? [];
    }

    public function obtenerCategorias()
    {
        $sql = "SELECT nombre as categoria FROM categorias ORDER BY nombre";
        return $this->conexion->query($sql) ?? [];
    }
}