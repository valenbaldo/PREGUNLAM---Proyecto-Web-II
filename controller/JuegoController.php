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

    /*public function iniciarJuego()
    {

        $this->renderer->render("juego");
        //header("Location: /juego");
    }*/

    public function iniciarJuego(){
        $id_usuario = $_SESSION['id_usuario'];
        if(!$_SESSION['esCorrecta']){
            $_SESSION['id_juego'] = $this->model->iniciarJuego($id_usuario);
        }
        $datosPregunta = $this->model->obtenerPreguntaParaMostrar($id_usuario);
        $juego = $this->model->obtenerJuego($_SESSION['id_juego']);

        if($datosPregunta == null){
            $this->finalizarJuego();
        }
        $data = [
            "pregunta" => $datosPregunta,
            "puntaje" => $juego[0]['puntaje']
        ];
        $this->renderer->render("juego", $data);
    }
    public function responder(){
    if(!isset($_POST['id_pregunta']) || !isset($_POST['respuesta_elegida'])){
        header("Location: juego/iniciarJuego");
        exit;
    }
    $id_pregunta = $_POST['id_pregunta'];
    $respuestaElegida = $_POST['respuesta_elegida'];
    $id_usuario = $_SESSION['id_usuario'];
        $datosPregunta = $this->model->obtenerPreguntaParaMostrar($id_usuario);
        $juego = $this->model->obtenerJuego($_SESSION['id_juego']);

    $resultado = $this->model->validarRespuesta($id_pregunta, $respuestaElegida,  $id_usuario);
    if($resultado['esCorrecta']){
    $this->model->actualizarPuntaje($resultado['puntos_ganados'], $_SESSION['id_juego']);
        $data = [
            "pregunta" => $datosPregunta,
            "puntaje" => $juego[0]['puntaje']
        ];
        $_SESSION['esCorrecta'] = $resultado['esCorrecta'];
        header("Location: /juego");
        //$this->renderer->render("juego", $data);

    }else{
        header("Location: juego/finjuego");
        exit;
    }
    }

    public function finalizarJuego()
    {
        $juego = $this->model->obtenerJuego($_SESSION['id_juego']);
        $idUsuario = $_SESSION['id_usuario'];
        $puntajeFinal = $juego[0]['puntaje'];
        $this->model->guardarPartida($idUsuario, $puntajeFinal);
        $data = [
            "puntaje" => $puntajeFinal
        ];
        $this->renderer->render("resultadoJuego", $data);

        unset($_SESSION['puntaje']);
        unset($_SESSION['preguntas_respondidas']);

    }

}