<?php
include("conexion/conexion.php");

// Filtros
$docente_id = isset($_GET['docente_id']) && $_GET['docente_id'] !== '' ? intval($_GET['docente_id']) : null;
$carrera_id = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? intval($_GET['carrera_id']) : null;

// Configuración del horario
$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$hora_inicio = strtotime("07:00");
$hora_fin = strtotime("18:00");
$intervalo = 40 * 60; // 40 minutos

echo "<table id='tablaHorario'>
        <thead>
          <tr>
            <th>No.</th>
            <th>Horario</th>";

foreach ($dias as $d) echo "<th>$d</th>";

echo "  </tr>
        </thead>
        <tbody>";

$contador = 1;

// Recorremos bloques horarios
for ($h = $hora_inicio; $h < $hora_fin; $h += $intervalo) {
    $hi = date("H:i:s", $h);
    $hf = date("H:i:s", $h + $intervalo);

    echo "<tr>";
    echo "<td>{$contador}</td>";
    echo "<td>$hi a $hf</td>";

    foreach ($dias as $dia) {
        $sql = "SELECT a.*, 
                       c.nombre AS curso_nombre, 
                       d.nombre AS docente_nombre, 
                       ca.nombre AS carrera_nombre
                FROM asignaciones a
                INNER JOIN cursos c ON c.id=a.curso_id
                INNER JOIN docentes d ON d.id=a.docente_id
                INNER JOIN carreras ca ON ca.id=a.carrera_id
                WHERE a.dia_semana='$dia'
                AND a.hora_inicio <= '$hi'
                AND a.hora_fin > '$hi'";

        if ($docente_id) $sql .= " AND a.docente_id=$docente_id";
        if ($carrera_id) $sql .= " AND a.carrera_id=$carrera_id";

        $res_cell = $conn->query($sql);

        if ($res_cell && $res_cell->num_rows > 0) {
            echo "<td>";
            while ($r = $res_cell->fetch_assoc()) {
                $colorIndex = crc32($r['carrera_nombre']) % 5;
                $colores = ['azul', 'verde', 'amarillo', 'rosa', 'celeste'];
                $colorClass = $colores[$colorIndex];

                echo "<div class='celda-curso $colorClass'>
                        <b>{$r['curso_nombre']}</b><br>
                        <small>{$r['carrera_nombre']}</small><br>
                        <small>{$r['docente_nombre']}</small>
                      </div>";
            }
            echo "</td>";
        } else {
            echo "<td class='vacio'>-</td>";
        }
    }

    echo "</tr>";
    $contador++;
}

echo "</tbody></table>";
?>
