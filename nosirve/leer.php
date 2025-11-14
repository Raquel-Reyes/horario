<?php
include("db.php");

$result = $conexion->query("SELECT profesor, curso, carrera, grado, periodos FROM asignaciones");
$asignaciones = [];
while($row = $result->fetch_assoc()){
    $asignaciones[] = $row;
}

header('Content-Type: application/json');
echo json_encode($asignaciones);

$conexion->close();
?>
