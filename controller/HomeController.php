<?php
class HomeController
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
        $this->home();
    }

    public function home()
    {
        $this->estalogeado();
        $nombreUsuario = $_SESSION['nombreUsuario'];
        $imagen = $_SESSION['imagen'];
        $data = [
            "nombreUsuario" => $nombreUsuario,
            "imagen" => $imagen
        ];
        $this->renderer->render("home", $data);
    }

    public function estalogeado(){
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: /login");
            exit;
        }
    }

}