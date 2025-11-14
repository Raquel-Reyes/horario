<?php
include("conexion/conexion.php");

if (isset($_GET['curso_id'])) {
    $curso_id = intval($_GET['curso_id']);

    $res = $conn->query("SELECT nivel_id FROM cursos WHERE id = $curso_id");
    if ($res && $row = $res->fetch_assoc()) {
        $nivel_id = $row['nivel_id'];

        $sql = "SELECT id, nombre FROM carreras WHERE nivel_id = $nivel_id ORDER BY nombre";
        $res2 = $conn->query($sql);

        if ($res2 && $res2->num_rows > 0) {
            while($r = $res2->fetch_assoc()) {
                echo "<option value='{$r['id']}'>{$r['nombre']}</option>";
            }
        } else {
            echo "<option value=''>No hay carreras para este nivel</option>";
        }
    } else {
        echo "<option value=''>Curso no encontrado</option>";
    }
}
?>

