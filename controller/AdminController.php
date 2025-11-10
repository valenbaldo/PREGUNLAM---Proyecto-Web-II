<?php

class AdminController
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
        $this->tienePermisoAdmin();

        [$desde, $hasta] = $this->resolverRango();

        $data = [
            'nombreUsuario'          => $_SESSION['nombreUsuario'] ?? 'Administrador',
            'totalUsuarios'          => $this->model->contarUsuarios(),
            'totalPartidas'          => $this->model->contarPartidas(),
            'totalPreguntas'         => $this->model->contarPreguntas(),
            'totalPreguntasCreadas'  => $this->model->contarPreguntasCreadas($desde, $hasta),
            'usuariosNuevos'         => $this->model->contarUsuariosNuevos($desde, $hasta),
            'aciertoPorUsuario'      => $this->model->aciertoPorUsuario(),
            'usuariosPorPais'        => $this->model->usuariosPorPais(),
            'usuariosPorSexo'        => $this->model->usuariosPorSexo(),
            'usuariosPorGrupoEdad'   => $this->model->usuariosPorGrupoEdad()
        ];

        $this->renderer->render("adminPanel", $data);
    }

    private function tienePermisoAdmin()
    {
        if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_rol'] ?? 1) != 3) {
            header("Location: /home");
            exit;
        }
    }

    private function resolverRango(): array
    {
        $hoy = new DateTime('today');
        $desde = isset($_GET['desde']) ? $_GET['desde'] : $hoy->modify('-30 days')->format('Y-m-d');
        $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
        return [$desde, $hasta];
    }
}
