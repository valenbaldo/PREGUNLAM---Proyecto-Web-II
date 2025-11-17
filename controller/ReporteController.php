<?php

class ReporteController
{
    private $reporteModel;
    private $renderer;

    public function __construct($reporteModel, $renderer)
    {
        $this->reporteModel = $reporteModel;
        $this->renderer = $renderer;
    }

    public function ajaxReportar()
    {
        header('Content-Type: application/json; charset=utf-8');


        try {
            if (session_status() === PHP_SESSION_NONE) session_start();

            $user = $_SESSION['id_usuario'] ?? null;
            $idPregunta = intval($_POST['id_pregunta'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (!$user) {
                http_response_code(401);
                echo json_encode(['success'=>false,'error'=>'Debes estar logeado para reportar.']);
                exit;
            }

            if ($idPregunta <= 0 || empty($descripcion) || strlen($descripcion) < 10) {
                http_response_code(400);
                echo json_encode(['success'=>false,'error'=>'Datos inválidos o descripción muy corta.']);
                exit;
            }

            $exito = $this->reporteModel->crearReporte($idPregunta, $user, $descripcion);

            if ($exito !== false) {
                echo json_encode(['success'=>true,'message'=>'¡Gracias! El reporte ha sido enviado y será revisado.']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error al intentar guardar el reporte en la base de datos.']);
            }

        } catch (\Throwable $e) {

            error_log("Error Reporte SQL: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Error interno del servidor. Verifique la tabla `reportes`.']);
        }
        exit;
    }
}