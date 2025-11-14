<?php
$host = "bzqkayjaezumpwe9dulb-mysql.services.clever-cloud.com";
$user = "uafgwvplhmoozfhv"; // tu usuario
$pass = "xZuGISpYXWBh1DbDtZoh"; // tu contraseña
$dbname = "bzqkayjaezumpwe9dulb";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
