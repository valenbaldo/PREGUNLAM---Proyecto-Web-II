<?php

class ReporteModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function crearReporte($idPregunta, $idUsuario, $descripcion)
    {

        $desc_escapada = addslashes($descripcion);
        $id_pregunta = intval($idPregunta);
        $id_usuario = intval($idUsuario);

        $sql = "INSERT INTO reportes (id_pregunta, id_usuario_reporta, descripcion, estado)
            VALUES ($id_pregunta, $id_usuario, '$desc_escapada', 'pendiente')";

        return $this->database->execute($sql);
    }
    public function contarReportesPendientes()
    {
        $sql = "SELECT COUNT(*) AS total FROM reportes WHERE estado = 'pendiente'";
        $resultado = $this->database->query($sql);
        return $resultado[0]['total'] ?? 0;
    }
    public function obtenerReportesPendientes()
    {
        $sql = "SELECT 
                r.id_reporte,
                r.id_pregunta,
                r.descripcion,
                r.estado,
                r.created_at,
                p.pregunta AS nombre_pregunta,
                u.nombre AS reportado_por
            FROM 
                reportes r
            JOIN 
                preguntas p ON r.id_pregunta = p.id_pregunta
            JOIN 
                usuarios u ON r.id_usuario_reporta = u.id_usuario
            WHERE 
                r.estado = 'pendiente'
            ORDER BY 
                r.created_at DESC";

        return $this->database->query($sql);
    }
}