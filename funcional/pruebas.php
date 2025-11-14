<?php
include("conexion/conexion.php");

$sql = "SELECT id, nombre FROM cursos";
$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    echo $row['id'] . " - " . $row['nombre'] . "<br>";
}
?>
