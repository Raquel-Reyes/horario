<?php
// registro_cursos_profesores.php
// Incluye conexión y header si lo tienes
include __DIR__ . '/conexion/conexion.php';
include_once("header.php");

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    header('Content-Type: application/json; charset=utf-8');

    if ($action === 'save_assignment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        // Esperamos: docente_id, curso_id, carrera_ids[] (array), seccion_id, cantidad, jornada, grado (opcional)
        $docente = intval($data['docente_id'] ?? 0);
        $curso = intval($data['curso_id'] ?? 0);
        $seccion = intval($data['seccion_id'] ?? 0);
        $cantidad = intval($data['cantidad'] ?? 0);
        $jornada = $conn->real_escape_string($data['jornada'] ?? '');
        $grado = intval($data['grado'] ?? 0);
        $carreras = $data['carrera_ids'] ?? [];

        if (!$docente || !$curso || !$seccion || !$cantidad || empty($carreras)) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO asignaciones (docente_id, curso_id, carrera_id, seccion_id, grado, cantidad, dia_semana, hora_inicio, hora_fin, jornada) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?)");
        if (!$stmt) { echo json_encode(['error'=>$conn->error]); exit; }

        foreach ($carreras as $car) {
            $car_id = intval($car);
            $stmt->bind_param("iiiiss", $docente, $curso, $car_id, $seccion, $grado, $cantidad, $jornada); // note: binding incorrect count -> fix below
            // The above bind is wrong because types length mismatch: we have 6 placeholders. We'll prepare correct binding:
        }
        // We'll close and re-prepare with correct binding (mysqli requires correct param count)
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO asignaciones (docente_id, curso_id, carrera_id, seccion_id, grado, cantidad, dia_semana, hora_inicio, hora_fin, jornada) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?)");
        if (!$stmt) { echo json_encode(['error'=>$conn->error]); exit; }
        foreach ($carreras as $car) {
            $car_id = intval($car);
            $stmt->bind_param("iiiiis", $docente, $curso, $car_id, $seccion, $grado, $cantidad);
            // NOTE: we have 7 columns to bind but query actually has 7 placeholders: ?,?,?,?,?,?,? (the last is jornada)
            // Actually the query ends with , ?), so param types should be "iiiiiss" (int,int,int,int,int,int,string)
            // Let's fix:
        }
        // Clean up and do it properly:
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO asignaciones (docente_id, curso_id, carrera_id, seccion_id, grado, cantidad, dia_semana, hora_inicio, hora_fin, jornada) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?)");
        // types: i i i i i i s  => "iiiiis"
        if (!$stmt) { echo json_encode(['error'=>$conn->error]); exit; }

        foreach ($carreras as $car) {
            $car_id = intval($car);
            $stmt->bind_param("iiiiis", $docente, $curso, $car_id, $seccion, $grado, $cantidad, $jornada);
            if (!$stmt->execute()) {
                echo json_encode(['error' => $stmt->error]);
                $stmt->close();
                exit;
            }
        }
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'list_asignaciones_docente') {
        $docente = intval($_GET['docente_id'] ?? 0);
        if (!$docente) { echo json_encode([]); exit; }
        // traer asignaciones (tanto programadas como no programadas)
        $sql = "SELECT a.*, c.nombre AS curso_nombre, ca.nombre AS carrera_nombre, s.nombre AS seccion_nombre
                FROM asignaciones a
                LEFT JOIN cursos c ON c.id = a.curso_id
                LEFT JOIN carreras ca ON ca.id = a.carrera_id
                LEFT JOIN secciones s ON s.id = a.seccion_id
                WHERE a.docente_id = ?
                ORDER BY a.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $docente);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit;
    }

    if ($action === 'delete_asignacion') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error'=>'Id faltante']); exit; }
        $stmt = $conn->prepare("DELETE FROM asignaciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['error'=>$stmt->error]);
        exit;
    }

    if ($action === 'generate_schedule') {
        // Algoritmo simple greedy que reserva franjas para las asignaciones **NO programadas** (dia_semana IS NULL)
        // Parámetros opcionales: docente_id (si quieres generar solo para un docente)
        $docente_filter = intval($_GET['docente_id'] ?? 0);

        // Configuración de franjas
        $interval_seconds = 40 * 60; // 40 min
        $start_time = strtotime("07:00");
        $end_time = strtotime("17:00"); // exclusor
        $days = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

        // 1) Obtener todas las asignaciones pendientes (cantidad > 0 y dia_semana IS NULL)
        $sql = "SELECT * FROM asignaciones WHERE dia_semana IS NULL";
        $params = [];
        if ($docente_filter) {
            $sql .= " AND docente_id = ?";
            $params[] = $docente_filter;
        }
        $stmt = $conn->prepare($sql);
        if ($docente_filter) $stmt->bind_param("i", $docente_filter);
        $stmt->execute();
        $res = $stmt->get_result();
        $pendientes = [];
        while ($r = $res->fetch_assoc()) $pendientes[] = $r;

        // 2) Cargar ocupaciones existentes (tanto programadas como ya generadas)
        // mapa: ocupaciones[day][time] => ['docente'=>true, 'seccion'=>true]
        $ocupaciones = [];
        $stmt2 = $conn->prepare("SELECT dia_semana, hora_inicio, hora_fin, docente_id, seccion_id FROM asignaciones WHERE dia_semana IS NOT NULL");
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($r = $res2->fetch_assoc()) {
            $d = $r['dia_semana'];
            $hi = strtotime($r['hora_inicio']);
            // convertir a slot index: time string "HH:MM"
            $timekey = date('H:i', $hi);
            if (!isset($ocupaciones[$d])) $ocupaciones[$d] = [];
            if (!isset($ocupaciones[$d][$timekey])) $ocupaciones[$d][$timekey] = ['docentes'=>[], 'secciones'=>[]];
            $ocupaciones[$d][$timekey]['docentes'][$r['docente_id']] = true;
            $ocupaciones[$d][$timekey]['secciones'][$r['seccion_id']] = true;
        }

        // 3) Generar lista de slots ordenada por día y hora
        $slots = [];
        foreach ($days as $day) {
            for ($t = $start_time; $t + $interval_seconds <= $end_time; $t += $interval_seconds) {
                $timekey = date('H:i', $t);
                $slots[] = ['day'=>$day, 'time'=>$timekey, 'start_ts'=>$t, 'end_ts'=>$t+$interval_seconds];
            }
        }

        // 4) Para cada asignación pendiente, intentar asignar 'cantidad' slots sin choque
        $created = [];
        foreach ($pendientes as $asg) {
            $cantidad = intval($asg['cantidad'] ?? 1);
            $docente_id = intval($asg['docente_id']);
            $seccion_id = intval($asg['seccion_id']);
            $curso_id = intval($asg['curso_id']);
            $carrera_id = intval($asg['carrera_id']);
            $grado = intval($asg['grado'] ?? 0);
            $jornada = $asg['jornada'] ?? '';

            $assigned = 0;
            // simple strategy: iterate slots earliest-first, assign if free for docente and section
            foreach ($slots as $slot) {
                if ($assigned >= $cantidad) break;
                $d = $slot['day'];
                $timekey = $slot['time'];

                // existe ocupacion en esa franja?
                $doc_ok = !isset($ocupaciones[$d][$timekey]['docentes'][$docente_id]);
                $sec_ok = !isset($ocupaciones[$d][$timekey]['secciones'][$seccion_id]);

                if ($doc_ok && $sec_ok) {
                    // reservar: insert into DB a fila con dia_semana, hora_inicio, hora_fin
                    $hi = date('H:i:s', $slot['start_ts']);
                    $hf = date('H:i:s', $slot['end_ts']);
                    $ins = $conn->prepare("INSERT INTO asignaciones (docente_id, curso_id, carrera_id, seccion_id, grado, cantidad, dia_semana, hora_inicio, hora_fin, jornada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$ins) {
                        echo json_encode(['error' => $conn->error]);
                        exit;
                    }
                    $ins->bind_param("iiiiisssss", $docente_id, $curso_id, $carrera_id, $seccion_id, $grado, $cantidad, $d, $hi, $hf, $jornada);
                    // Note: bind types must match: i i i i i i s s s s -> "iiiiissss"? There are 10 params: types "iiiiisssss" (6 i + 4 s) but we used 6 ints and 4 strings.
                    // For safety build appropriate types:
                    $ins->close();
                    // Prepare correctly:
                    $ins = $conn->prepare("INSERT INTO asignaciones (docente_id, curso_id, carrera_id, seccion_id, grado, cantidad, dia_semana, hora_inicio, hora_fin, jornada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $types = "iiiiisssss"; // 6 ints (docente,curso,carrera,seccion,grado,cantidad) and 4 strings (dia,hora_i,hora_f,jornada)
                    if (!$ins) { echo json_encode(['error'=>$conn->error]); exit; }
                    $ins->bind_param($types, $docente_id, $curso_id, $carrera_id, $seccion_id, $grado, $cantidad, $d, $hi, $hf, $jornada);
                    if (!$ins->execute()) {
                        echo json_encode(['error' => $ins->error]);
                        $ins->close();
                        exit;
                    }
                    $ins->close();

                    // marcar ocupacion en memoria para evitar choques posteriores
                    if (!isset($ocupaciones[$d])) $ocupaciones[$d] = [];
                    if (!isset($ocupaciones[$d][$timekey])) $ocupaciones[$d][$timekey] = ['docentes'=>[], 'secciones'=>[]];
                    $ocupaciones[$d][$timekey]['docentes'][$docente_id] = true;
                    $ocupaciones[$d][$timekey]['secciones'][$seccion_id] = true;

                    $created[] = ['docente'=>$docente_id,'curso'=>$curso_id,'dia'=>$d,'hora'=>$hi];
                    $assigned++;
                }
            }

            // Luego de asignar los 'cantidad' slots intentados, eliminamos la asignación "pendiente" original (la que tenía dia_semana NULL),
            // porque ya generamos entradas individuales con día/hora. Para evitar borrar otras ya programadas, borramos por id si existe.
            $orig_id = intval($asg['id']);
            if ($orig_id) {
                $del = $conn->prepare("DELETE FROM asignaciones WHERE id = ?");
                $del->bind_param("i", $orig_id);
                $del->execute();
                $del->close();
            }
        }

        echo json_encode(['success'=>true,'created'=>$created]);
        exit;
    }

    // acción desconocida
    echo json_encode(['error'=>'Acción desconocida']);
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Registro de Cursos y Profesores</title>

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <style>
    /* estilos (mismo look & feel que te gustó) */
    :root{--bg:#f5f7fb;--card:#fff;--muted:#6b7280;--accent:#0b73ff;--accent-2:#0b5bcc;--danger:#e11d48;--border:#e6e9ef;--shadow:0 6px 18px rgba(20,25,40,0.06);font-family:Inter, system-ui, Arial;}
    html,body{height:100%;margin:0;background:linear-gradient(180deg,var(--bg),#eef3fb 100%);padding:30px;color:#0f172a;}
    .container{max-width:980px;margin:0 auto;}
    .card{background:var(--card);border-radius:12px;box-shadow:var(--shadow);border:1px solid var(--border);padding:22px;}
    header.app-title{display:flex;align-items:center;gap:14px;margin-bottom:18px;}
    header.app-title h1{font-size:20px;margin:0;}
    p.lead{margin:6px 0 18px;color:var(--muted);font-size:14px;}
    form.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 18px;align-items:end;}
    .field{display:flex;flex-direction:column;gap:6px;}
    label{font-size:13px;color:var(--muted);}
    select,input[type="number"],input[type="text"]{height:40px;padding:8px 12px;border-radius:8px;border:1px solid var(--border);background:#fff;font-size:14px;}
    .btn{height:40px;padding:0 14px;border-radius:8px;border:0;cursor:pointer;font-weight:600;background:var(--accent);color:white;box-shadow:0 6px 12px rgba(11,115,255,0.12);}
    .btn.secondary{background:transparent;color:var(--accent-2);border:1px solid rgba(11,88,204,0.12);}
    .btn.ghost{background:transparent;color:var(--muted);border:1px dashed #e6e9ef;}
    .table-wrap{overflow:auto;margin-top:8px;border-radius:8px;border:1px solid var(--border);}
    table{width:100%;border-collapse:collapse;font-size:14px;min-width:680px;background:#fff;}
    thead th{text-align:left;padding:12px 14px;border-bottom:1px solid var(--border);color:var(--muted);font-size:13px;}
    tbody td{padding:12px 14px;border-bottom:1px solid #f1f4f8;}
    .opt-btn{display:inline-flex;gap:8px;align-items:center;}
    .actions{margin-top:16px;display:flex;gap:12px;justify-content:flex-end;}
    @media(max-width:820px){form.grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <header class="app-title">
        <div class="icon">📚</div>
        <div>
          <h1>Registro de Cursos y Profesores</h1>
          <p class="lead">Asigna cursos por carrera / grado y genera horarios automáticos.</p>
        </div>
      </header>

      <!-- formulario -->
      <form class="grid" id="formMain" onsubmit="return false;">
        <div class="field">
          <label for="profesor">Profesor</label>
          <select id="profesor" name="docente_id" required>
            <option value="">Seleccione un profesor</option>
            <?php
            $r = $conn->query("SELECT id,nombre FROM docentes ORDER BY nombre");
            while($row = $r->fetch_assoc()){
              echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
            }
            ?>
          </select>
        </div>

        <div class="field">
          <label for="curso">Curso</label>
          <select id="curso" name="curso_id" required onchange="cargarCarreras()">
            <option value="">Seleccione un curso</option>
            <?php
            $r = $conn->query("SELECT id,nombre FROM cursos ORDER BY nombre");
            while($row = $r->fetch_assoc()){
              echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
            }
            ?>
          </select>
        </div>

        <div class="field">
          <label for="carrera">Carreras</label>
          <select id="carrera" name="carrera_ids[]" multiple style="width:100%">
            <!-- cargado dinámicamente -->
          </select>
        </div>

        <div class="field">
          <label for="grado">Grado</label>
          <select id="grado" name="grado">
            <option value="1">1</option><option value="2">2</option><option value="3">3</option>
          </select>
        </div>

        <div class="field">
          <label for="seccion">Sección</label>
          <select id="seccion" name="seccion_id">
            <?php
            $r = $conn->query("SELECT s.id, s.nombre, j.nombre AS jornada FROM secciones s JOIN jornadas j ON j.id=s.jornada_id ORDER BY s.nombre");
            while($row=$r->fetch_assoc()){
              echo "<option value='{$row['id']}'>{$row['nombre']} - {$row['jornada']}</option>";
            }
            ?>
          </select>
        </div>

        <div class="field">
          <label for="cantidad">Cantidad de períodos (40 min c/u)</label>
          <input id="cantidad" name="cantidad" type="number" min="1" max="12" value="6">
        </div>

        <div class="field">
          <label for="jornada">Jornada</label>
          <select id="jornada" name="jornada">
            <?php
            $r = $conn->query("SELECT nombre FROM jornadas ORDER BY nombre");
            while($row=$r->fetch_assoc()){
              echo "<option value='{$row['nombre']}'>{$row['nombre']}</option>";
            }
            ?>
          </select>
        </div>

        <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px;">
          <button id="addAssign" class="btn ghost" type="button">+ Agregar asignación</button>
          <button id="saveAll" class="btn" type="button">Guardar Todo</button>
        </div>
      </form>

      <!-- tabla asignaciones (preview y actuales) -->
      <div class="assignments">
        <h3>📋 Asignaciones actuales del profesor</h3>
        <div class="table-wrap">
          <table id="assignTable">
            <thead>
              <tr>
                <th>Curso</th><th>Carrera</th><th>Sección</th><th>Grado</th><th>Períodos</th><th>Jornada</th><th>Dia / Hora</th><th>Opciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="actions">
        <button id="genSchedule" class="btn secondary" type="button">Generar Horario Automático</button>
        <button class="btn" id="refreshTable" type="button">Refrescar</button>
      </div>
    </div>
  </div>

<script>
$(function(){
  $('#carrera').select2({placeholder:"Seleccione una o varias carreras"});
});

// cargar carreras (simple endpoint que devuelve todas las carreras)
function cargarCarreras(){
  const curso_id = $('#curso').val();
  // Si quieres filtrar carreras por curso, cambia este endpoint
  $.get('obtener_carreras.php', {curso_id}, function(html){
    $('#carrera').html(html).trigger('change');
  }).fail(()=>alert('Error cargando carreras'));
}

// Estructura JS local para asignaciones pendientes (antes de guardar)
let pending = [];

// añadir fila al preview (no guarda aún)
$('#addAssign').click(function(){
  const docente_id = $('#profesor').val();
  const docente_text = $('#profesor option:selected').text();
  const curso_id = $('#curso').val();
  const curso_text = $('#curso option:selected').text();
  const carreras = $('#carrera').val() || [];
  const carreras_text = $('#carrera option:selected').map(function(){ return $(this).text(); }).get().join(', ');
  const seccion_id = $('#seccion').val();
  const seccion_text = $('#seccion option:selected').text();
  const grado = $('#grado').val();
  const cantidad = $('#cantidad').val();
  const jornada = $('#jornada').val();

  if (!docente_id || !curso_id || carreras.length === 0) { alert('Seleccione profesor, curso y al menos una carrera'); return; }

  // Agregar a pending (crea una fila por combinación carrera)
  carreras.forEach(function(carId){
    const carText = $('#carrera option[value="'+carId+'"]').text();
    const obj = {
      docente_id: docente_id,
      docente_text: docente_text,
      curso_id: curso_id,
      curso_text: curso_text,
      carrera_id: carId,
      carrera_text: carText,
      seccion_id: seccion_id,
      seccion_text: seccion_text,
      grado: grado,
      cantidad: parseInt(cantidad),
      jornada: jornada
    };
    pending.push(obj);
    appendRowPreview(obj);
  });
});

// render fila preview
function appendRowPreview(obj){
  const tbody = $('#assignTable tbody');
  const tr = $('<tr></tr>');
  tr.data('assignment', obj);
  tr.append('<td>'+escapeHtml(obj.curso_text)+'</td>');
  tr.append('<td>'+escapeHtml(obj.carrera_text)+'</td>');
  tr.append('<td>'+escapeHtml(obj.seccion_text)+'</td>');
  tr.append('<td>'+escapeHtml(obj.grado)+'</td>');
  tr.append('<td>'+escapeHtml(obj.cantidad)+'</td>');
  tr.append('<td>'+escapeHtml(obj.jornada)+'</td>');
  tr.append('<td style="color:var(--muted)">Pendiente</td>');
  tr.append('<td><div class="opt-btn"><button class="btn secondary" onclick="editarFila(this)">Editar</button> <button class="btn" style="background:var(--danger)" onclick="eliminarFila(this)">❌</button></div></td>');
  tbody.prepend(tr);
}

function escapeHtml(s){ if(!s) return ''; return s.replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }

window.eliminarFila = function(btn){
  if(!confirm('Eliminar esta fila?')) return;
  const tr = $(btn).closest('tr');
  const obj = tr.data('assignment');
  // quitar del pending (por coincidencia simple)
  pending = pending.filter(p => !(p.docente_id==obj.docente_id && p.curso_id==obj.curso_id && p.carrera_id==obj.carrera_id && p.seccion_id==obj.seccion_id && p.cantidad==obj.cantidad));
  tr.remove();
};

window.editarFila = function(btn){
  const tr = $(btn).closest('tr');
  const obj = tr.data('assignment');
  const nuevo = prompt('Editar número de períodos (40 min):', obj.cantidad);
  if (nuevo === null) return;
  const n = parseInt(nuevo);
  if (isNaN(n) || n<1) return alert('Valor inválido');
  // actualizar pending
  for (let i=0;i<pending.length;i++){
    let p = pending[i];
    if (p.docente_id==obj.docente_id && p.curso_id==obj.curso_id && p.carrera_id==obj.carrera_id && p.seccion_id==obj.seccion_id) {
      p.cantidad = n;
    }
  }
  tr.find('td').eq(4).text(n);
};

// GUARDAR TODO: envía pending al endpoint save_assignment
$('#saveAll').click(function(){
  if (pending.length === 0) { alert('No hay asignaciones pendientes'); return; }
  // Agrupar por docente/curso/carrera/seccion para enviar en lote
  // el endpoint espera: docente_id, curso_id, carrera_ids[], seccion_id, cantidad, jornada, grado
  // Para simplificar haremos varios POST: uno por elemento pending
  const toSave = pending.slice(); // copia
  let errors = [];
  let saved = 0;
  (function saveNext(){
    if (toSave.length===0) {
      alert('Guardado completado: ' + saved + ' asignaciones.');
      pending = [];
      loadAsignacionesDocente();
      return;
    }
    const item = toSave.shift();
    $.ajax({
      url: location.pathname + '?action=save_assignment',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        docente_id: item.docente_id,
        curso_id: item.curso_id,
        carrera_ids: [item.carrera_id],
        seccion_id: item.seccion_id,
        cantidad: item.cantidad,
        jornada: item.jornada,
        grado: item.grado
      }),
      success: function(resp){
        if (resp && resp.success) saved++;
        else errors.push(resp.error || 'Error');
        saveNext();
      },
      error: function(xhr){
        errors.push(xhr.responseText || 'Error AJAX');
        saveNext();
      }
    });
  })();
});

// Cargar asignaciones actuales del docente seleccionado
$('#profesor').change(loadAsignacionesDocente);
$('#refreshTable').click(loadAsignacionesDocente);

function loadAsignacionesDocente(){
  const docente = $('#profesor').val();
  if (!docente) { $('#assignTable tbody').html(''); return; }
  $.getJSON(location.pathname + '?action=list_asignaciones_docente&docente_id=' + docente, function(rows){
    const tbody = $('#assignTable tbody');
    // primero mantenemos filas pendientes (las que estén en 'pending') y luego mostramos desde BD
    tbody.html('');
    // mostrar asignaciones programadas/pendientes desde BD
    rows.forEach(function(r){
      const tr = $('<tr></tr>');
      tr.append('<td>'+escapeHtml(r.curso_nombre || '')+'</td>');
      tr.append('<td>'+escapeHtml(r.carrera_nombre || '')+'</td>');
      tr.append('<td>'+escapeHtml(r.seccion_nombre || '')+'</td>');
      tr.append('<td>'+escapeHtml(r.grado || '')+'</td>');
      tr.append('<td>'+escapeHtml(r.cantidad || '')+'</td>');
      tr.append('<td>'+escapeHtml(r.jornada || '')+'</td>');
      let when = (r.dia_semana ? (r.dia_semana + ' ' + (r.hora_inicio||'') + '-' + (r.hora_fin||'')) : '<span style="color:var(--muted)">Pendiente</span>');
      tr.append('<td>'+when+'</td>');
      tr.append('<td><div class="opt-btn"><button class="btn secondary" onclick="editarAsignacion('+r.id+')">Editar</button> <button class="btn" style="background:var(--danger)" onclick="deleteAsignacion('+r.id+')">❌</button></div></td>');
      tbody.append(tr);
    });
    // agregar previews pendientes encima
    pending.forEach(function(p){
      appendRowPreview(p);
    });
  });
}

// eliminar asignacion en BD
window.deleteAsignacion = function(id){
  if (!confirm('Eliminar asignación de la BD?')) return;
  $.getJSON(location.pathname + '?action=delete_asignacion&id=' + id, function(res){
    if (res && res.success) loadAsignacionesDocente(); else alert('Error: '+(res.error||''));
  });
};

// editar asignacion simple (solo jornada o cantidad)
window.editarAsignacion = function(id){
  const nuevo = prompt('Editar jornada o deja vacío para no cambiar (ej: Diario):');
  if (nuevo === null) return;
  // Para simplicidad actualizaremos campo jornada via AJAX por ahora (puedes ampliar)
  $.ajax({
    url: 'editar_asignacion_simple.php',
    method: 'POST',
    data: { id: id, jornada: nuevo },
    success: function(){ loadAsignacionesDocente(); },
    error: function(){ alert('Error al editar'); }
  });
};

// generar horario automático: llama al endpoint generate_schedule (que guarda las entradas horarias en BD)
$('#genSchedule').click(function(){
  if (!confirm('Generar horario automático: el sistema intentará programar las asignaciones pendientes. ¿Continuar?')) return;
  const docente = $('#profesor').val() || 0;
  $.getJSON(location.pathname + '?action=generate_schedule&docente_id=' + docente, function(resp){
    if (resp && resp.success) {
      alert('Generación completada. Entradas creadas: ' + (resp.created?resp.created.length:0));
      loadAsignacionesDocente();
    } else {
      alert('Error: ' + (resp.error || 'Respuesta inesperada'));
    }
  }).fail(function(xhr){ alert('Error al generar: ' + xhr.responseText); });
});

// inicial
loadAsignacionesDocente();
</script>
</body>
</html>
