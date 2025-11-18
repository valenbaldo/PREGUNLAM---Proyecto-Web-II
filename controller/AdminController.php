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

        // Simplificado - sin rangos de tiempo específicos
        $desde = date('Y-01-01'); // Inicio del año actual
        $hasta = date('Y-m-d');   // Fecha actual

        $stats = [
            'usuarios_totales'      => $this->adminModel->contarUsuarios(),
            'partidas'              => $this->adminModel->contarPartidas(),
            'preguntas_totales'     => $this->adminModel->contarPreguntas(),
            'preguntas_creadas'     => $this->adminModel->contarPreguntasCreadas($desde, $hasta),
            'usuarios_nuevos'       => $this->adminModel->contarUsuariosNuevos($desde, $hasta),
            'reportes_pendientes'   => $this->reporteModel->contarReportesPendientes(),
        ];

        // Obtener filtros de la URL
        $year = $_GET['year'] ?? date('Y');
        $categoria = $_GET['categoria'] ?? null;

        // Datos para gráficos
        $partidasPorMes = $this->adminModel->partidasPorMes($year, $categoria);
        $preguntasPorCategoria = $this->adminModel->preguntasPorCategoria($desde, $hasta);
        $categorias = $this->adminModel->obtenerCategorias();

        // Marcar categorías y años seleccionados para los selectores
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
            // Datos para gráficos
            'partidasPorMes'         => $partidasPorMes,
            'preguntasPorCategoria'  => $preguntasPorCategoria,
            'rendimientoPorCategoria'=> $this->adminModel->rendimientoPorCategoria(),
            'categorias'             => $categorias,
            'yearSeleccionado'       => $year,
            'categoriaSeleccionada'  => $categoria,
            'year2024Selected'       => ($year == '2024'),
            'year2025Selected'       => ($year == '2025'),
            // JSON para JavaScript
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
            'roles' => $rolesDisponibles,
            'msg' => $_SESSION['msg'] ?? null,
            'error_flag' => $_SESSION['error_flag'] ?? false
        ];

        if (isset($_SESSION['msg'])) {
            unset($_SESSION['msg']);
            unset($_SESSION['error_flag']);
        }

        $this->renderer->render("adminUsuarios", $data);
    }

    public function cambiarRol()
    {
        $this->tienePermisoAdmin();

        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        $idRolNuevo = (int)($_POST['id_rol'] ?? 0);

        if ($idUsuario > 0 && $idRolNuevo > 0) {
            $exito = $this->adminModel->cambiarRolUsuario($idUsuario, $idRolNuevo);
            $_SESSION['msg'] = $exito ? "Rol actualizado correctamente." : "Error al actualizar el rol o permiso denegado.";
            $_SESSION['error_flag'] = !$exito;
        } else {
            $_SESSION['msg'] = "Datos inválidos para cambiar el rol.";
            $_SESSION['error_flag'] = true;
        }

        header("Location: /admin/gestionarUsuarios");
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

        // Obtener los mismos datos que en el método base
        [$desde, $hasta] = $this->resolverRango();

        $stats = [
            'usuarios_totales'      => $this->adminModel->contarUsuarios(),
            'partidas'              => $this->adminModel->contarPartidas(),
            'preguntas_totales'     => $this->adminModel->contarPreguntas(),
            'preguntas_creadas'     => $this->adminModel->contarPreguntasCreadas($desde, $hasta),
            'usuarios_nuevos'       => $this->adminModel->contarUsuariosNuevos($desde, $hasta),
            'reportes_pendientes'   => $this->reporteModel->contarReportesPendientes(),
        ];

        // Obtener filtros de la URL para PDF
        $year = $_GET['year'] ?? date('Y');
        $categoria = $_GET['categoria'] ?? null;

        // Datos para gráficos en PDF
        $partidasPorMes = $this->adminModel->partidasPorMes($year, $categoria);
        $preguntasPorCategoria = $this->adminModel->preguntasPorCategoria($desde, $hasta);

        $data = [
            'nombreUsuario'          => $_SESSION['nombreUsuario'] ?? 'Administrador',
            'stats'                  => $stats,
            'aciertos'               => $this->adminModel->aciertoPorUsuario(),
            'porPais'                => $this->adminModel->usuariosPorPais(),
            'porSexo'                => $this->adminModel->usuariosPorSexo(),
            'porEdad'                => $this->adminModel->usuariosPorGrupoEdad(),
            // Nuevos datos para gráficos en PDF
            'partidasPorMes'         => $partidasPorMes,
            'preguntasPorCategoria'  => $preguntasPorCategoria,
            'rendimientoPorCategoria'=> $this->adminModel->rendimientoPorCategoria(),
            'fecha_reporte'          => date('Y-m-d H:i:s'),
            'periodo'                => $this->obtenerNombrePeriodo(),
            'yearSeleccionado'       => $year,
            'categoriaSeleccionada'  => $categoria,
            // JSON para JavaScript
            'partidasPorMesJson'     => json_encode($partidasPorMes),
            'preguntasCategoriaJson' => json_encode($preguntasPorCategoria)
        ];

        // Renderizar vista especial para PDF
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
