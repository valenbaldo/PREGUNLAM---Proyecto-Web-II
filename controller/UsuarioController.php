<?php

class UsuarioController
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
        $this->estalogeado();
        $idUsuario = (int)$_SESSION['id_usuario'];

        $usuario = $this->model->obtenerDatosPorId($idUsuario) ?? [
            'id_usuario'       => $idUsuario,
            'nombre'           => $_SESSION['usuario'] ?? 'Usuario',
            'apellido'         => '',
            'username'         => $_SESSION['usuario'] ?? 'usuario',
            'email'            => $_SESSION['mail'] ?? '',
            'imagen'           => $_SESSION['imagen'] ?? '/imagenes/default.png',
            'fecha_nacimiento' => '',
        ];

        $stats = $this->model->obtenerStats($idUsuario);

        $data = [
            'usuario' => $usuario,   // 👈 coincide con la vista
            'stats'   => $stats,     // 👈 coincide con la vista
            'logueado'=> true
        ];

        // tu renderer suele mapear "perfil" -> vista/perfilVista.mustache
        $this->renderer->render("perfil", $data);
    }
    public function editarPerfil()
    {
        $this->renderer->render("editar_perfil");
    }
    public function verEstadisticas() {
        $this->estalogeado();
        $idUsuario = $_SESSION['id_usuario'];

        $historial = $this->model->obtenerPartidas($idUsuario);
        $puntajeTotal = $this->model->obtenerPuntajeTotal($idUsuario);

        $data = [
            'historial' => $historial,
            'puntaje_total_acumulado' => $puntajeTotal,
        ];

        $this->renderer->render("estadisticas", $data);
    }
    public function ranking()
    {
        $topJugadores = $this->model->obtenerRankingAcumulado(10);

        // agrego posición y un fallback de imagen
        foreach ($topJugadores as $i => &$row) {
            $row['pos'] = $i + 1; // 👈 reemplaza al @index_plus_one
            if (empty($row['imagen'])) {
                $row['imagen'] = '/imagenes/default.png';
            }
        }

        $data = [
            'ranking'        => $topJugadores,            // lista simple
            'has_ranking'    => count($topJugadores) > 0, // 👈 flag para la vista
            'usuario_actual' => $_SESSION['usuario'] ?? null,
        ];

        $this->renderer->render("ranking", $data);
    }


    public function estalogeado(){
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: /login");
            exit;
        }
    }
}