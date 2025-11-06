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

        $id_usuario     = $_SESSION['id_usuario'];
        $nombreUsuario  = $_SESSION['nombreUsuario'];

        $partidaActiva = $this->juegoModel->obtenerJuegoActivo($id_usuario);
        
        if ($partidaActiva && $partidaActiva['estado'] === 'activo') {
            $_SESSION['id_juego'] = $partidaActiva['id_juego'];
        } else {
            $_SESSION['id_juego'] = $this->juegoModel->iniciarJuego($id_usuario);
        }

        $id_juego = $_SESSION['id_juego'];

        $infoNivel = $this->usuarioModel->obtenerInfoCompleteNivel($id_usuario);
        $datosPregunta = $this->juegoModel->obtenerPreguntaPorNivel($id_usuario, $infoNivel['nivel'], $id_juego);
        
        if ($datosPregunta == null) {
            $data = [
                "error" => "🎉 ¡Felicitaciones! Has visto todas las preguntas disponibles.",
                "mensaje" => "Has completado todo el contenido del juego. Puedes:",
                "opciones" => [
                    "• Revisar tu perfil y estadísticas",
                    "• Competir en el ranking con otros jugadores", 
                    "• Contactar al administrador para más contenido"
                ],
                "nombreUsuario" => $nombreUsuario
            ];
            $this->renderer->render("juego", $data);
            return;
        }

        $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);
        $puntajeActual = $this->juegoModel->obtenerPuntajeJuego($_SESSION['id_juego']);
        $respondidas = 0;

        $data = [
            "id_juego"      => $_SESSION['id_juego'],
            "pregunta"      => $datosPregunta,
            "nombreUsuario" => $nombreUsuario,
            "nivel_info"    => $infoNivel,
            "puntaje"       => $puntajeActual,
            "respondidas"   => $respondidas,
        ];
        $this->renderer->render("juego", $data);
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

    public function abandonarPartida(){
        $this->estalogeado();
        $id_usuario = $_SESSION['id_usuario'];
        if (isset($_SESSION['id_juego']) && $_SESSION['id_juego']) {
            $this->juegoModel->marcarPartidaPerdida($_SESSION['id_juego']);
        }
        unset($_SESSION['id_juego']);
        unset($_SESSION['puntajeFinal']);
        unset($_SESSION['esCorrecta']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Partida abandonada']);
        exit();
    }

    public function nuevaPartida(){
        $this->estalogeado();
        $id_usuario = $_SESSION['id_usuario'];
        $partidaActiva = $this->juegoModel->obtenerJuegoActivo($id_usuario);
        if ($partidaActiva) {
            $this->juegoModel->guardarPartida($partidaActiva['puntaje'], $partidaActiva['id_juego']);
        }
        unset($_SESSION['id_juego']);
        unset($_SESSION['puntajeFinal']);
        $_SESSION['esCorrecta'] = false;
        header('Location: /juego');
        exit();
    }

    public function estalogeado(){
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: /login");
            exit;
        }
    }

    public function ajaxRuleta()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $user = $_SESSION['id_usuario'] ?? null;
        $idJuego = intval($_POST['id_juego'] ?? 0);

        if (!$user || !$idJuego) {
            http_response_code(401);
            echo json_encode(['success'=>false,'error'=>'No autorizado','session_user'=>$user,'id_juego'=>$idJuego]);
            exit;
        }

        $infoNivel = $this->usuarioModel->obtenerInfoCompleteNivel($user);
        $data = $this->juegoModel->girarRuleta($idJuego, $user, $infoNivel['nivel']);
        if (isset($data['error'])) {
            echo json_encode(['success'=>false,'error'=>$data['error']]);
            exit;
        }
        echo json_encode(['success'=>true,'data'=>$data]);
        exit;
    }

    public function ajaxResponder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $user = $_SESSION['id_usuario'] ?? null;
        $idJuego = intval($_POST['id_juego'] ?? $_SESSION['id_juego'] ?? 0);
        $idPreg = intval($_POST['id_pregunta'] ?? 0);
        $op = $_POST['opcion'] ?? $_POST['respuesta_elegida'] ?? '';

        if (!$user || !$idJuego || !$idPreg || !$op) {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'Datos inválidos','received'=>$_POST,'session'=>$_SESSION]);
            exit;
        }

        try {
            $res = $this->juegoModel->procesarRespuesta($idJuego, $user, $idPreg, $op);
            if (isset($res['error'])) {
                echo json_encode(['success'=>false,'error'=>$res['error']]);
                exit;
            }

            $infoNivel = $this->usuarioModel->obtenerInfoCompleteNivel($user);

            if (!empty($res['correct'])) {
                $_SESSION['esCorrecta'] = true;
                echo json_encode(['success'=>true,'result'=>$res,'finalize'=>false,'nivel_info'=>$infoNivel]);
                exit;
            }

            $_SESSION['esCorrecta'] = false;
            $_SESSION['puntajeFinal'] = $res['puntaje'] ?? ($this->juegoModel->obtenerPuntajeJuego($idJuego) ?? 0);
            $this->juegoModel->guardarPartida($_SESSION['puntajeFinal'], $idJuego);

            echo json_encode(['success'=>true,'result'=>$res,'finalize'=>true,'redirect'=>'/juego/resultadoJuego','nivel_info'=>$infoNivel]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Error interno','msg'=>$e->getMessage()]);
            exit;
        }
    }

    public function ajaxGetJuego()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $sess = [
            'PHPSESSID' => session_id(),
            'id_usuario' => $_SESSION['id_usuario'] ?? null,
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? null,
            'id_juego_session' => $_SESSION['id_juego'] ?? null
        ];

        if (empty($sess['id_usuario'])) {
            echo json_encode(['success'=>false,'error'=>'Sesión sin usuario','session'=>$sess]);
            exit;
        }

        if (!empty($sess['id_juego_session'])) {
            echo json_encode(['success'=>true,'data'=>['id_juego' => (int)$sess['id_juego_session']],'session'=>$sess]);
            exit;
        }

        $idUsuario = intval($sess['id_usuario']);
        $res = $this->juegoModel->obtenerJuegoActivo($idUsuario);

        echo json_encode([
            'success' => (bool)$res,
            'data' => $res ? ['id_juego' => (int)$res['id_juego']] : null,
            'session' => $sess,
            'db_result' => $res ?: null
        ]);
        exit;
    }
}