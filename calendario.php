<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config_local.php';
// Configuración de conexión
/*$servername = "pruebas.maristaschamberi.com";
$username = "root";
$password = "Chamberi10$";
$dbname = "reservas";*/
$id_espacio = $_GET['id'];

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener reservas y colores desde las tablas
    $query = "
        SELECT 
            r.fecha_reserva, 
            r.hora_inicio, 
            r.hora_fin,
            r.observaciones,
            e.nombre AS espacio_nombre,
            e.color AS espacio_color,
			e.id_espacio AS id_espacio
        FROM reservas r
        JOIN espacios e ON r.lugar = e.nombre
        WHERE r.estado = 'confirmado' AND e.id_espacio IN ($id_espacio)";
    $stmt = $pdo->query($query);

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $color = $row['espacio_color'] ?? '#3498db'; // Color predeterminado si no hay color definido
        $events[] = [
            'title' => $row['espacio_nombre'] . ": \n" . $row['observaciones'],
            'start' => $row['fecha_reserva'] . 'T' . $row['hora_inicio'],
            'end' => $row['fecha_reserva'] . 'T' . $row['hora_fin'],
            'color' => $color,
            'textColor' => '#ffffff',
			'espacio' => $row['espacio_nombre'],
			'id_espacio' => $row['id_espacio']
        ];
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
