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
        $this->model->nuevo($_POST["nombre"], $_POST["apellido"], $_POST["mail"], $ubicacion["address"]["country"], $ubicacion["address"]["state_district"], $hash, $token);

        $this->confirmacionDeUsuario($_POST["mail"], $token);

        $this->redirectToIndex();
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
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = 2;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'giancroci5@gmail.com';                     //SMTP username
            $mail->Password   = 'srqn bvvy uers apwr';                               //SMTP password
            $mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('giancroci5@gmail.com', 'Pregunlam');
            $mail->addAddress($mailUser);     //Add a recipient

            $enlace = 'http://localhost/login/verificarMail?token=' . $token;

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'TestMailPregunlam';
            $mail->Body    = '<a href="' . $enlace . '">Confirmar mi cuenta</a>';

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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
        $this->renderer->render("login");
    }

    public function loginPost()
    {
        $mail = $_POST["mail"];
        $contraseñaIntentada = $_POST["contraseña"];

        $usuarioEncontrado = $this->model->obtenerUsuario($mail);

        if ($usuarioEncontrado && password_verify($contraseñaIntentada, $usuarioEncontrado[0]["contraseña"]) && $usuarioEncontrado[0]["verificado"] == 1) {
            $this->renderer->render("pregunlam");
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

