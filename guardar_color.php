<?php
require_once 'config_local.php';
header('Content-Type: application/json');

// Habilitar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Leer los datos enviados desde JavaScript
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos
$id_espacio = isset($data['id_espacio']) ? intval($data['id_espacio']) : null;
$color = isset($data['color']) ? $data['color'] : null;

if ($id_espacio && $color) {
    // Conexión a la base de datos
    $conn = new mysqli("pruebas.maristaschamberi.com", "root", "Chamberi10$", "reservas");

    if ($conn->connect_error) {
        echo json_encode(["success" => false, "error" => $conn->connect_error]);
        exit;
    }

    // Actualizar color en la base de datos
    $query = "UPDATE espacios SET color = ? WHERE id_espacio = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $color, $id_espacio);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Datos inválidos"]);
}
?>
