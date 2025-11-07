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
            if ($partidaActiva) {
                $this->juegoModel->marcarPartidaPerdida($partidaActiva['id_juego']);
            }
            unset($_SESSION['puntajeFinal']);
            unset($_SESSION['esCorrecta']);
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
        $user = $_SESSION['id_usuario'];
        $juego = $this->juegoModel->obtenerJuego($_SESSION['id_juego']);
        $nombreUsuario = $_SESSION['nombreUsuario'];
        
        $infoNivelAnterior = $this->usuarioModel->obtenerInfoCompleteNivel($user);
        $nivelAnterior = $infoNivelAnterior['nivel'];
        
        $puntajeFinal = $this->juegoModel->obtenerPuntajeJuego($_SESSION['id_juego']);
        
        $_SESSION['puntajeFinal'] = $puntajeFinal;
        $_SESSION['esCorrecta'] = false;
        $this->juegoModel->guardarPartida($puntajeFinal, $_SESSION['id_juego']);
        
        $infoNivelNuevo = $this->usuarioModel->obtenerInfoCompleteNivel($user);
        $nivelNuevo = $infoNivelNuevo['nivel'];
        $cambioNivel = $this->determinarCambioNivel($nivelAnterior, $nivelNuevo);
        
        $_SESSION['nivel_anterior'] = $nivelAnterior;
        $_SESSION['nivel_nuevo'] = $nivelNuevo;
        $_SESSION['cambio_nivel'] = $cambioNivel;
        
        header('Location: /juego/resultadoJuego');
        exit();
    }

    public function resultadoJuego(){
        $this->estalogeado();
        $nombreUsuario = $_SESSION['nombreUsuario'];
        
        $puntajeFinal = $_SESSION['puntajeFinal'] ?? 0;
        
        if (!$puntajeFinal && isset($_SESSION['id_juego'])) {
            $puntajeFinal = $this->juegoModel->obtenerPuntajeJuego($_SESSION['id_juego']);
        }
        
        $mensajeCambioNivel = '';
        if (isset($_SESSION['cambio_nivel']) && isset($_SESSION['nivel_anterior']) && isset($_SESSION['nivel_nuevo'])) {
            $cambio = $_SESSION['cambio_nivel'];
            $anterior = $_SESSION['nivel_anterior'];
            $nuevo = $_SESSION['nivel_nuevo'];
            
            if ($cambio === 'subio') {
                $mensajeCambioNivel = "¡Subiste de nivel! $anterior → $nuevo";
            } elseif ($cambio === 'bajo') {
                $mensajeCambioNivel = "Bajaste de nivel: $anterior → $nuevo";
            } else {
                $mensajeCambioNivel = "Mantuviste tu nivel: $nuevo";
            }
        }
        
        $data = [
            "nombreUsuario" => $nombreUsuario,
            "puntaje" => $puntajeFinal,
            "nivel_anterior" => $_SESSION['nivel_anterior'] ?? null,
            "nivel_nuevo" => $_SESSION['nivel_nuevo'] ?? null,
            "cambio_nivel" => $_SESSION['cambio_nivel'] ?? null,
            "mensaje_cambio_nivel" => $mensajeCambioNivel
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
            echo json_encode(['success'=>false,'error'=>'No autorizado']);
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
            echo json_encode(['success'=>false,'error'=>'Datos inválidos']);
            exit;
        }

        try {
            $res = $this->juegoModel->procesarRespuesta($idJuego, $user, $idPreg, $op);
            if (isset($res['error'])) {
                echo json_encode(['success'=>false,'error'=>$res['error']]);
                exit;
            }

            $puntajeActual = $this->juegoModel->obtenerPuntajeJuego($idJuego);

            if (empty($res['correct']) || $res['correct'] == false) {
                $_SESSION['esCorrecta'] = false;
                
                $infoNivelAnterior = $this->obtenerNivelSinPartidaActual($user, $idJuego);
                $nivelAnterior = $infoNivelAnterior['nivel'];
                
                $this->juegoModel->marcarPartidaPerdida($idJuego);
                $_SESSION['puntajeFinal'] = $puntajeActual;
                
                $infoNivelNuevo = $this->usuarioModel->obtenerInfoCompleteNivel($user);
                $nivelNuevo = $infoNivelNuevo['nivel'];
                
                $cambioNivel = $this->determinarCambioNivel($nivelAnterior, $nivelNuevo);
                
                // Guardar en sesión para mostrar en la vista de resultados
                $_SESSION['nivel_anterior'] = $nivelAnterior;
                $_SESSION['nivel_nuevo'] = $nivelNuevo;
                $_SESSION['cambio_nivel'] = $cambioNivel;
                
                echo json_encode([
                    'success'=>true,
                    'result'=>['correct' => false, 'correcta' => $res['correcta'], 'puntaje' => $puntajeActual],
                    'finalize'=>true,
                    'game_over'=>true,
                    'mensaje'=>'❌ Respuesta incorrecta. ¡Partida terminada!'
                    // NO enviar información de nivel aquí - solo se mostrará en resultados
                ]);
                exit;
            }

            // Si la respuesta es correcta - NO actualizar nivel durante partida
            $_SESSION['esCorrecta'] = true;
            
            // Devolver el resultado con puntaje actualizado (sin nivel)
            $res['puntaje'] = $puntajeActual;
            
            echo json_encode(['success'=>true,'result'=>$res,'finalize'=>false]);
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

    public function ajaxGetEstadoJuego()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $user = $_SESSION['id_usuario'] ?? null;
        $idJuego = intval($_POST['id_juego'] ?? $_SESSION['id_juego'] ?? 0);

        if (!$user || !$idJuego) {
            echo json_encode(['success'=>false,'error'=>'No autorizado']);
            exit;
        }

        try {
            $estado = $this->juegoModel->obtenerEstadoJuego($idJuego, $user);
            echo json_encode(['success'=>true,'data'=>$estado]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'error'=>'Error al obtener estado','msg'=>$e->getMessage()]);
        }
        exit;
    }

    public function ajaxValidarEstado()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $user = $_SESSION['id_usuario'] ?? null;
        $idJuego = intval($_POST['id_juego'] ?? $_SESSION['id_juego'] ?? 0);

        if (!$user || !$idJuego) {
            echo json_encode(['success'=>false,'error'=>'No autorizado']);
            exit;
        }

        try {
            $tienePreguntas = $this->juegoModel->tienePreguntasPendientes($idJuego, $user);
            
            echo json_encode([
                'success'=>true,
                'data'=>['pregunta_pendiente'=>$tienePreguntas]
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'error'=>'Error en validación','msg'=>$e->getMessage()]);
        }
        exit;
    }
    
    private function determinarCambioNivel($nivelAnterior, $nivelNuevo) {
        // Mapeo de niveles a números para comparar
        $niveles = ['facil' => 1, 'intermedia' => 2, 'dificil' => 3];
        
        $valorAnterior = $niveles[$nivelAnterior] ?? 1;
        $valorNuevo = $niveles[$nivelNuevo] ?? 1;
        
        if ($valorNuevo > $valorAnterior) {
            return 'subio';
        } elseif ($valorNuevo < $valorAnterior) {
            return 'bajo';
        } else {
            return 'mantuvo';
        }
    }
    
    private function obtenerNivelSinPartidaActual($id_usuario, $id_juego_actual) {
        // Obtener nivel excluyendo las respuestas de la partida actual
        return $this->usuarioModel->obtenerInfoCompleteNivelExcluyendoJuego($id_usuario, $id_juego_actual);
    }
    
    public function resetearPreguntasVistas()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $user = $_SESSION['id_usuario'] ?? null;
        $idJuego = intval($_POST['id_juego'] ?? 0);

        if (!$user || !$idJuego) {
            http_response_code(401);
            echo json_encode(['success'=>false,'error'=>'No autorizado']);
            exit;
        }

        try {
            $result = $this->juegoModel->resetearPreguntasVistas($idJuego, $user);
            if ($result) {
                echo json_encode(['success'=>true,'message'=>'Preguntas reseteadas correctamente']);
            } else {
                echo json_encode(['success'=>false,'error'=>'No se pudieron resetear las preguntas']);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Error interno: ' . $e->getMessage()]);
        }
        exit;
    }
}