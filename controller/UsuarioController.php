
<?php

class UsuarioController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model   = $model;
        $this->renderer = $renderer;
    }


    public function base()
    {
        $this->estalogeado();
        $idUsuario = (int)$_SESSION['id_usuario'];

        $usuario = $this->model->obtenerDatosPorId($idUsuario) ?? [
            'id_usuario'       => $idUsuario,
            'nombre'           => $_SESSION['usuario'] ?? 'Usuario',
            'apellido'         => '',
            'username'         => $_SESSION['usuario'] ?? 'usuario',
            'email'            => $_SESSION['mail'] ?? '',
            'imagen'           => $_SESSION['imagen'] ?? '/imagenes/default.png',
            'fecha_nacimiento' => '',
        ];

        $stats = $this->model->obtenerStats($idUsuario);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';


        $perfilUrl = $scheme . '://' . $host . '/home?controller=usuario&method=ver&id=' . $idUsuario;


        $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
            . urlencode($perfilUrl);

        $data = [
            'usuario'    => $usuario,
            'stats'      => $stats,
            'logueado'   => true,
            'perfil_url' => $perfilUrl,
            'qr_src'     => $qrSrc,
            'es_publico' => false,
        ];


        $this->renderer->render("perfil", $data);
    }

    public function ver()
    {
        $idUsuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($idUsuario <= 0) {
            header("Location: /home");
            exit;
        }

        $usuario = $this->model->obtenerDatosPorId($idUsuario);
        if (!$usuario) {
            header("Location: /home");
            exit;
        }

        $stats = $this->model->obtenerStats($idUsuario);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $perfilUrl = $scheme . '://' . $host . '/usuario/ver/id=' . $idUsuario;

        $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
            . urlencode($perfilUrl);

        $data = [
            'usuario'    => $usuario,
            'stats'      => $stats,
            'perfil_url' => $perfilUrl,
            'qr_src'     => $qrSrc,
            'es_publico' => true,
            'logueado'   => isset($_SESSION['id_usuario']),
        ];

        $this->renderer->render("perfil", $data);
    }

    public function editarPerfil()
    {
        $this->renderer->render("editar_perfil");
    }

    public function verEstadisticas()
    {
        $this->estalogeado();
        $idUsuario = $_SESSION['id_usuario'];

        $historial    = $this->model->obtenerPartidas($idUsuario);
        $puntajeTotal = $this->model->obtenerPuntajeTotal($idUsuario);

        $data = [
            'historial'                 => $historial,
            'puntaje_total_acumulado'   => $puntajeTotal,
        ];

        $this->renderer->render("estadisticas", $data);
    }

    public function ranking()
    {
        $topJugadores = $this->model->obtenerRankingPorAverage(10);

        foreach ($topJugadores as $i => &$row) {
            $row['pos'] = $i + 1;
            if (empty($row['imagen'])) {
                $row['imagen'] = '/imagenes/default.png';
            }
            $row['average_formateado'] = round($row['average_aciertos_por_partida'], 2);
        }

        $data = [
            'ranking'        => $topJugadores,
            'has_ranking'    => count($topJugadores) > 0,
            'usuario_actual' => $_SESSION['usuario'] ?? null,
        ];

        $this->renderer->render("ranking", $data);
    }

    public function estalogeado()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: /login");
            exit;
        }
    }
}
