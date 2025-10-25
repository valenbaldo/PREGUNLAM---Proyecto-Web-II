<?php
include_once("helper/MyConexion.php");
include_once("helper/IncludeFileRenderer.php");
include_once("helper/NewRouter.php");
include_once("controller/LoginController.php");
include_once("controller/JuegoController.php");
include_once("controller/UsuarioController.php");
include_once("controller/HomeController.php");
include_once("model/LoginModel.php");
include_once("model/HomeModel.php");
include_once ("helper/MustacheRenderer.php");
include_once ("vendor/autoload.php");

// Cargar las variables de .env
$projectRoot = dirname(__DIR__);
$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->load();


class ConfigFactory
{
    private $config;
    private $objetos;

    private $conexion;
    private $renderer;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");

        $this->conexion= new MyConexion(
            $this->config["server"],
            $this->config["user"],
            $this->config["pass"],
            $this->config["database"]
        );

        $this->renderer = new MustacheRenderer("vista");

        $this->objetos["router"] = new NewRouter($this, "LoginController", "base");

        $this->objetos["LoginController"] = new LoginController(new LoginModel($this->conexion), $this->renderer);

        $this->objetos["HomeController"] = new HomeController(new HomeModel($this->conexion), $this->renderer);

        $this->objetos["JuegoController"] = new JuegoController(new JuegoModel($this->conexion), $this->renderer);

        $this->objetos["UsuarioController"] = new UsuarioController(new usuarioModel($this->conexion), $this->renderer);

    }

    public function get($objectName)
    {
        return $this->objetos[$objectName];
    }
}