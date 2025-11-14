$(document).ready(function() {
  // Inicializar selects con select2
  $('#docente, #curso, #carrera, #seccion, #jornada').select2({
    width: '100%',
    placeholder: 'Seleccione una opción'
  });

  cargarSelects();
  cargarTabla();

  // Registrar asignación
  $('#asignacionForm').on('submit', function(e) {
    e.preventDefault();
    $.post('guardar_asignacion.php', $(this).serialize(), function(resp) {
      alert(resp);
      $('#asignacionForm')[0].reset();
      $('#docente, #curso, #carrera, #seccion, #jornada').val(null).trigger('change');
      cargarTabla();
    });
  });

  // Eliminar asignación
  $(document).on('click', '.eliminar', function() {
    const id = $(this).data('id');
    if (confirm('¿Eliminar esta asignación?')) {
      $.post('eliminar_asignacion.php', { id }, function(resp) {
        alert(resp);
        cargarTabla();
      });
    }
  });

  function cargarSelects() {
    $('#docente').load('obtener_docentes.php');
    $('#curso').load('obtener_cursos.php');
    $('#carrera').load('obtener_carreras.php');
    $('#seccion').load('obtener_secciones.php');
    $('#jornada').load('obtener_jornadas.php');
  }

  function cargarTabla() {
    $.get('obtener_asignaciones.php', function(data) {
      $('#tablaAsignaciones tbody').html(data);
    });
  }
});
