<?php

class AdminController
{
    private $adminModel;

    private $reporteModel;
    private $renderer;

    public function __construct($adminModel, $reporteModel, $renderer)
    {
        $this->reporteModel = $reporteModel;
        $this->adminModel = $adminModel;
        $this->renderer = $renderer;
    }


    public function base()
    {
        $this->tienePermisoAdmin();

        [$desde, $hasta] = $this->resolverRango();

        $stats = [
            'usuarios_totales'      => $this->adminModel->contarUsuarios(),
            'partidas'              => $this->adminModel->contarPartidas(),
            'preguntas_totales'     => $this->adminModel->contarPreguntas(),
            'preguntas_creadas'     => $this->adminModel->contarPreguntasCreadas($desde, $hasta),
            'usuarios_nuevos'       => $this->adminModel->contarUsuariosNuevos($desde, $hasta),
            'reportes_pendientes'   => $this->reporteModel->contarReportesPendientes(),
        ];

        $data = [
            'nombreUsuario'          => $_SESSION['nombreUsuario'] ?? 'Administrador',
            'stats'                  => $stats,
            'aciertos'               => $this->adminModel->aciertoPorUsuario(),
            'porPais'                => $this->adminModel->usuariosPorPais(),
            'porSexo'                => $this->adminModel->usuariosPorSexo(),
            'porEdad'                => $this->adminModel->usuariosPorGrupoEdad()
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

        $usuarios = $this->adminModel->obtenerUsuariosConRol();
        $rolesDisponibles = $this->adminModel->obtenerRolesDisponibles();

        foreach ($usuarios as &$usuario) {
            $opcionesRol = [];

            foreach ($rolesDisponibles as $rol) {
                $rolOpcion = [
                    'id_rol' => $rol['id_rol'],
                    'nombre' => $rol['nombre'],
                ];

                if ($rol['id_rol'] == $usuario['id_rol']) {
                    $rolOpcion['seleccionado'] = true;
                }

                $opcionesRol[] = $rolOpcion;
            }

            $usuario['opciones_rol'] = $opcionesRol;
        }

        $data = [
            'usuarios' => $usuarios,
            'roles' => $rolesDisponibles
        ];

        $this->renderer->render("adminUsuarios", $data);
    }

    public function cambiarRol()
    {
        $this->tienePermisoAdmin();

        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        $idRolNuevo = (int)($_POST['id_rol'] ?? 0);

        if ($idUsuario > 0 && $idRolNuevo > 0) {
            $exito = $this->adminModel->cambiarRolUsuario($idUsuario, $idRolNuevo);
            $msg = $exito ? "Rol actualizado correctamente." : "Error al actualizar el rol o permiso denegado.";
        } else {
            $msg = "Datos inválidos para cambiar el rol.";
        }

        header("Location: /admin/gestionarUsuarios?msg=" . urlencode($msg));
        exit;
    }
    public function gestionarReportes()
    {
        $this->tienePermisoAdmin();

        $reportes = $this->reporteModel->obtenerReportesPendientes();

        $data = [
            'reportes' => $reportes,
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? 'Administrador',
        ];

        $this->renderer->render("adminReportes", $data);
    }
}
