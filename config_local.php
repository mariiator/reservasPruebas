<?php
define('APP_ENV', 'local');
define('USE_CAS', false); // no usar CAS en local

// Conexión a la base de datos local
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservas_pruebas";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}