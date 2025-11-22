<?php

class SugerenciaController
{
    private $sugerenciaModel;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->sugerenciaModel = $model;
        $this->renderer = $renderer;
    }
    public function base()
    {
        if (!$this->estaAutenticado()) {
            header("Location: /?controller=login");
            exit();
        }
        $data = [
            'error' => $_SESSION['sugerencia_error'] ?? null,
            'success' => $_SESSION['sugerencia_success'] ?? null,
        ];
        unset($_SESSION['sugerencia_error']);
        unset($_SESSION['sugerencia_success']);
        $this->renderer->render('jugadorSugerirPregunta', $data);
    }
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /?controller=sugerencia&method=base");
            exit();
        }
        $idUsuario = $this->obtenerIdUsuarioSesion();
        if (!$idUsuario) {
            $_SESSION['sugerencia_error'] = "Error: Usuario no autenticado. Inicie sesión nuevamente.";
            header("Location: /?controller=login");
            exit();
        }

        $datos = $this->sanitizarYValidarDatos($_POST);

        if (!$datos) {
            $_SESSION['sugerencia_error'] = "Error: Datos incompletos o formato incorrecto. Asegúrese de completar todos los campos y seleccionar la opción correcta (A, B, C o D).";
            header("Location: /?controller=sugerencia&method=base");
            exit();
        }
        $exito = $this->sugerenciaModel->guardarSugerencia(
            $datos['pregunta'],
            $datos['opcion_a'],
            $datos['opcion_b'],
            $datos['opcion_c'],
            $datos['opcion_d'],
            $datos['correcta'],
            $idUsuario,
            $datos['id_categoria']
        );
        if ($exito) {
            $_SESSION['sugerencia_success'] = "¡Gracias por tu sugerencia! La pregunta ha sido enviada para revisión.";
        } else {
            $_SESSION['sugerencia_error'] = "Error: No se pudo guardar la sugerencia en la base de datos.";
        }

        header("Location: /?controller=sugerencia&method=base");
        exit();
    }
    private function estaAutenticado()
    {
        return $this->obtenerIdUsuarioSesion() !== null;
    }
    private function obtenerIdUsuarioSesion()
    {
        $id = $_SESSION['id_usuario'] ?? null;

        if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) {
            return (int)$id;
        }
        return null;
    }
    private function sanitizarYValidarDatos($post)
    {
        $datos = [
            'pregunta'      => trim($post['pregunta'] ?? ''),
            'opcion_a'      => trim($post['opcion_a'] ?? ''),
            'opcion_b'      => trim($post['opcion_b'] ?? ''),
            'opcion_c'      => trim($post['opcion_c'] ?? ''),
            'opcion_d'      => trim($post['opcion_d'] ?? ''),
            'correcta'      => strtoupper(substr(trim($post['opcion_correcta'] ?? ''), 0, 1)),
            'id_categoria'  => filter_var($post['id_categoria'] ?? null, FILTER_VALIDATE_INT) ?? 0
        ];
        if (
            empty($datos['pregunta']) ||
            empty($datos['opcion_a']) ||
            empty($datos['opcion_b']) ||
            empty($datos['opcion_c']) ||
            empty($datos['opcion_d']) ||
            !in_array($datos['correcta'], ['A', 'B', 'C', 'D'])
        ) {
            return null;
        }

        return $datos;
    }
}