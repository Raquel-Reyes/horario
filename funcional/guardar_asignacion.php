<?php
include("conexion/conexion.php");

if (!isset($_POST['asignaciones'])) {
  die("No se recibieron datos.");
}

$asignaciones = json_decode($_POST['asignaciones'], true);
if (!$asignaciones) {
  die("Datos inválidos.");
}

// Calcular hora fin
function calcularHoraFin($horaInicio, $periodos)
{
  $minutos = $periodos * 40;
  $nuevaHora = strtotime("+$minutos minutes", strtotime($horaInicio));
  return date("H:i:s", $nuevaHora);
}

// Días de la semana (para repartir clases)
$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

foreach ($asignaciones as $a) {
  $docente_id = !empty($a['profesorId']) ? intval($a['profesorId']) : 'NULL';
  $curso_id = intval($a['cursoId']);
  $carrera_ids = $a['carreraIds'];
  $seccion_id = intval($a['seccionId']);
  $cantidad = intval($a['cantidad']);
  $jornada_texto = strtolower(trim($a['jornada']));

  // 🔹 Mapeo entre texto y jornada_id (según tu tabla "jornadas")
  switch ($jornada_texto) {
    case 'matutina':
      $jornada_id = 1;
      break;
    case 'vespertina':
      $jornada_id = 2;
      break;
    case 'doble':
    case 'doble jornada':
      $jornada_id = 3;
      break;
    case 'fin de semana':
      $jornada_id = 4;
      break;
    default:
      $jornada_id = 1;
  }

  // Determinar en cuántos días se repartirá el curso (máximo 5 días)
  $dias_a_usar = min($cantidad, count($dias));
  $periodos_por_dia = ceil($cantidad / $dias_a_usar);

  foreach ($carrera_ids as $carrera_id) {

    for ($i = 0; $i < $dias_a_usar; $i++) {
      $dia = $dias[$i];

      // --- MATUTINA ---
      if ($jornada_id === 1) {
        $hora_inicio = "07:00:00";
        $hora_fin = calcularHoraFin($hora_inicio, $periodos_por_dia);

        $sql = "INSERT INTO asignaciones 
                (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin, jornada_id)
                VALUES ($docente_id, $curso_id, $carrera_id, $seccion_id, '$dia', '$hora_inicio', '$hora_fin', $jornada_id)";
        $conn->query($sql);
      }

      // --- VESPERTINA ---
      elseif ($jornada_id === 2) {
        $hora_inicio = "13:00:00";
        $hora_fin = calcularHoraFin($hora_inicio, $periodos_por_dia);

        $sql = "INSERT INTO asignaciones 
                (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin, jornada_id)
                VALUES ($docente_id, $curso_id, $carrera_id, $seccion_id, '$dia', '$hora_inicio', '$hora_fin', $jornada_id)";
        $conn->query($sql);
      }

      // --- DOBLE JORNADA ---
      elseif ($jornada_id === 3) {
        // Mitad en la mañana
        $hora_inicio_m = "07:00:00";
        $hora_fin_m = calcularHoraFin($hora_inicio_m, ceil($periodos_por_dia / 2));

        // Mitad en la tarde
        $hora_inicio_v = "13:00:00";
        $hora_fin_v = calcularHoraFin($hora_inicio_v, floor($periodos_por_dia / 2));

        // Insertar ambas sesiones
        $sql1 = "INSERT INTO asignaciones 
                (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin, jornada_id)
                VALUES ($docente_id, $curso_id, $carrera_id, $seccion_id, '$dia', '$hora_inicio_m', '$hora_fin_m', 1)";
        $conn->query($sql1);

        $sql2 = "INSERT INTO asignaciones 
                (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin, jornada_id)
                VALUES ($docente_id, $curso_id, $carrera_id, $seccion_id, '$dia', '$hora_inicio_v', '$hora_fin_v', 2)";
        $conn->query($sql2);
      }

      // --- FIN DE SEMANA ---
      elseif ($jornada_id === 4) {
        $hora_inicio = "08:00:00";
        $hora_fin = calcularHoraFin($hora_inicio, $periodos_por_dia);
        $dia = ($i % 2 == 0) ? "Sábado" : "Domingo"; // alternar sábado/domingo

        $sql = "INSERT INTO asignaciones 
                (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin, jornada_id)
                VALUES ($docente_id, $curso_id, $carrera_id, $seccion_id, '$dia', '$hora_inicio', '$hora_fin', $jornada_id)";
        $conn->query($sql);
      }
    }
  }
}

echo "✅ Asignaciones guardadas correctamente y distribuidas por jornada y días.";
?>
