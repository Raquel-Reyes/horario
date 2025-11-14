<?php
include("conexion.php");
include("funciones.php");

$docente = $_POST['docente_id'];
$curso = $_POST['curso_id'];
$carreras = $_POST['carrera_ids']; // array de IDs
$seccion = $_POST['seccion_id'];
$dia = $_POST['dia_semana'];
$cantidad_periodos = intval($_POST['cantidad']); // número de periodos

$hora_base = new DateTime("07:00:00"); // hora de inicio
$duracion = new DateInterval('PT40M'); // 40 minutos por periodo

for ($i=0; $i < $cantidad_periodos; $i++) {
    $hora_inicio = clone $hora_base;
    $hora_fin = clone $hora_base;
    $hora_fin->add($duracion);

    // validar conflicto de horario
    if(validar_conflicto($conn, $docente, $dia, $hora_inicio->format('H:i:s'), $hora_fin->format('H:i:s'))) {
        echo "<script>alert('⚠️ Conflicto detectado en el período ".($i+1)."'); window.history.back();</script>";
        exit;
    }

    // asignar a cada carrera
    foreach ($carreras as $carrera) {
        $stmt = $conn->prepare("INSERT INTO asignaciones 
            (docente_id, curso_id, carrera_id, seccion_id, dia_semana, hora_inicio, hora_fin)
            VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "iiiisss",
            $docente,
            $curso,
            $carrera,
            $seccion,
            $dia,
            $hora_inicio->format('H:i:s'),
            $hora_fin->format('H:i:s')
        );
        $stmt->execute();
    }

    // avanzar a la siguiente hora base
    $hora_base->add($duracion);
}

echo "<script>alert('✅ Asignaciones generadas automáticamente'); window.location='registro.php';</script>";
?>









<?php
include("db.php");

// Recibir JSON enviado por fetch
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    die("No se recibieron datos.");
}

// PREPARAR CONSULTA
$stmt = $conexion->prepare("INSERT INTO asignaciones (profesor, curso, carrera, grado, periodos) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssii", $profesor, $curso, $carrera, $grado, $periodos);

// RECORRER Y GUARDAR
foreach ($data as $fila) {
    $profesor = $fila["profesor"];
    $curso = $fila["curso"];
    $carrera = $fila["carrera"];
    $grado = intval($fila["grado"]);
    $periodos = intval($fila["periodos"]);
    $stmt->execute();
}

echo "✅ Datos guardados correctamente.";

$stmt->close();
$conexion->close();
?>
