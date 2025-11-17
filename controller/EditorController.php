<?php

class EditorController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->tienePermisoEditor();
        $msg = $_GET['msg'] ?? null;
        $error = $_GET['error'] ?? null;

        $preguntas = $this->model->obtenerTodasLasPreguntas();
        $reportes_pendientes = $this->model->contarReportesPendientes();

        $data = [
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor',
            'preguntas' => $preguntas,
            'reportes_pendientes' => $reportes_pendientes,
            'msg' => $msg,
            'error' => $error
        ];

        $this->renderer->render("editorPanel", $data);
    }

    private function tienePermisoEditor()
    {
        if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
            header("Location: /home");
            exit;
        }
    }
    public function crearPregunta()
    {
        $this->tienePermisoEditor();

        $categorias = $this->model->obtenerCategorias();

        $this->renderer->render("editorCrear", ['categorias' => $categorias]);
    }
    public function guardarPregunta()
    {
        $this->tienePermisoEditor();

        $datos = $_POST;

        if (empty($datos['pregunta']) || empty($datos['respuesta_correcta'])) {
            $datos['error'] = "Todos los campos son obligatorios.";
            $this->renderer->render("editorCrear", $datos);
            return;
        }

        $resultado = $this->model->guardar($datos);

        if ($resultado) {
            header("Location: /editor/base?msg=Pregunta creada exitosamente");
        } else {
            $datos['error'] = "Error al guardar la pregunta en la base de datos.";
            $this->renderer->render("editorCrear", $datos);
        }
        exit;
    }

    public function editarPregunta()
    {
        $this->tienePermisoEditor();
        $idPregunta = ($_GET['id'] ?? 0);

        if ($idPregunta <= 0) {
            header("Location: /editor/base?error=ID de pregunta inválido");
            exit;
        }
        $pregunta = $this->model->obtenerPreguntaCompleta($idPregunta);

        if (!$pregunta) {
            header("Location: /editor/base?error=Pregunta no encontrada");
            exit;
        }
        $pregunta['categorias'] = $this->model->obtenerCategorias();

        $this->renderer->render("editorEditar", $pregunta);
    }

    public function actualizarPregunta()
    {
        $this->tienePermisoEditor();
        $datos = $_POST;

        if (empty($datos['id_pregunta'])) {
            header("Location: /editor/base?error=Falta ID para actualizar");
            exit;
        }
        $resultado = $this->model->actualizar($datos);

        if ($resultado) {
            header("Location: /editor/base?msg=Pregunta actualizada exitosamente");
        } else {
            header("Location: /editor/base?error=Error al actualizar la pregunta");
        }
        exit;
    }
    public function eliminarPregunta()
    {
        $this->tienePermisoEditor();
        $idPregunta = ($_GET['id'] ?? 0);

        if ($idPregunta > 0) {
            $resultado = $this->model->eliminar($idPregunta);
            if ($resultado) {
                header("Location: /editor/base?msg=Pregunta eliminada exitosamente");
            } else {
                header("Location: /editor/base?error=Error al eliminar la pregunta");
            }
        }
        header("Location: /editor/base");
        exit;
    }
    public function gestionarReportes()
    {
        $this->tienePermisoEditor();

        $reportes = $this->model->obtenerReportesPendientes();

        $data = [
            'reportes' => $reportes,
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor',
            'mensaje' => $_GET['msg'] ?? null
        ];

        $this->renderer->render("editorReportes", $data);
    }

    public function procesarReporte()
    {
        $this->tienePermisoEditor();

        $id_reporte = $_POST['id_reporte'] ?? 0;
        $accion = $_POST['accion'] ?? '';
        $nuevo_estado = '';

        if ($id_reporte <= 0 || empty($accion)) {
            $msg = "Error: Datos de reporte inválidos.";
            header("Location: /editor/gestionarReportes?msg=" . urlencode($msg));
            exit;
        }

        switch ($accion) {
            case 'validar':
                $nuevo_estado = 'revisado';
                break;
            case 'rechazar':
                $nuevo_estado = 'rechazado';
                break;
            default:
                $msg = "Error: Acción no reconocida.";
                header("Location: /editor/gestionarReportes?msg=" . urlencode($msg));
                exit;
        }

        $exito = $this->model->actualizarEstadoReporte($id_reporte, $nuevo_estado);

        if ($exito) {
            $msg = "Reporte ID $id_reporte actualizado a '$nuevo_estado' correctamente.";
        } else {
            $msg = "Error al actualizar el reporte ID $id_reporte.";
        }

        header("Location: /editor/gestionarReportes?msg=" . urlencode($msg));
        exit;
    }

}