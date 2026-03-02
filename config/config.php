<?php
// Configuración BD
define('DB_TYPE', 'mysql');
define('DB_HOST', '46.28.42.226');
define('DB_NAME', 'u760464709_24005366_bd');
define('DB_USER', 'u760464709_24005366_usr');
define('DB_PASS', '!|F>1$H1p');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Mostrar errores (desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Opciones de conexión
$opciones = [
    "tipo"       => DB_TYPE,
    "servidor"   => DB_HOST,
    "bd"         => DB_NAME,
    "usuario"    => DB_USER,
    "contrasena" => DB_PASS
];

// Cargar la librería PDO
require_once __DIR__ . '/conexion.php';

?>