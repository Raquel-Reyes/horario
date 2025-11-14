<?php
include("db.php");

?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Registro de Cursos y Profesores</title>
  <style>
    :root{
      --bg:#f5f7fb;
      --card:#ffffff;
      --muted:#6b7280;
      --accent:#0b73ff;
      --accent-2:#0b5bcc;
      --danger:#e11d48;
      --border: #e6e9ef;
      --shadow: 0 6px 18px rgba(20,25,40,0.06);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }

    html,body{
      height:100%;
      margin:0;
      background: linear-gradient(180deg, var(--bg), #eef3fb 100%);
      color:#0f172a;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      padding:30px;
    }

    .container{
      max-width:980px;
      margin:0 auto;
    }

    .card{
      background:var(--card);
      border-radius:12px;
      box-shadow:var(--shadow);
      border:1px solid var(--border);
      padding:22px;
    }

    header.app-title{
      display:flex;
      align-items:center;
      gap:14px;
      margin-bottom:18px;
    }
    header.app-title h1{
      font-size:20px;
      margin:0;
    }
    header.app-title .icon{
      font-size:22px;
    }
    p.lead{
      margin:6px 0 18px 0;
      color:var(--muted);
      font-size:14px;
    }

    form.grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:12px 18px;
      align-items:end;
    }

    .field{
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    label{
      font-size:13px;
      color:var(--muted);
    }
    select, input[type="number"], input[type="text"]{
      height:40px;
      padding:8px 12px;
      border-radius:8px;
      border:1px solid var(--border);
      background:#fff;
      font-size:14px;
    }

    .full{
      grid-column: 1 / -1;
    }

    .small-row{
      display:flex;
      gap:8px;
      align-items:center;
    }
    .btn{
      height:40px;
      padding:0 14px;
      border-radius:8px;
      border:0;
      cursor:pointer;
      font-weight:600;
      background:var(--accent);
      color:white;
      box-shadow: 0 6px 12px rgba(11,115,255,0.12);
    }
    .btn.secondary{
      background:transparent;
      color:var(--accent-2);
      border:1px solid rgba(11,88,204,0.12);
      font-weight:600;
    }
    .btn.ghost{
      background:transparent;
      color:var(--muted);
      border:1px dashed #e6e9ef;
      font-weight:600;
    }

    /* assignments area */
    .assignments{
      margin-top:18px;
    }
    .assignments h3{
      margin:0 0 8px 0;
      font-size:15px;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .table-wrap{
      overflow:auto;
      margin-top:8px;
      border-radius:8px;
      border:1px solid var(--border);
    }

    table{
      width:100%;
      border-collapse:collapse;
      font-size:14px;
      min-width:680px;
      background: #fff;
    }
    thead th{
      text-align:left;
      padding:12px 14px;
      border-bottom:1px solid var(--border);
      color:var(--muted);
      font-size:13px;
    }
    tbody td{
      padding:12px 14px;
      border-bottom:1px solid #f1f4f8;
    }

    tbody tr:last-child td { border-bottom: none; }

    .opt-btn{
      display:inline-flex;
      gap:8px;
      align-items:center;
    }
    .chip{
      display:inline-block;
      padding:6px 10px;
      border-radius:999px;
      background:#f1f6ff;
      color:var(--accent-2);
      font-weight:600;
      font-size:13px;
      border:1px solid rgba(11,88,204,0.06);
    }

    /* footer actions */
    .actions{
      margin-top:16px;
      display:flex;
      gap:12px;
      justify-content:flex-end;
    }

    /* responsive */
    @media (max-width:820px){
      form.grid{ grid-template-columns: 1fr; }
      .small-row{ flex-wrap:wrap; }
      .container{ padding:10px; }
    }
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
      <form class="grid" onsubmit="return false;">
        <div class="field">
          <label for="profesor">Profesor</label>
          <div style="display:flex; gap:8px;">
            <input id="profesor" type="text" placeholder="Buscar Profesor..." />
            <button class="btn secondary" type="button" onclick="alert('Implementar búsqueda/alta')">+ Nuevo</button>
          </div>
        </div>

        <div class="field">
          <label for="curso">Curso</label>
          <div style="display:flex; gap:8px;">
            <select id="curso">
              <option value="mat1">Matemática I</option>
              <option value="prog">Programación Básica</option>
              <option value="fis1">Física I</option>
            </select>
            <button class="btn secondary" type="button" onclick="alert('Formulario para agregar nuevo curso')">+ Nuevo</button>
          </div>
        </div>

        <div class="field">
          <label for="carrera">Carrera</label>
          <select id="carrera">
            <option value="ing">Ingeniería</option>
            <option value="arq">Arquitectura</option>
            <option value="adm">Administración</option>
          </select>
        </div>

        <div class="field">
          <label for="grado">Grado</label>
          <select id="grado">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
          </select>
        </div>

        <div class="field">
          <label for="periodo">Periodo(s)</label>
          <select id="periodo">
            <option value="1">Periodo 1</option>
            <option value="2">Periodo 2</option>
            <option value="3">Periodo 3</option>
            <option value="4">Periodo 4</option>
            <option value="5">Periodo 5</option>
            <option value="6">Periodo 6</option>
          </select>
        </div>

        <div class="field small-row">
          <div style="flex:1">
            <label for="cantidad">Cantidad</label>
            <input id="cantidad" type="number" value="6" min="1" max="12" />
          </div>
          <div style="display:flex; align-items:flex-end;">
            <button id="addAssign" class="btn ghost" type="button">+ Agregar asignación</button>
          </div>
        </div>

      </form>

      <!-- assignments -->
      <div class="assignments">
        <h3>📋 Asignaciones actuales del profesor</h3>
        <div class="table-wrap">
          <table id="assignTable" aria-label="Asignaciones actuales">
            <thead>
              <tr>
                <th>Curso</th>
                <th>Carrera</th>
                <th>Grado</th>
                <th>Períodos</th>
                <th>Opciones</th>
              </tr>
            </thead>
            <tbody>
              <!-- filas ejemplo iniciales -->
              <tr>
                <td>Matemática I</td>
                <td>Ingeniería</td>
                <td>1</td>
                <td>6</td>
                <td>
                  <div class="opt-btn">
                    <button class="btn secondary" onclick="editar(this)">Editar</button>
                    <button class="btn" onclick="eliminar(this)" style="background:var(--danger);box-shadow:none">❌</button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>Matemática I</td>
                <td>Arquitectura</td>
                <td>1</td>
                <td>6</td>
                <td>
                  <div class="opt-btn">
                    <button class="btn secondary" onclick="editar(this)">Editar</button>
                    <button class="btn" onclick="eliminar(this)" style="background:var(--danger);box-shadow:none">❌</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="actions">
        <button class="btn secondary" onclick="generarHorario()">Generar Horario Automático</button>
        <button class="btn" onclick="guardarTodo()">Guardar Todo</button>
      </div>

    </div>
  </div>

  <script>
    // Pequeña lógica para añadir filas a la tabla (opcionales)
    const addBtn = document.getElementById('addAssign');
    addBtn.addEventListener('click', () => {
      const cursoText = document.getElementById('curso').selectedOptions[0].textContent;
      const carreraText = document.getElementById('carrera').selectedOptions[0].textContent;
      const gradoText = document.getElementById('grado').value;
      const cantidad = document.getElementById('cantidad').value;
      addRow(cursoText, carreraText, gradoText, cantidad);
    });

    function addRow(curso, carrera, grado, periodos){
      const tbody = document.querySelector('#assignTable tbody');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${curso}</td>
        <td>${carrera}</td>
        <td>${grado}</td>
        <td>${periodos}</td>
        <td>
          <div class="opt-btn">
            <button class="btn secondary" onclick="editar(this)">Editar</button>
            <button class="btn" onclick="eliminar(this)" style="background:var(--danger);box-shadow:none">❌</button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    }

    function editar(btn){
      const tr = btn.closest('tr');
      const cells = tr.querySelectorAll('td');
      // ejemplo simple: mostrar los valores para editar (puedes abrir un modal con un form)
      alert('Editar asignación:\n' +
            'Curso: ' + cells[0].textContent + '\n' +
            'Carrera: ' + cells[1].textContent + '\n' +
            'Grado: ' + cells[2].textContent + '\n' +
            'Períodos: ' + cells[3].textContent);
    }

    function eliminar(btn){
      if(!confirm('Eliminar esta asignación?')) return;
      const tr = btn.closest('tr');
      tr.remove();
    }

    function generarHorario(){
      alert('Aquí implementarías la generación automática del horario (algoritmo que evite choques).');
    }
    function guardarTodo(){
      alert('Guardar en backend: serializar asignaciones y enviarlas al servidor.');
    }
  </script>
</body>
</html>
