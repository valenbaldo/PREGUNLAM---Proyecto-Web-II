<?php
class LoginController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base(){
        $this->login();
    }

    public function login(){
        // Display login form
        $this->renderer->render("login", []);
    }

    public function registro(){
        // Display registration form
        $this->renderer->render("registro", []);
    }

    public function procesarLogin(){
        // Process login form
        if (isset($_POST['usuario']) && isset($_POST['contraseña'])) {
            $usuario = $_POST['usuario'];
            $contraseña = $_POST['contraseña'];
            
            // Validate login credentials using the model
            // This would need to be implemented in LoginModel
            $resultado = $this->model->validarLogin($usuario, $contraseña);
            
            if ($resultado) {
                // Set session and redirect to home
                $_SESSION['id_usuario'] = $resultado['id_usuario'];
                $_SESSION['nombreUsuario'] = $resultado['usuario'];
                $_SESSION['imagen'] = $resultado['imagen'];
                header("Location: /home");
                exit;
            } else {
                // Display error
                $this->renderer->render("login", ["error" => "Credenciales incorrectas"]);
            }
        }
    }

    public function procesarRegistro(){
        // Process registration form
        if (isset($_POST['submit'])) {
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $usuario = $_POST['usuario'];
            $nacimiento = $_POST['nacimiento'];
            $imagen = $_POST['imagen'];
            $sexo = $_POST['sexo'];
            $mail = $_POST['mail'];
            $pais = $_POST['pais'];
            $ciudad = $_POST['ciudad'];
            $contraseña = $_POST['contraseña'];
            $token = bin2hex(random_bytes(16)); // Generate random token
            
            $this->model->nuevo($nombre, $apellido, $usuario, $nacimiento, $imagen, $sexo, $mail, $pais, $ciudad, $contraseña, $token);
            
            // Redirect to success page or login
            $this->renderer->render("registroExitoso", []);
        }
    }

    public function logout(){
        session_destroy();
        header("Location: /login");
        exit;
    }
}
?>