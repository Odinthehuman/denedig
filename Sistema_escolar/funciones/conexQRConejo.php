<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "u880452948_s_escolar";

// Conexión a la base de datos
$conexion = mysqli_connect($servername, $username, $password, $dbname);
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, 'utf8');


?>
