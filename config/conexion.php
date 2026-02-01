<?php
// Busca el archivo config.php que está en su misma carpeta
require_once __DIR__ . '/config.php'; 

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($con, "utf8");

if (!$con) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>