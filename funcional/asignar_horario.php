<?php
include("conexion/conexion.php");
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Registro de Cursos y Profesores</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
  <div class="container">
    <div class="card">
      <header class="app-title">
        <div class="icon">📚</div>
        <div>
          <h1>Registro de Cursos y Profesores</h1>
          <p class="lead">Asigna cursos por carrera, grado y genera horarios automáticos.</p>
        </div>
      </header>

      <!-- formulario -->
      <form class="grid" method="post" action="guardar_asignacion.php">
        <div class="field">
          <label for="profesor">Profesor</label>
          <div style="display:flex; gap:8px;">
            <select id="profesor" name="docente_id" required>
              <?php
                $res = $conn->query("SELECT * FROM docentes");
                while($row = $res->fetch_assoc()){
                  echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
                }
              ?>
            </select>
            <button class="btn secondary" type="button" onclick="alert('Agregar nuevo docente')">+ Nuevo</button>
          </div>
        </div>

        <div class="field">
          <label for="curso">Curso</label>
          <div style="display:flex; gap:8px;">
            <select id="curso" name="curso_id" required>
              <?php
                $res = $conn->query("SELECT * FROM cursos");
                while($row = $res->fetch_assoc()){
                  echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
                }
              ?>
            </select>
            <button class="btn secondary" type="button" onclick="alert('Agregar nuevo curso')">+ Nuevo</button>
          </div>
        </div>

        <div class="field">
          <label for="carrera">Carrera</label>
          <select id="carrera" name="carrera_ids[]" multiple required>
            <?php
              $res = $conn->query("SELECT * FROM carreras");
              while($row = $res->fetch_assoc()){
                echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
              }
            ?>
          </select>
        </div>

        <div class="field">
          <label for="seccion">Sección</label>
          <select id="seccion" name="seccion_id" required>
            <?php
              $res = $conn->query("SELECT * FROM secciones");
              while($row = $res->fetch_assoc()){
                echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
              }
            ?>
          </select>
        </div>

        <div class="field">
          <label for="dia">Día</label>
          <select id="dia" name="dia_semana" required>
            <option value="Lunes">Lunes</option>
            <option value="Martes">Martes</option>
            <option value="Miércoles">Miércoles</option>
            <option value="Jueves">Jueves</option>
            <option value="Viernes">Viernes</option>
            <option value="Sábado">Sábado</option>
          </select>
        </div>

        <div class="field small-row">
          <div style="flex:1">
            <label for="hora_inicio">Hora Inicio</label>
            <input id="hora_inicio" type="time" name="hora_inicio" required />
          </div>
          <div style="flex:1">
            <label for="hora_fin">Hora Fin</label>
            <input id="hora_fin" type="time" name="hora_fin" required />
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn">Guardar Asignación</button>
        </div>
      </form>

      <!-- tabla de asignaciones actuales -->
      <div class="assignments">
        <h3>📋 Asignaciones actuales</h3>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Profesor</th>
                <th>Curso</th>
                <th>Carrera</th>
                <th>Sección</th>
                <th>Día</th>
                <th>Hora</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $res = $conn->query("
                  SELECT a.id, d.nombre as docente, c.nombre as curso, ca.nombre as carrera, s.nombre as seccion, a.dia_semana, a.hora_inicio, a.hora_fin
                  FROM asignaciones a
                  JOIN docentes d ON d.id = a.docente_id
                  JOIN cursos c ON c.id = a.curso_id
                  JOIN carreras ca ON ca.id = a.carrera_id
                  JOIN secciones s ON s.id = a.seccion_id
                  ORDER BY a.dia_semana, a.hora_inicio
                ");
                while($row = $res->fetch_assoc()){
                  echo "<tr>
                          <td>{$row['docente']}</td>
                          <td>{$row['curso']}</td>
                          <td>{$row['carrera']}</td>
                          <td>{$row['seccion']}</td>
                          <td>{$row['dia_semana']}</td>
                          <td>{$row['hora_inicio']} - {$row['hora_fin']}</td>
                        </tr>";
                }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
