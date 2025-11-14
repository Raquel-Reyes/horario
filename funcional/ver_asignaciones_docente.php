<?php
include("conexion/conexion.php");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Horario Académico - Ver Asignaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link rel="stylesheet" href="./static/estilo_horario.css">

</head>
<body>

  <h1><i class="fas fa-clock"></i> Horario</h1>

  <form id="form-filtros" class="filtros">
    <!-- FILTRO DOCENTE -->
    <div>
      <label for="docente">Docente:</label>
      <select id="docente" name="docente_id">
        <option value="">Todos</option>
        <?php
        $res = $conn->query("SELECT id, nombre FROM docentes ORDER BY nombre");
        while ($r = $res->fetch_assoc()) {
          echo "<option value='{$r['id']}'>{$r['nombre']}</option>";
        }
        ?>
      </select>
    </div>

    <!-- FILTRO CARRERA -->
    <div>
      <label for="carrera">Carrera:</label>
      <select id="carrera" name="carrera_id">
        <option value="">Todas</option>
        <?php
        $res = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre");
        while ($r = $res->fetch_assoc()) {
          echo "<option value='{$r['id']}'>{$r['nombre']}</option>";
        }
        ?>
      </select>
    </div>

    <!-- FILTRO SECCIÓN -->
    <div>
      <label for="seccion">Sección:</label>
      <select id="seccion" name="seccion_id">
        <option value="">Todas</option>
        <?php
        $res = $conn->query("SELECT id, nombre FROM secciones ORDER BY nombre");
        while ($r = $res->fetch_assoc()) {
          echo "<option value='{$r['id']}'>{$r['nombre']}</option>";
        }
        ?>
      </select>
    </div>
    <button type="button" id="btn-filtrar" class="btn">Ver Horario</button>
    <button type="button" onclick="window.location.href='registro.php'" id="btn-regresar" class="btn">Regresar</button>
  </form>
  <div id="resultado" class="tabla-horario mensaje">
    Seleccione un docente, una carrera o una sección para visualizar el horario.
  </div>

  <!-- Script principal -->
  <script>
    $(document).ready(function(){
      // Inicializar Select2
      $('#docente, #carrera, #seccion').select2({
        placeholder: "Seleccione una opción",
        allowClear: true
      });

      // Acción de filtrar
      $('#btn-filtrar').on('click', function(){
        var docente_id = $('#docente').val();
        var carrera_id = $('#carrera').val();
        var seccion_id = $('#seccion').val();

        // Mostrar mensaje de carga
        $('#resultado').html('<p class="mensaje">Cargando horario...</p>');

        $.ajax({
          url: 'obtener_asignaciones.php',
          type: 'GET',
          data: { docente_id, carrera_id, seccion_id },
          success: function(data){
            $('#resultado').html(data);
          },
          error: function(){
            $('#resultado').html('<p class="mensaje">Error al obtener los datos.</p>');
          }
        });
      });
    });
  </script>

  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>