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

        $stats = [
            'usuarios_totales'      => $this->model->contarUsuarios(),
            'partidas'              => $this->model->contarPartidas(),
            'preguntas_totales'     => $this->model->contarPreguntas(),
            'preguntas_creadas'     => $this->model->contarPreguntasCreadas($desde, $hasta),
            'usuarios_nuevos'       => $this->model->contarUsuariosNuevos($desde, $hasta),
        ];

        $data = [
            'nombreUsuario'          => $_SESSION['nombreUsuario'] ?? 'Administrador',
            'stats'                  => $stats,
            'aciertos'               => $this->model->aciertoPorUsuario(),
            'porPais'                => $this->model->usuariosPorPais(),
            'porSexo'                => $this->model->usuariosPorSexo(),
            'porEdad'                => $this->model->usuariosPorGrupoEdad()
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

    private function resolverRango()
    {
        $rango = $_GET['r'] ?? 'month';
        $hoy = new DateTime('today');
        $desde = null;
        $hasta = date('Y-m-d');

        switch ($rango) {
            case 'day':
                $desde = date('Y-m-d');
                break;
            case 'week':
                $desde = $hoy->modify('-7 days')->format('Y-m-d');
                break;
            case 'year':
                $desde = $hoy->modify('first day of January this year')->format('Y-m-d');
                break;
            case 'month':
            default:
                $desde = $hoy->modify('first day of this month')->format('Y-m-d');
                break;
        }

        $this->renderer->addKey('r_' . $rango, true);

        return [$desde, $hasta];
    }

    public function gestionarUsuarios()
    {
        $this->tienePermisoAdmin();

        $data = [
            'usuarios' => $this->model->obtenerUsuariosConRol(),
            'roles' => $this->model->obtenerRolesDisponibles()
        ];

        $this->renderer->render("adminUsuarios", $data);
    }

    public function cambiarRol()
    {
        $this->tienePermisoAdmin();

        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        $idRolNuevo = (int)($_POST['id_rol'] ?? 0);

        if ($idUsuario > 0 && $idRolNuevo > 0) {
            $exito = $this->model->cambiarRolUsuario($idUsuario, $idRolNuevo);
            $msg = $exito ? "Rol actualizado correctamente." : "Error al actualizar el rol o permiso denegado.";
        } else {
            $msg = "Datos inválidos para cambiar el rol.";
        }

        header("Location: /admin/gestionarUsuarios?msg=" . urlencode($msg));
        exit;
    }
}
