<?php
include("./conexion/conexion.php");

$carreras = $_POST['carreras']; // array
$anio     = intval($_POST['anio']);

if (!$carreras || !$anio) {
    echo json_encode([]);
    exit;
}

$carrerasLista = implode(",", array_map('intval', $carreras));

$sql = "
SELECT DISTINCT c.id, c.nombre
FROM cursos c
JOIN cursos_carrera cc ON cc.curso_id = c.id
WHERE cc.carrera_id IN ($carrerasLista)
AND cc.anio = $anio
ORDER BY c.nombre
";

$res = $conn->query($sql);

$cursos = [];
while ($r = $res->fetch_assoc()) {
    $cursos[] = $r;
}

echo json_encode($cursos);

?>
