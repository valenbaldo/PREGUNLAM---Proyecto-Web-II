<script>
(function(){
  let idJuego = 0;
  const gameHeader = document.getElementById('gameHeader');
  if (gameHeader) idJuego = parseInt(gameHeader.getAttribute('data-id')||'0',10) || 0;

  const spinEl = document.getElementById('wheel');
  const mensajeEl = document.getElementById('mensaje');
  const preguntaBox = document.getElementById('preguntaBox');
  const preguntaTexto = document.getElementById('preguntaTexto');
  const opcionesEl = document.getElementById('opciones');
  const puntajeEl = document.getElementById('puntaje');

  async function fetchJson(route, body){
    const res = await fetch(route, {
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body: new URLSearchParams(body).toString()
    });
    const text = await res.text();
    try { return JSON.parse(text); } catch(e){ return { success:false, __rawStatus: res.status, __rawBody: text }; }
  }

  async function obtenerIdJuegoServidor(){
    const r = await fetchJson('index.php?controller=juego&method=ajaxGetJuego', {});
    if (r && r.success && r.data && r.data.id_juego) {
      idJuego = parseInt(r.data.id_juego,10);
      if (gameHeader) gameHeader.setAttribute('data-id', idJuego);
      return true;
    }
    return false;
  }

  async function ajaxRuletaCall(){
    if (!idJuego) {
      const ok = await obtenerIdJuegoServidor();
      if (!ok) { mensajeEl.textContent = 'No hay partida activa.'; return null; }
    }
    return await fetchJson('index.php?controller=juego&method=ajaxRuleta', { id_juego: idJuego });
  }

  async function ajaxResponderCall(payload){
    return await fetchJson('index.php?controller=juego&method=ajaxResponder', payload);
  }

  spinEl.addEventListener('click', async () => {
    mensajeEl.textContent = 'Girando...';
    const res = await ajaxRuletaCall();
    if (!res || !res.success) { mensajeEl.textContent = res && res.error ? res.error : 'Error al girar'; console.log(res); return; }
    const p = res.data.pregunta;
    preguntaTexto.textContent = p.pregunta;
    opcionesEl.innerHTML = '';
    ['A','B','C','D'].forEach(k=>{
      const btn = document.createElement('button');
      btn.textContent = k + ') ' + (p.opciones[k]||'');
      btn.dataset.opt = k;
      btn.addEventListener('click', async (e)=>{
        Array.from(opcionesEl.children).forEach(b=>b.disabled=true);
        mensajeEl.textContent = 'Enviando respuesta...';
        const r2 = await ajaxResponderCall({ id_juego: idJuego, id_pregunta: p.id_pregunta, opcion: e.currentTarget.dataset.opt });
        console.log('ajaxResponder resp', r2);
        if (!r2 || !r2.success) { mensajeEl.textContent = r2 && r2.error ? r2.error : 'Error al responder'; Array.from(opcionesEl.children).forEach(b=>b.disabled=false); return; }
        const result = r2.result || {};
        if (r2.finalize === true) {
          // partida finalizada por fallo: redirigir a resultado o mostrar modal
          window.location.href = r2.redirect || '/juego/resultadoJuego';
          return;
        }
        // respuesta correcta: actualizar puntaje y ocultar pregunta para seguir
        mensajeEl.textContent = result.correct ? '¡Correcto!' : ('Incorrecto. Correcta: '+(result.correcta||'?'));
        puntajeEl.textContent = result.puntaje ?? puntajeEl.textContent;
        preguntaBox.classList.add('hidden');
      });
      opcionesEl.appendChild(btn);
    });
    preguntaBox.classList.remove('hidden');
    mensajeEl.textContent = '';
  });
})();

<?php

class JuegoModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // INICIO: métodos nuevos para ruleta + respuesta segura
    /**
     * Girar la ruleta: el servidor elige una categoría aleatoria y devuelve
     * una pregunta válida para la partida. Registra la pregunta en juego_preguntas.
     */
    public function girarRuleta(int $id_juego, int $id_usuario)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);

        // verificar partida y pertenencia (simple: id_usuario en juegos)
        $g = $this->conexion->query("SELECT * FROM juegos WHERE id_juego = $id_juego LIMIT 1");
        if (!$g || !isset($g[0])) {
            return ['error' => 'Partida no encontrada'];
        }
        // si la partida almacena id_usuario como propietario
        if ((int)$g[0]['id_usuario'] !== $id_usuario) {
            return ['error' => 'No autorizado para jugar esta partida'];
        }

        // elegir categoría aleatoria
        $cats = $this->conexion->query("SELECT id_categoria, nombre FROM categorias");
        if (!$cats || count($cats) === 0) return ['error' => 'No hay categorías definidas'];
        $cat = $cats[array_rand($cats)];
        $id_cat = intval($cat['id_categoria']);

        // obtener preguntas ya usadas en esta partida
        $usedRows = $this->conexion->query("SELECT id_pregunta FROM juego_preguntas WHERE id_juego = $id_juego");
        $used = [];
        if ($usedRows) foreach ($usedRows as $r) $used[] = intval($r['id_pregunta']);
        $excluir = count($used) ? implode(',', $used) : '0';

        // seleccionar una pregunta de la categoría no usada en la partida
        $sql = "SELECT p.id_pregunta, p.pregunta, r.a, r.b, r.c, r.d, r.es_correcta
                FROM preguntas p
                JOIN respuestas r ON r.id_pregunta = p.id_pregunta
                WHERE p.id_categoria = $id_cat
                  AND p.id_pregunta NOT IN ($excluir)
                ORDER BY RAND() LIMIT 1";
        $q = $this->conexion->query($sql);

        // fallback: cualquier pregunta no usada
        if (!$q || count($q) === 0) {
            $q = $this->conexion->query("SELECT p.id_pregunta, p.pregunta, r.a, r.b, r.c, r.d, r.es_correcta
                FROM preguntas p
                JOIN respuestas r ON r.id_pregunta = p.id_pregunta
                WHERE p.id_pregunta NOT IN ($excluir)
                ORDER BY RAND() LIMIT 1");
            if (!$q || count($q) === 0) {
                return ['error' => 'No hay preguntas disponibles'];
            }
        }

        $preg = $q[0];

        // registrar pregunta mostrada para esta partida y usuario (evita que el cliente "falsifique")
        $this->conexion->execute("INSERT INTO juego_preguntas (id_juego, id_pregunta, id_usuario, creado_en) VALUES ($id_juego, " . intval($preg['id_pregunta']) . ", $id_usuario, NOW())");

        return [
            'categoria' => ['id' => $id_cat, 'nombre' => $cat['nombre']],
            'pregunta' => [
                'id_pregunta' => (int)$preg['id_pregunta'],
                'pregunta' => $preg['pregunta'],
                'opciones' => ['A' => $preg['a'], 'B' => $preg['b'], 'C' => $preg['c'], 'D' => $preg['d']]
            ]
        ];
    }

    /**
     * Procesar respuesta enviada por el cliente.
     * Valida que la pregunta fue provista por el servidor (existe en juego_preguntas
     * para esta partida y usuario) y que no fue respondida ya.
     */
    public function procesarRespuesta(int $id_juego, int $id_usuario, int $id_pregunta, string $opcion)
    {
        $id_juego = intval($id_juego);
        $id_usuario = intval($id_usuario);
        $id_pregunta = intval($id_pregunta);
        $opcion = strtoupper(substr($opcion, 0, 1));

        // buscar registro en juego_preguntas (último inserto para esta pregunta)
        $rows = $this->conexion->query("SELECT * FROM juego_preguntas WHERE id_juego = $id_juego AND id_pregunta = $id_pregunta AND id_usuario = $id_usuario ORDER BY id_juego_pregunta DESC LIMIT 1");
        if (!$rows || count($rows) === 0) {
            return ['error' => 'Pregunta no autorizada para esta partida'];
        }
        $jp = $rows[0];

        // evitar doble respuesta (si ya tiene opcion_elegida o es_correcta no es nulo)
        $alreadyAnswered = false;
        if (array_key_exists('opcion_elegida', $jp) && $jp['opcion_elegida'] !== null && $jp['opcion_elegida'] !== '') $alreadyAnswered = true;
        if (array_key_exists('es_correcta', $jp) && $jp['es_correcta'] !== null && $jp['es_correcta'] !== '0') $alreadyAnswered = $alreadyAnswered || ($jp['es_correcta'] !== null && $jp['es_correcta'] !== '');

        if ($alreadyAnswered) return ['error' => 'La pregunta ya fue respondida'];

        // obtener letra correcta del registro respuestas
        $r = $this->conexion->query("SELECT es_correcta FROM respuestas WHERE id_pregunta = $id_pregunta LIMIT 1");
        if (!$r || !isset($r[0]['es_correcta'])) return ['error' => 'No se encontró la respuesta correcta'];
        $correct = strtoupper($r[0]['es_correcta']);
        $isCorrect = ($opcion === $correct) ? 1 : 0;

        // actualizar juego_preguntas (si existe columna opcion_elegida la usamos; si no, solo es_correcta)
        $checkOpc = $this->conexion->query("SHOW COLUMNS FROM juego_preguntas LIKE 'opcion_elegida'");
        if ($checkOpc && count($checkOpc)) {
            $this->conexion->execute("UPDATE juego_preguntas SET opcion_elegida = '" . addslashes($opcion) . "', es_correcta = $isCorrect WHERE id_juego = $id_juego AND id_pregunta = $id_pregunta AND id_usuario = $id_usuario");
        } else {
            // solo es_correcta
            $this->conexion->execute("UPDATE juego_preguntas SET es_correcta = $isCorrect WHERE id_juego = $id_juego AND id_pregunta = $id_pregunta AND id_usuario = $id_usuario");
        }

        // actualizar puntaje en tabla juegos (simple)
        if ($isCorrect) {
            $this->conexion->execute("UPDATE juegos SET puntaje = COALESCE(puntaje,0) + 1 WHERE id_juego = $id_juego");
        }

        $puntaje = $this->obtenerPuntajeJuego($id_juego);

        return [
            'correct' => (bool)$isCorrect,
            'correcta' => $correct,
            'puntaje' => (int)$puntaje
        ];
    }
    // FIN: métodos nuevos

    // Stubs mínimos — reemplazá por tu lógica real luego
    public function obtenerJuegoActivo($id_usuario)
    {
        $id = intval($id_usuario);
        $res = $this->conexion->query("SELECT * FROM juegos WHERE id_usuario = $id AND estado = 'activo' ORDER BY iniciado_en DESC LIMIT 1");
        return (isset($res[0]) && is_array($res[0])) ? $res[0] : null;
    }

    public function obtenerJuego($id_juego)
    {
        return null;
    }

    // Devuelve el puntaje actual de la partida (si existe columna puntaje en juegos)
    public function obtenerPuntajeJuego($id_juego)
    {
        $id = intval($id_juego);
        $res = $this->conexion->query("SELECT puntaje FROM juegos WHERE id_juego = $id LIMIT 1");
        if ($res && isset($res[0]['puntaje'])) {
            return (int)$res[0]['puntaje'];
        }

        // Fallback: calcular a partir de juego_preguntas (suma de es_correcta)
        $rows = $this->conexion->query("SELECT COALESCE(SUM(es_correcta),0) AS aciertos FROM juego_preguntas WHERE id_juego = $id");
        if ($rows && isset($rows[0]['aciertos'])) {
            return (int)$rows[0]['aciertos'];
        }

        return 0;
    }

    // Aumenta el puntaje de la partida (por ejemplo al responder correctamente)
    public function actualizarPuntaje($incremento, $id_juego)
    {
        $inc = intval($incremento);
        $id = intval($id_juego);
        $this->conexion->execute("UPDATE juegos SET puntaje = COALESCE(puntaje,0) + $inc WHERE id_juego = $id");
    }

    // Marca la partida como finalizada y guarda el puntaje final
    public function guardarPartida($puntajeFinal, $id_juego)
    {
        $p = intval($puntajeFinal);
        $id = intval($id_juego);
        $this->conexion->execute("UPDATE juegos SET puntaje = $p, estado = 'finalizado', finalizado_en = NOW() WHERE id_juego = $id");
    }
}
?>
