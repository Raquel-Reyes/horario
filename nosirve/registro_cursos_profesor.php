<?php include("header.php"); ?>
<?php include __DIR__ . '/conexion/conexion.php';?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Asignación de Cursos a Docentes</title>

  <link rel="stylesheet" href="registro_cursos_profesores.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="registro_cursos_profesores.js"></script>
</head>

<body>
  <div class="container">
    <div class="card">
      <header class="app-title">
        <h1>Asignación de Cursos a Docentes</h1>
      </header>

      <form id="asignacionForm" class="grid">
        <div class="field">
          <label for="docente">Docente</label>
          <select id="docente" name="docente" required></select>
        </div>

        <div class="field">
          <label for="curso">Curso</label>
          <select id="curso" name="curso" required></select>
        </div>

        <div class="field">
          <label for="carrera">Carrera</label>
          <select id="carrera" name="carrera" required></select>
        </div>

        <div class="field">
          <label for="seccion">Sección</label>
          <select id="seccion" name="seccion" required></select>
        </div>

        <div class="field">
          <label for="dia_semana">Día</label>
          <select id="dia_semana" name="dia_semana" required>
            <option value="Lunes">Lunes</option>
            <option value="Martes">Martes</option>
            <option value="Miércoles">Miércoles</option>
            <option value="Jueves">Jueves</option>
            <option value="Viernes">Viernes</option>
            <option value="Sábado">Sábado</option>
            <option value="Domingo">Domingo</option>
          </select>
        </div>

        <div class="field">
          <label for="hora_inicio">Hora inicio</label>
          <input type="time" id="hora_inicio" name="hora_inicio" required>
        </div>

        <div class="field">
          <label for="hora_fin">Hora fin</label>
          <input type="time" id="hora_fin" name="hora_fin" required>
        </div>

        <div class="field">
          <label for="jornada">Jornada</label>
          <select id="jornada" name="jornada" required></select>
        </div>

        <button type="submit" class="btn">Registrar Asignación</button>
      </form>

      <div class="table-wrap">
        <table id="tablaAsignaciones">
          <thead>
            <tr>
              <th>Docente</th>
              <th>Curso</th>
              <th>Carrera</th>
              <th>Sección</th>
              <th>Día</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Jornada</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
