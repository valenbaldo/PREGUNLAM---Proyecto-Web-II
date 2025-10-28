<?php

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader (created by composer, not included with PHPMailer)
require 'vendor/autoload.php';

class LoginController
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
        $this->login();
    }

    public function registro(){
        $this->renderer->render("registro");
    }

    public function nuevoUsuario()
    {
        $latitud = $_POST['latitud'];
        $longitud = $_POST['longitud'];
        $ubicacion = $this->geoLocalizacion($latitud, $longitud);
        $token = bin2hex(random_bytes(16));
        $contraseñaPlana = $_POST["contraseña"];
        $hash = password_hash($contraseñaPlana, PASSWORD_DEFAULT);
        $rutaImagen = dirname(__DIR__) . "/imagenes/" . $_POST['usuario'] . ".png";
        move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaImagen);
        $imagen = "/imagenes/" . $_POST['usuario'] . ".png";
        if ($_POST["contraseña"]===$_POST["confirmarContraseña"]){
            $this->model->nuevo($_POST["nombre"], $_POST["apellido"], $_POST["usuario"], $_POST["nacimiento"], $imagen, $_POST["sexo"], $_POST["mail"], $ubicacion["address"]["country"], $ubicacion["address"]["state_district"], $hash, $token);

            $this->confirmacionDeUsuario($_POST["mail"], $token);

            $this->renderer->render("registroExitoso");
        }else{
            die("Las contraseñas no coinciden");
        }


    }


    public function geoLocalizacion($latitud, $longitud){
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$latitud&lon=$longitud&addressdetails=1&accept-language=es";

        $options = [
            'http' => [
                'header' => "User-Agent: pregunlam/1.0 (giancroci5@gmail.com)\r\n"
            ]
        ];
        $context = stream_context_create($options);

        $response = @file_get_contents($url, false, $context);


        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    public function confirmacionDeUsuario($mailUser, $token){

        $mail = new PHPMailer(true);

        try {

            $mail->SMTPDebug = 0; // Desactívarlo para producción
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];                     // <-- CAMBIO
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];                     // <-- CAMBIO
            $mail->Password   = $_ENV['SMTP_PASS'];                     // <-- CAMBIO
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'];                   // <-- CAMBIO
            $mail->Port       = $_ENV['SMTP_PORT'];                     // <-- CAMBIO

            //Recipients
            $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_FROM_NAME']); // <-- CAMBIO
            $mail->addAddress($mailUser);     //Add a recipient

            $enlace = 'http://localhost/login/verificarMail?token=' . $token;

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'TestMailPregunlam';
            $mail->Body    = '<a href="' . $enlace . '">Confirmar mi cuenta</a>';

            $mail->send();
            echo 'El mensaje fue enviado correctamente.';
        } catch (Exception $e) {
            echo "El mensaje no se pudo enviar. Error: {$mail->ErrorInfo}";
        }
    }

    public function verificarMail()
    {
        if (!isset($_GET["token"])){
            die("Token no encontrado");
        }
        $token_recibido = $_GET['token'];
        $this->model->verificarMail($token_recibido);

        $this->renderer->render("mailVerificado");

    }

    public function login()
    {
        if($_SESSION['nombreUsuario']??null){
            header('location: /home');
            exit();
        }
        $this->renderer->render("login");
    }

    public function loginPost()
    {
        $mail = $_POST["mail"];
        $contraseñaIntentada = $_POST["contraseña"];

        $usuarioEncontrado = $this->model->obtenerUsuario($mail);

        if ($usuarioEncontrado && password_verify($contraseñaIntentada, $usuarioEncontrado[0]["contraseña"]) && $usuarioEncontrado[0]["verificado"] == 1) {
            $_SESSION['nombreUsuario'] = $usuarioEncontrado[0]["usuario"];
            $_SESSION['imagen'] = $usuarioEncontrado[0]["imagen"];
            $_SESSION['id_usuario'] = $usuarioEncontrado[0]["id_usuario"];
            $_SESSION['esCorrecta'] = false;
            header("Location: /home");
            exit;
        }elseif ($usuarioEncontrado && password_verify($contraseñaIntentada, $usuarioEncontrado[0]["contraseña"]) && $usuarioEncontrado[0]["verificado"] == 0){
            $data["error"] = "Mail no verificado";
            $this->renderer->render("login", $data);
        }else{
            $data["error"] = "Mail o contraseña incorrectos";
            $this->renderer->render("login", $data);
        }
    }


    public function logout()
    {
        session_destroy();
        $this->redirectToIndex();
    }

    public function redirectToIndex()
    {
        header("Location: /login");
        exit;
    }

}

