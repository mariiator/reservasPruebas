<?php
$fecha = $_GET['fecha'];

$sql = "
    SELECT 
        u.nombre AS usuario, 
        e.nombre AS espacio, 
        r.hora_inicio, 
        r.hora_fin, 
        r.observaciones 
    FROM reservas r
    JOIN usuarios u ON r.id_usuario = u.id_usuario
    JOIN espacios e ON r.lugar = e.nombre
    WHERE r.fecha_reserva = '$fecha' AND r.estado = 'confirmado'";

$result = $conn->query($sql);

$reservas = [];
while ($row = $result->fetch_assoc()) {
    $reservas[] = $row;
}

echo json_encode($reservas);

$conn->close();
?>
