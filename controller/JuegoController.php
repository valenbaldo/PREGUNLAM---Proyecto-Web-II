<?php

class JuegoController
{
private $model;
private $renderer;

public function __construct($model, $renderer){
    $this->model = $model;
    $this->renderer = $renderer;

}

public function base(){
    $this->iniciarJuego();
}


    public function iniciarJuego(){
        $id_usuario = $_SESSION['id_usuario'];
        $nombreUsuario = $_SESSION['nombreUsuario'];
        if(!$_SESSION['esCorrecta']){
            $_SESSION['id_juego'] = $this->model->iniciarJuego($id_usuario);
        }
        $datosPregunta = $this->model->obtenerPreguntaParaMostrar($id_usuario);
        $juego = $this->model->obtenerJuego($_SESSION['id_juego']);

        if($datosPregunta == null){
            $this->finalizarJuego();
        }else{
            $data = [
                "pregunta" => $datosPregunta,
                "nombreUsuario" => $nombreUsuario,
                "puntaje" => $juego[0]['puntaje']
            ];
            $this->renderer->render("juego", $data);
        }

    }
    public function responder(){
    $id_pregunta = $_POST['id_pregunta'];
    $respuestaElegida = $_POST['respuesta_elegida'];
    $id_usuario = $_SESSION['id_usuario'];
    $juego = $this->model->obtenerJuego($_SESSION['id_juego']);

    $resultado = $this->model->validarRespuesta($id_pregunta, $respuestaElegida,  $id_usuario);
    $estado = $juego[0]['estado'];
    if($resultado['esCorrecta'] && $estado == 'activo'){
        $this->model->actualizarPuntaje($resultado['puntos_ganados'], $_SESSION['id_juego']);
        $_SESSION['esCorrecta'] = $resultado['esCorrecta'];
        header("Location: /juego");
        exit();
    }
    }

    public function finalizarJuego()
    {
        $juego = $this->model->obtenerJuego($_SESSION['id_juego']);
        $nombreUsuario = $_SESSION['nombreUsuario'];
        $puntajeFinal = $juego[0]['puntaje'];
        $_SESSION['puntajeFinal'] = $puntajeFinal;
        $_SESSION['esCorrecta'] = false;
        $this->model->guardarPartida($puntajeFinal, $_SESSION['id_juego']);
        $data = [
            "nombreUsuario" => $nombreUsuario,
            "puntaje" => $puntajeFinal
        ];
        header('Location: /juego/resultadoJuego');
        exit();
        //$this->renderer->render("resultadoJuego", $data);
    }

    public function resultadoJuego(){
        $nombreUsuario = $_SESSION['nombreUsuario'];
        $puntajeFinal = $_SESSION['puntajeFinal'];
        $data = [
            "nombreUsuario" => $nombreUsuario,
            "puntaje" => $puntajeFinal
        ];
        $this->renderer->render("resultadoJuego", $data);
    }
}