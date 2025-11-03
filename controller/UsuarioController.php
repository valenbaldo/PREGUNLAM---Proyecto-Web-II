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

        $idUsuario = $_SESSION['id_usuario'];

        $datosPerfil = $this->model->obtenerDatosPorId($idUsuario);

        $data = [
            'perfil' => $datosPerfil,
            'logueado' => true
        ];

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

        $data = [
            'ranking' => $topJugadores,
            'usuario_actual' => $_SESSION['usuario'] ?? null
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