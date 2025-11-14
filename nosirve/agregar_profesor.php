<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Docentes</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="container">
        <h2>Registrar nuevo docente</h2>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre_docente">Nombre del docente:</label>
                <input type="text" id="nombre_docente" name="nombre_docente" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo electrónico:</label>
                <input type="text" id="correo" name="correo" required>
            </div>

            <div class="form-group">
                <label for="telefono">Número de teléfono:</label>
                <input type="text" id="telefono" name="telefono" required>
            </div>

            <button type="submit" name="nuevo_docente">Guardar Docente</button>
            <a href="registro.php" type="submit" >Regresar</a>

        </form>
    </div>

    <?php
    include __DIR__ . '/conexion/conexion.php';
 

if (isset($_POST['nuevo_docente'])) {

    $nombre_docente = isset($_POST['nombre_docente']) ? trim($_POST['nombre_docente']) : '';
    $correo        = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $telefono      = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';

    if ($nombre_docente !== '' && $correo !== '' && $telefono !== '') {

        if (!$conn) {
            die("❌ Error de conexión a la base de datos.");
        }
        $stmt = $conn->prepare("INSERT INTO docentes (nombre, telefono, correo) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("❌ Error al preparar la consulta: " . $conn->error);
        }
        $stmt->bind_param("sss", $nombre_docente, $telefono, $correo);

        if ($stmt->execute()) {
            echo "<script>alert('✅ Docente agregado correctamente');</script>";
        } else {
            echo "<script>alert('❌ Error al agregar docente: " . htmlspecialchars($stmt->error, ENT_QUOTES) . "');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('⚠️ Todos los campos son obligatorios');</script>";
    }
}
?>



</body>
</html>
