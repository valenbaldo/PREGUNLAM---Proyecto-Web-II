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

        $desde = date('Y-01-01');
        $hasta = date('Y-m-d');

        $stats = [
            'usuarios_totales'      => $this->adminModel->contarUsuarios(),
            'partidas'              => $this->adminModel->contarPartidas(),
            'preguntas_totales'     => $this->adminModel->contarPreguntas(),
            'preguntas_creadas'     => $this->adminModel->contarPreguntasCreadas($desde, $hasta),
            'usuarios_nuevos'       => $this->adminModel->contarUsuariosNuevos($desde, $hasta),
            'reportes_pendientes'   => $this->reporteModel->contarReportesPendientes(),
        ];

        $year = $_GET['year'] ?? date('Y');
        $categoria = $_GET['categoria'] ?? null;

        $partidasPorMes = $this->adminModel->partidasPorMes($year, $categoria);
        $preguntasPorCategoria = $this->adminModel->preguntasPorCategoria($desde, $hasta);
        $categorias = $this->adminModel->obtenerCategorias();

        foreach ($categorias as &$cat) {
            $cat['selected'] = ($categoria == $cat['categoria']);
        }

        $data = [
            'nombreUsuario'          => $_SESSION['nombreUsuario'] ?? 'Administrador',
            'stats'                  => $stats,
            'aciertos'               => $this->adminModel->aciertoPorUsuario(),
            'porPais'                => $this->adminModel->usuariosPorPais(),
            'porSexo'                => $this->adminModel->usuariosPorSexo(),
            'porEdad'                => $this->adminModel->usuariosPorGrupoEdad(),
            'partidasPorMes'         => $partidasPorMes,
            'preguntasPorCategoria'  => $preguntasPorCategoria,
            'rendimientoPorCategoria'=> $this->adminModel->rendimientoPorCategoria(),
            'categorias'             => $categorias,
            'yearSeleccionado'       => $year,
            'categoriaSeleccionada'  => $categoria,
            'year2024Selected'       => ($year == '2024'),
            'year2025Selected'       => ($year == '2025'),
            'partidasPorMesJson'     => json_encode($partidasPorMes),
            'preguntasCategoriaJson' => json_encode($preguntasPorCategoria)
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

    public function descargarPDF()
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

        $year = $_GET['year'] ?? date('Y');
        $categoria = $_GET['categoria'] ?? null;

        $partidasPorMes = $this->adminModel->partidasPorMes($year, $categoria);
        $preguntasPorCategoria = $this->adminModel->preguntasPorCategoria($desde, $hasta);

        $data = [
            'nombreUsuario'          => $_SESSION['nombreUsuario'] ?? 'Administrador',
            'stats'                  => $stats,
            'aciertos'               => $this->adminModel->aciertoPorUsuario(),
            'porPais'                => $this->adminModel->usuariosPorPais(),
            'porSexo'                => $this->adminModel->usuariosPorSexo(),
            'porEdad'                => $this->adminModel->usuariosPorGrupoEdad(),
            'partidasPorMes'         => $partidasPorMes,
            'preguntasPorCategoria'  => $preguntasPorCategoria,
            'rendimientoPorCategoria'=> $this->adminModel->rendimientoPorCategoria(),
            'fecha_reporte'          => date('Y-m-d H:i:s'),
            'periodo'                => $this->obtenerNombrePeriodo(),
            'yearSeleccionado'       => $year,
            'categoriaSeleccionada'  => $categoria,
            'partidasPorMesJson'     => json_encode($partidasPorMes),
            'preguntasCategoriaJson' => json_encode($preguntasPorCategoria)
        ];
        $this->renderer->renderStandalone("adminPanelPDF", $data);
    }

    private function obtenerNombrePeriodo()
    {
        $rango = $_GET['r'] ?? 'day';
        switch ($rango) {
            case 'day': return 'Hoy';
            case 'week': return 'Esta semana';
            case 'month': return 'Este mes';
            case 'year': return 'Este año';
            default: return 'Hoy';
        }
    }
}
