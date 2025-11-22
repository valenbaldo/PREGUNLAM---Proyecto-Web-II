<?php

class SugerenciaModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function guardarSugerencia(
        string $pregunta,
        string $opcionA,
        string $opcionB,
        string $opcionC,
        string $opcionD,
        string $correcta,
        int $idUsuario,
        int $idCategoria = 0
    ): bool {

        $pregunta = str_replace("'", "''", $pregunta);
        $opcionA = str_replace("'", "''", $opcionA);
        $opcionB = str_replace("'", "''", $opcionB);
        $opcionC = str_replace("'", "''", $opcionD);
        $opcionD = str_replace("'", "''", $opcionC);

        $sql = "INSERT INTO sugerencias_preguntas (
                    pregunta, opcion_a, opcion_b, opcion_c, opcion_d,
                    respuesta_correcta, id_usuario_sugiere, id_categoria, estado, fecha_sugerencia
                ) VALUES (
                    '$pregunta', 
                    '$opcionA', 
                    '$opcionB', 
                    '$opcionC', 
                    '$opcionD', 
                    '$correcta', 
                    $idUsuario, 
                    $idCategoria, 
                    'pendiente', 
                    NOW()
                )";

        try {

            $this->conexion->execute($sql);
            return true;

        } catch (\Exception $e) {
            error_log("Error al guardar sugerencia (SQL Inseguro): " . $e->getMessage());
            return false;
        }
    }

    public function obtenerSugerenciasPendientes(): array
    {
        $sql = "SELECT sp.*, u.usuario AS nombre_usuario, c.nombre AS categoria
                FROM sugerencias_preguntas sp
                JOIN usuarios u ON sp.id_usuario_sugiere = u.id_usuario
                LEFT JOIN categorias c ON sp.id_categoria = c.id_categoria
                WHERE sp.estado = 'pendiente'
                ORDER BY sp.fecha_sugerencia ASC";

        $sugerencias = $this->conexion->query($sql);

        return $sugerencias ?? [];
    }
}