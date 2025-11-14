<?php
function validar_conflicto($conn, $docente_id, $dia, $hora_inicio, $hora_fin) {
  $sql = "SELECT * FROM asignaciones 
          WHERE docente_id = ? 
          AND dia_semana = ?
          AND ((hora_inicio <= ? AND hora_fin > ?) 
          OR (hora_inicio < ? AND hora_fin >= ?))";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isssss", $docente_id, $dia, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin);
  $stmt->execute();
  $resultado = $stmt->get_result();
  return $resultado->num_rows > 0;
}
?>
