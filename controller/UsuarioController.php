<?php

class UsuarioController
{
    private $usuarioModel;
    private $renderer;

    public function __construct($usuarioModel, $renderer)
    {
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
    }
    public function base()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: /login");
            exit;
        }

        $idUsuario = $_SESSION['id_usuario'];

        $datosPerfil = $this->usuarioModel->obtenerDatosPorId($idUsuario);

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
}