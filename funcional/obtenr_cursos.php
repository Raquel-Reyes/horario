<?php
include("conexion/conexion.php");

if (!isset($_GET['carrera']) || !isset($_GET['anio'])) {
    echo json_encode([]);
    exit;
}

$carrera = intval($_GET['carrera']);
$anio = intval($_GET['anio']);

$sql = "SELECT c.id, c.nombre 
        FROM cursos c
        JOIN cursos_carrera cc ON cc.curso_id = c.id
        WHERE cc.carrera_id = $carrera
          AND cc.anio = $anio
        ORDER BY c.nombre";

$res = $conn->query($sql);

$cursos = [];
while ($row = $res->fetch_assoc()) {
    $cursos[] = $row;
}

echo json_encode($cursos);
?>
