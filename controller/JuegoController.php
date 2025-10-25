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

    public function iniciarJuego()
    {
        $this->model->iniciarJuego();
        header("Location: juego/iniciarJuego");
    }

    public function preguntar(){
        $idUsuario = $_SESSION['id_usuario'];
        $datosPregunta = $this->model->obtenerPreguntaParaMostrar($idUsuario);

        if($datosPregunta == null){
            $this->finalizarJuego();
        }
        $data = [
            "pregunta" => $datosPregunta,
            "puntaje" => $_SESSION['puntaje']
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
    $idUsuario = $_SESSION['id_usuario'];

    $resultado = $this->model->validarRespuesta($id_pregunta, $respuestaElegida,  $idUsuario);
    if($resultado['esCorrecta']){
    $this->model->actualizarPuntaje($idUsuario, $resultado['puntos_ganados']);
        header("Location: juego/preguntar");
        exit;

    }else{
        header("Location: juego/finjuego");
        exit;
    }
    }

    public function finalizarJuego()
    {
        $idUsuario = $_SESSION['id_usuario'];
        $puntajeFinal = $_SESSION['puntaje'];
        $this->model->guardarPartida($idUsuario, $puntajeFinal);
        $data = [
            "puntaje" => $puntajeFinal
        ];
        $this->renderer->render("resultado_juego", $data);

        unset($_SESSION['puntaje']);
        unset($_SESSION['preguntas_respondidas']);

    }

}