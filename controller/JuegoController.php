<?php

class JuegoController
{
private $juegoModel;

private $usuarioModel;
private $renderer;

public function __construct($juegoModel, $usuarioModel, $renderer){
    $this->juegoModel = $juegoModel;
    $this->usuarioModel = $usuarioModel;
    $this->renderer = $renderer;

}

public function base(){
    $this->iniciarJuego();
}


    public function iniciarJuego(){

        $this->estalogeado();
        $id_usuario = $_SESSION['id_usuario'];
        $nombreUsuario = $_SESSION['nombreUsuario'];
        if(!$_SESSION['esCorrecta']){
            $_SESSION['id_juego'] = $this->juegoModel->iniciarJuego($id_usuario);
        }
        if (!isset($_SESSION['preguntaPendiente'])){
            $nivelUsuario = $this->usuarioModel->obtenerNivelUsuario($id_usuario);
            $datosPregunta = $this->juegoModel->obtenerPreguntaPorNivel($id_usuario, $nivelUsuario);
        }else
        {
            $datosPregunta = $_SESSION['preguntaPendiente'];
        }

        $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);

        if($datosPregunta == null){
            $this->finalizarJuego();
        }else{
            $_SESSION['preguntaPendiente'] = $datosPregunta;
            $data = [
                "pregunta" => $datosPregunta,
                "nombreUsuario" => $nombreUsuario,
                "puntaje" => $juego[0]['puntaje']
            ];
            $this->renderer->render("juego", $data);
        }

    }
    public function responder(){
        $this->estalogeado();
    $id_pregunta = $_POST['id_pregunta'];
    $respuestaElegida = $_POST['respuesta_elegida'];
    $id_usuario = $_SESSION['id_usuario'];
    $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);

    $resultado = $this->juegoModel->validarRespuesta($id_pregunta, $respuestaElegida,  $id_usuario);
    unset($_SESSION['preguntaPendiente']);
    $estado = $juego[0]['estado'];
    if($resultado['esCorrecta'] && $estado == 'activo'){
        $this->juegoModel->actualizarPuntaje($resultado['puntos_ganados'], $_SESSION['id_juego']);
        $_SESSION['esCorrecta'] = $resultado['esCorrecta'];
        header("Location: /juego");
        exit();
    }else{
        $this->finalizarJuego();
    }
    }

    public function finalizarJuego()
    {
        $this->estalogeado();
        $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);
        $nombreUsuario = $_SESSION['nombreUsuario'];
        $puntajeFinal = $juego[0]['puntaje'];
        $_SESSION['puntajeFinal'] = $puntajeFinal;
        $_SESSION['esCorrecta'] = false;
        $this->juegoModel->guardarPartida($puntajeFinal, $_SESSION['id_juego']);
        header('Location: /juego/resultadoJuego');
        exit();
    }

    public function resultadoJuego(){
        $this->estalogeado();
        $nombreUsuario = $_SESSION['nombreUsuario'];
        $puntajeFinal = $_SESSION['puntajeFinal'];
        $data = [
            "nombreUsuario" => $nombreUsuario,
            "puntaje" => $puntajeFinal
        ];
        $this->renderer->render("resultadoJuego", $data);
    }

    public function estalogeado(){
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: /login");
            exit;
        }
    }
}