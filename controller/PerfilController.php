<?php
class PerfilController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer) {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base() {
        $idUsuario = $_SESSION['id_usuario'] ?? null;
        if (!$idUsuario) {
            header("Location: /home");
            exit;
        }

        $usuario = $this->model->obtenerPerfil((int)$idUsuario);
        $stats   = $this->model->obtenerStats((int)$idUsuario);

        $data = [
            'usuario' => $usuario,
            'stats'   => $stats,
        ];

        $this->renderer->render("perfil", $data);
    }
}
