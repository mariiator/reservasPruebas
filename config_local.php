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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mitiga fijación de sesión tras autenticación
if (empty($_SESSION['session_hardened'])) {
    session_regenerate_id(true);
    $_SESSION['session_hardened'] = true;
}

if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'admin1@example.com';
}