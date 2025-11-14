<?php
include("conexion/conexion.php");

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $docente_id = $_POST['docente_id'];
    $curso_id = $_POST['curso_id'];
    $carrera_ids = $_POST['carrera_ids'] ?? [];
    if(!is_array($carrera_ids)) $carrera_ids = [$carrera_ids];

    $seccion_id = $_POST['seccion_id'];
    $jornada = $_POST['jornada'];
    $cantidad = intval($_POST['cantidad']);

    $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
    $hora_inicio_base = new DateTime('07:00');
    $duracion = 40; // minutos por período

    foreach($carrera_ids as $carrera_id){

        $hora_inicio = clone $hora_inicio_base; // reset hora inicio por carrera

        for($i = 0; $i < $cantidad; $i++){
            $hora_fin = clone $hora_inicio;
            $hora_fin->modify("+$duracion minutes");

            $dia_index = $i % count($dias);
            $dia = $dias[$dia_index];

            $start_time = $hora_inicio->format('H:i:s');
            $end_time = $hora_fin->format('H:i:s');

            // 🔹 Buscar siguiente horario libre
            $conflict = true;
            while($conflict){
                // Verificar sección
                $sql_sec = "SELECT COUNT(*) as total FROM asignaciones 
                            WHERE seccion_id=? AND dia_semana=? 
                            AND ((hora_inicio <= ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin >= ?))";
                $stmt_sec = $conn->prepare($sql_sec);
                $stmt_sec->bind_param("isssss", $seccion_id, $dia, $start_time, $start_time, $end_time, $end_time);
                $stmt_sec->execute();
                $res_sec = $stmt_sec->get_result()->fetch_assoc();

                // Verificar docente
                $sql_doc = "SELECT COUNT(*) as total FROM asignaciones 
                            WHERE docente_id=? AND dia_semana=? 
                            AND ((hora_inicio <= ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin >= ?))";
                $stmt_doc = $conn->prepare($sql_doc);
                $stmt_doc->bind_param("isssss", $docente_id, $dia, $start_time, $start_time, $end_time, $end_time);
                $stmt_doc->execute();
                $res_doc = $stmt_doc->get_result()->fetch_assoc();

                if($res_sec['total'] == 0 && $res_doc['total'] == 0){
                    $conflict = false; // horario libre
                } else {
                    // avanzar 40 minutos y recalcular hora_fin
                    $hora_inicio->modify("+$duracion minutes");
                    $hora_fin = clone $hora_inicio;
                    $hora_fin->modify("+$duracion minutes");
                    $start_time = $hora_inicio->format('H:i:s');
                    $end_time = $hora_fin->format('H:i:s');
                }
            }

            // 🔹 Insertar asignación
            $sql_ins = "INSERT INTO asignaciones (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin, jornada)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->bind_param(
                "iiiissss",
                $docente_id,
                $curso_id,
                $carrera_id,
                $seccion_id,
                $dia,
                $start_time,
                $end_time,
                $jornada
            );
            if(!$stmt_ins->execute()){
                echo "Error al guardar la asignación: " . $stmt_ins->error;
                exit;
            }

            // Avanzar hora inicio para el próximo período
            $hora_inicio->modify("+$duracion minutes");
        }
    }

    echo "<p style='color:green;'>Asignaciones generadas automáticamente sin conflictos.</p>";
    echo "<a href='registro.php'>Volver al formulario</a>";

} else {
    echo "Método no permitido.";
}
?>
