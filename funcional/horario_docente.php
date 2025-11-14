<?php
include("conexion/conexion.php");

// Filtros opcionales
$filtro_docente = isset($_GET['docente']) ? $_GET['docente'] : '';
$filtro_carrera = isset($_GET['carrera']) ? $_GET['carrera'] : '';
$filtro_jornada = isset($_GET['jornada']) ? $_GET['jornada'] : '';

// Listas para filtros
$docentes = $conn->query("SELECT id, nombre FROM docentes ORDER BY nombre");
$carreras = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre");
$jornadas = $conn->query("SELECT nombre FROM jornadas ORDER BY nombre");

// Días de la semana
$dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

// Horas base (periodos de 40 min)
$horas = [
    '07:00','07:40','08:20','09:00','09:40','10:20','11:00','11:40'
];

// Consulta de asignaciones
$filtro_sql = "WHERE 1=1";
if($filtro_docente != '') $filtro_sql .= " AND d.id = $filtro_docente";
if($filtro_carrera != '') $filtro_sql .= " AND ca.id = $filtro_carrera";
if($filtro_jornada != '') $filtro_sql .= " AND a.jornada = '$filtro_jornada'";

$sql = "
SELECT a.id, d.nombre AS docente, c.nombre AS curso, ca.nombre AS carrera,
       a.dia_semana, a.hora_inicio, a.hora_fin, a.jornada, s.nombre AS seccion
FROM asignaciones a
JOIN docentes d ON d.id = a.docente_id
JOIN cursos c ON c.id = a.curso_id
JOIN carreras ca ON ca.id = a.carrera_id
JOIN secciones s ON s.id = a.seccion_id
$filtro_sql
ORDER BY a.dia_semana, a.hora_inicio
";

$res = $conn->query($sql);

// Preparar array: día -> hora -> lista de asignaciones
$asignaciones = [];
while($row = $res->fetch_assoc()){
    $hora = substr($row['hora_inicio'],0,5); // solo HH:MM
    $asignaciones[$row['dia_semana']][$hora][] = [
        'docente' => $row['docente'],
        'curso'   => $row['curso'],
        'carrera' => $row['carrera'],
        'seccion' => $row['seccion'],
        'jornada' => $row['jornada']
    ];
}

// Colores por sección
$colores_seccion = [
    'A' => '#AED6F1',
    'B' => '#A3E4D7',
    'C' => '#F9E79F',
    'D' => '#F5B7B1'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Horario Académico Visual</title>
<style>
body { font-family: Arial, sans-serif; margin:20px; }
table { border-collapse: collapse; width:100%; }
th, td { border:1px solid #ccc; text-align:center; padding:5px; vertical-align:top; }
th { background:#333; color:#fff; }
td { min-height:50px; }
.periodo { margin:2px; padding:3px; border-radius:4px; color:#000; font-size:0.9em; }
.filtros { margin-bottom:20px; display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.filtros select { padding:4px; }
.filtros button { padding:5px 10px; cursor:pointer; }
</style>
</head>
<body>

<h2>🕒 Horario Académico Visual</h2>

<form method="get" class="filtros">
    <label>Docente:</label>
    <select name="docente">
        <option value="">Todos</option>
        <?php while($d = $docentes->fetch_assoc()){
            $sel = ($filtro_docente == $d['id']) ? "selected" : "";
            echo "<option value='{$d['id']}' $sel>{$d['nombre']}</option>";
        } ?>
    </select>

    <label>Carrera:</label>
    <select name="carrera">
        <option value="">Todas</option>
        <?php while($c = $carreras->fetch_assoc()){
            $sel = ($filtro_carrera == $c['id']) ? "selected" : "";
            echo "<option value='{$c['id']}' $sel>{$c['nombre']}</option>";
        } ?>
    </select>

    <label>Jornada:</label>
    <select name="jornada">
        <option value="">Todas</option>
        <?php while($j = $jornadas->fetch_assoc()){
            $sel = ($filtro_jornada == $j['nombre']) ? "selected" : "";
            echo "<option value='{$j['nombre']}' $sel>{$j['nombre']}</option>";
        } ?>
    </select>

    <button type="submit">Filtrar</button>
</form>

<table>
<thead>
<tr>
    <th>Hora</th>
    <?php foreach($dias as $dia) echo "<th>$dia</th>"; ?>
</tr>
</thead>
<tbody>
<?php foreach($horas as $hi):
    $hf = date("H:i", strtotime($hi.' +40 minutes'));
?>
<tr>
    <td><?=$hi?> - <?=$hf?></td>
    <?php foreach($dias as $dia):
        echo "<td>";
        if(isset($asignaciones[$dia][$hi])){
            foreach($asignaciones[$dia][$hi] as $a){
                $color = $colores_seccion[$a['seccion']] ?? '#D7DBDD';
                echo "<div class='periodo' style='background:$color;'>";
                echo "<strong>{$a['curso']}</strong><br>";
                echo "{$a['docente']}<br>";
                echo "{$a['carrera']} ({$a['seccion']})";
                echo "</div>";
            }
        } else {
            echo "<span style='color:#999;'>-</span>";
        }
        echo "</td>";
    endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
