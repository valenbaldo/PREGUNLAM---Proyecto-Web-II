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

        $data = [
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor',
            'preguntas' => $this->model->obtenerTodasLasPreguntas(),
            'reportes_pendientes' => $this->model->contarReportesPendientes(),
            'id_rol' => $_SESSION['id_rol'] ?? 2
        ];

        if(!empty($_SESSION['msg'])){
            $data['msg'] = $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        if (!empty($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

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

        $this->renderer->render("editorCrear", [
            'categorias' => $categorias,
            'id_rol' => $_SESSION['id_rol'] ?? 2,
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor'
        ]);
    }
    public function guardarPregunta()
    {
        $this->tienePermisoEditor();

        $datos = $_POST;
        $id_usuario = $_SESSION['id_usuario'];

        if (empty($datos['pregunta']) || empty($datos['respuesta_correcta'])) {
            $datos['error'] = "Todos los campos son obligatorios.";
            $datos['id_rol'] = $_SESSION['id_rol'] ?? 2;
            $datos['nombreUsuario'] = $_SESSION['nombreUsuario'] ?? 'Editor';
            $this->renderer->render("editorCrear", $datos);
            return;
        }

        $resultado = $this->model->guardar($datos, $id_usuario);

        if ($resultado) {
            $_SESSION['msg'] = "Pregunta creada exitosamente!!";
            header("Location: /editor/base");
        } else {
            $datos['error'] = "Error al guardar la pregunta en la base de datos.";
            $datos['id_rol'] = $_SESSION['id_rol'] ?? 2;
            $datos['nombreUsuario'] = $_SESSION['nombreUsuario'] ?? 'Editor';
            $this->renderer->render("editorCrear", $datos);
        }
        exit;
    }

    public function editarPregunta()
    {
        $this->tienePermisoEditor();
        $idPregunta = ($_GET['id'] ?? 0);

        if ($idPregunta <= 0) {
            $_SESSION['error'] = "ID de pregunta inválido";
            header("Location: /editor/base");
            exit;
        }
        $pregunta = $this->model->obtenerPreguntaCompleta($idPregunta);

        if (!$pregunta) {
            $_SESSION['error'] = "Pregunta no encontrada";
            header("Location: /editor/base");
            exit;
        }
        $pregunta['categorias'] = $this->model->obtenerCategorias();
        $pregunta['id_rol'] = $_SESSION['id_rol'] ?? 2;
        $pregunta['nombreUsuario'] = $_SESSION['nombreUsuario'] ?? 'Editor';

        $this->renderer->render("editorEditar", $pregunta);
    }

    public function actualizarPregunta()
    {
        $this->tienePermisoEditor();
        $datos = $_POST;

        if (empty($datos['id_pregunta'])) {
            $_SESSION['error'] = "Falta ID para actualizar";
            header("Location: /editor/base");
            exit;
        }
        $resultado = $this->model->actualizar($datos);

        if ($resultado) {
            $_SESSION['msg'] = "Pregunta actualizada exitosamente!!";
            header("Location: /editor/base");
        } else {
            $_SESSION['error'] = "Error al actualizar la pregunta";
            header("Location: /editor/base");
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
                $_SESSION['msg'] = "Pregunta eliminada exitosamente";
                header("Location: /editor/base");
            } else {
                $_SESSION['error'] = "Error al eliminar la pregunta";
                header("Location: /editor/base");
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
            'mensaje' => $_GET['msg'] ?? null,
            'id_rol' => $_SESSION['id_rol'] ?? 2
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
            $_SESSION['msg'] = $msg;
            header("Location: /editor/gestionarReportes");
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
                $_SESSION['msg'] = $msg;
                header("Location: /editor/gestionarReportes");
                exit;
        }

        $exito = $this->model->actualizarEstadoReporte($id_reporte, $nuevo_estado);

        if ($exito) {
            $msg = "Reporte ID $id_reporte actualizado a '$nuevo_estado' correctamente.";
        } else {
            $msg = "Error al actualizar el reporte ID $id_reporte.";
        }

        $_SESSION['msg'] = $msg;
        header("Location: /editor/gestionarReportes");
        exit;
    }

    public function gestionarCategorias()
    {
        $this->tienePermisoEditor();

        $data = [
            'categorias' => $this->model->obtenerTodasCategorias(),
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor',
            'id_rol' => $_SESSION['id_rol'] ?? 2
        ];

        if (!empty($_SESSION['msg'])) {
            $data['msg'] = $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        if (!empty($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        $this->renderer->render("editorCategorias", $data);
    }

    public function crearCategoria()
    {
        $this->tienePermisoEditor();
        $this->renderer->render("editorCategoriaForm", [
            'accion' => 'crear',
            'titulo' => 'Crear Nueva Categoría',
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor',
            'id_rol' => $_SESSION['id_rol'] ?? 2
        ]);
    }

    public function guardarCategoria()
    {
        $this->tienePermisoEditor();

        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $_SESSION['error'] = "El nombre de la categoría es obligatorio.";
            header("Location: /editor/crearCategoria");
            exit;
        }

        $resultado = $this->model->crearCategoria($nombre);

        if ($resultado) {
            $_SESSION['msg'] = "Categoría '$nombre' creada exitosamente.";
        } else {
            $_SESSION['error'] = "Error al crear la categoría. Puede que ya exista una categoría con ese nombre.";
        }

        header("Location: /editor/gestionarCategorias");
        exit;
    }

    public function editarCategoria()
    {
        $this->tienePermisoEditor();

        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error'] = "ID de categoría inválido.";
            header("Location: /editor/gestionarCategorias");
            exit;
        }

        $categoria = $this->model->obtenerCategoriaPorId($id);

        if (!$categoria) {
            $_SESSION['error'] = "Categoría no encontrada.";
            header("Location: /editor/gestionarCategorias");
            exit;
        }

        $this->renderer->render("editorCategoriaForm", [
            'accion' => 'editar',
            'titulo' => 'Editar Categoría',
            'categoria' => $categoria,
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Editor',
            'id_rol' => $_SESSION['id_rol'] ?? 2
        ]);
    }

    public function actualizarCategoria()
    {
        $this->tienePermisoEditor();

        $id = intval($_POST['id_categoria'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || empty($nombre)) {
            $_SESSION['error'] = "Datos inválidos para actualizar la categoría.";
            header("Location: /editor/gestionarCategorias");
            exit;
        }

        $resultado = $this->model->actualizarCategoria($id, $nombre);

        if ($resultado) {
            $_SESSION['msg'] = "Categoría actualizada exitosamente.";
        } else {
            $_SESSION['error'] = "Error al actualizar la categoría. Puede que ya exista una categoría con ese nombre.";
        }

        header("Location: /editor/gestionarCategorias");
        exit;
    }

    public function eliminarCategoria()
    {
        $this->tienePermisoEditor();

        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error'] = "ID de categoría inválido.";
            header("Location: /editor/gestionarCategorias");
            exit;
        }

        $resultado = $this->model->eliminarCategoria($id);

        if ($resultado === true) {
            $_SESSION['msg'] = "Categoría eliminada exitosamente.";
        } else if ($resultado === 'restriccion') {
            $_SESSION['error'] = "No se puede eliminar la categoría porque tiene preguntas asociadas.";
        } else {
            $_SESSION['error'] = "Error al eliminar la categoría.";
        }

        header("Location: /editor/gestionarCategorias");
        exit;
    }

}