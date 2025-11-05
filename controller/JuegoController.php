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


        if (!($_SESSION['esCorrecta'] ?? false)) {
            $_SESSION['id_juego'] = $this->juegoModel->iniciarJuego($id_usuario);
        }


        if (!isset($_SESSION['preguntaPendiente'])) {
            $nivelUsuario  = $this->usuarioModel->obtenerNivelUsuario($id_usuario);
            $datosPregunta = $this->juegoModel->obtenerPreguntaPorNivel($id_usuario, $nivelUsuario);
        } else {
            $datosPregunta = $_SESSION['preguntaPendiente'];
        }


        if ($datosPregunta == null) {
            $this->finalizarJuego();
            return;
        }


        $_SESSION['preguntaPendiente'] = $datosPregunta;


        $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);


        $respondidas = 0;
        if (!empty($_SESSION['preguntas_respondidas'])) {
            $respondidas = max(0, count($_SESSION['preguntas_respondidas']) - 1);
        }


        $data = [
            "pregunta"      => $datosPregunta,
            "nombreUsuario" => $nombreUsuario,
            "puntaje"       => $juego[0]['puntaje'],
            "respondidas"   => $respondidas,  // 👈 acá va el contador
        ];
        $this->renderer->render("juego", $data);
    }

    public function responder(){
        $this->estalogeado();

        // aceptar ambos nombres que pueda enviar el cliente
        $id_pregunta = intval($_POST['id_pregunta'] ?? 0);
        $respuestaElegida = $_POST['respuesta_elegida'] ?? $_POST['opcion'] ?? null;

        $id_usuario = $_SESSION['id_usuario'] ?? null;
        if (!$id_pregunta || !$respuestaElegida || !$id_usuario) {
            // petición inválida
            header("Location: /juego");
            exit();
        }

        $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);
        $resultado = $this->juegoModel->validarRespuesta($id_pregunta, $respuestaElegida, $id_usuario);

        // limpiar pregunta pendiente para que el controlador cargue la siguiente
        unset($_SESSION['preguntaPendiente']);

        $estado = $juego[0]['estado'] ?? null;

        if (!empty($resultado['esCorrecta']) && $estado === 'activo') {
            // acierto: sumar puntaje y avanzar a la siguiente pregunta
            $this->juegoModel->actualizarPuntaje($resultado['puntos_ganados'], $_SESSION['id_juego']);
            $_SESSION['esCorrecta'] = true;
            header("Location: /juego");
            exit();
        } else {
            // fallo: finalizar partida
            $_SESSION['esCorrecta'] = false;
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

    // AJAX: servidor decide categoría y pregunta (ruleta)
    public function ajaxRuleta()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        // debug rápido en error log
        error_log('ajaxRuleta hit. session_id=' . session_id());

        $user = $_SESSION['id_usuario'] ?? null;
        $idJuego = intval($_POST['id_juego'] ?? 0);

        if (!$user || !$idJuego) {
            http_response_code(401);
            echo json_encode(['success'=>false,'error'=>'No autorizado','session_user'=>$user,'id_juego'=>$idJuego]);
            exit;
        }

        $data = $this->juegoModel->girarRuleta($idJuego, $user);
        if (isset($data['error'])) {
            echo json_encode(['success'=>false,'error'=>$data['error']]);
            exit;
        }
        echo json_encode(['success'=>true,'data'=>$data]);
        exit;
    }

    // AJAX: procesar respuesta enviada por el cliente
    public function ajaxResponder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        error_log('ajaxResponder hit. session_id=' . session_id());
        error_log('POST=' . json_encode($_POST));
        error_log('_SESSION=' . json_encode($_SESSION));

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

            // si la respuesta es correcta: limpiar pregunta pendiente y devolver puntaje actualizado
            if (!empty($res['correct'])) {
                unset($_SESSION['preguntaPendiente']);
                $_SESSION['esCorrecta'] = true;
                echo json_encode(['success'=>true,'result'=>$res,'finalize'=>false]);
                exit;
            }

            // respuesta incorrecta: finalizar partida en servidor y devolver instrucción al cliente
            $_SESSION['esCorrecta'] = false;
            $_SESSION['puntajeFinal'] = $res['puntaje'] ?? ($this->juegoModel->obtenerPuntajeJuego($idJuego) ?? 0);
            // guardar partida (misma llamada que en finalizarJuego)
            $this->juegoModel->guardarPartida($_SESSION['puntajeFinal'], $idJuego);

            echo json_encode(['success'=>true,'result'=>$res,'finalize'=>true,'redirect'=>'/juego/resultadoJuego']);
            exit;

        } catch (\Throwable $e) {
            error_log('ajaxResponder exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Error interno','msg'=>$e->getMessage()]);
            exit;
        }
    }

    // AJAX: obtener juego activo
    public function ajaxGetJuego()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        // debug: volcar sesión en error_log
        error_log("ajaxGetJuego hit. session_id=" . session_id());
        error_log("ajaxGetJuego _SESSION=" . json_encode($_SESSION));

        $sess = [
            'PHPSESSID' => session_id(),
            'id_usuario' => $_SESSION['id_usuario'] ?? null,
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? null,
            'id_juego_session' => $_SESSION['id_juego'] ?? null
        ];

        // Si no hay usuario en sesión devolvemos debug para que inspecciones
        if (empty($sess['id_usuario'])) {
            echo json_encode(['success'=>false,'error'=>'Sesión sin usuario','session'=>$sess]);
            exit;
        }

        // devolver id_juego de sesión si existe
        if (!empty($sess['id_juego_session'])) {
            echo json_encode(['success'=>true,'data'=>['id_juego' => (int)$sess['id_juego_session']],'session'=>$sess]);
            exit;
        }

        // fallback: buscar partida activa en BD
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