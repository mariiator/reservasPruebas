<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


require __DIR__ . '/config_local.php';

// Si no hay sesión CAS, redirige a CAS
/*COMENTADO POR MARIA
phpCAS::forceAuthentication();

// Mitiga fijación de sesión tras autenticación
if (empty($_SESSION['session_hardened'])) {
  session_regenerate_id(true);
  $_SESSION['session_hardened'] = true;
}
*/

$id_usuario_actual = $_SESSION['usuario'];

// Obtener usuario autenticado y su rol
//COMENTADO POR MARIA$id_usuario_actual = phpCAS::getUser();
$query_usuario = "SELECT id_usuario, rol_id FROM usuarios WHERE nombre = ?";
$stmt_usuario = $conn->prepare($query_usuario);
if (!$stmt_usuario) {
    die("Error preparando la consulta: " . $conn->error);
}

// Usa el nombre obtenido de phpCAS
//COMENTADO POR MARIA$id_usuario_actual = trim(phpCAS::getUser());
$stmt_usuario->bind_param("s", $id_usuario_actual);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

// Verificar si el usuario existe
if ($result_usuario->num_rows === 0) {
    die("Error: El usuario no está registrado en la tabla usuarios.");
}

$usuario = $result_usuario->fetch_assoc();
$rol_id = $usuario['rol_id'];
$query_reservas = "";

if ($rol_id == 1) {
    // Admin: puede autorizar todas las reservas pendientes
    $query_reservas = "
        SELECT r.fecha_reserva, r.hora_inicio, r.hora_fin, r.lugar, r.id_usuario, u.nombre, r.estado, e.id_espacio AS esp_numero
        FROM reservas r
        INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
        INNER JOIN espacios e ON r.lugar = e.nombre
        WHERE r.estado = 'pendiente'
    ";
} else {
    // Verificar permisos para espacios específicos
    $query_permisos = "SELECT e.nombre FROM permisos_espacios p
                       INNER JOIN espacios e ON p.id_espacio = e.id_espacio
                       WHERE p.id_usuario = ?";
    $stmt_permisos = $conn->prepare($query_permisos);
    $stmt_permisos->bind_param("i", $usuario['id_usuario']);
    $stmt_permisos->execute();
    $result_permisos = $stmt_permisos->get_result();

    // Si no hay permisos, no hay que hacer nada
    if ($result_permisos->num_rows == 0) {
        die("No tienes permisos para autorizar reservas.");
    } else {
        // Crear un array con los nombres de los espacios permitidos
        $espacios_permitidos = [];
        while ($permiso = $result_permisos->fetch_assoc()) {
            $espacios_permitidos[] = $permiso['nombre'];
        }

        // Construir la cláusula WHERE para filtrar las reservas
        $espacios_nombres = implode("','", $espacios_permitidos);

        $query_reservas = "
            SELECT r.fecha_reserva, r.hora_inicio, r.hora_fin, r.lugar, r.id_usuario, u.nombre, r.estado, e.id_espacio AS esp_numero, r.observaciones
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            INNER JOIN espacios e ON r.lugar = e.nombre
            WHERE r.estado = 'pendiente' AND r.lugar IN ('$espacios_nombres')
        ";
    }
}



// Depuración: Verifica que la consulta no esté vacía
if (empty($query_reservas)) {
    die("Error: La consulta de reservas está vacía.");
}

$result = $conn->query($query_reservas);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

// Comprobar si el usuario tiene permiso para autorizar en el espacio
if (isset($_GET['id']) && isset($_GET['accion']) && isset($_GET['fecha_reserva']) && isset($_GET['hora_inicio']) && isset($_GET['hora_fin']) && isset($_GET['lugar'])) {
    $fecha_reserva = $_GET['fecha_reserva'];
    $hora_inicio = $_GET['hora_inicio'];
    $hora_fin = $_GET['hora_fin'];
    $lugar = $_GET['lugar'];
    $accion = $_GET['accion'];

    // Verificar si el usuario tiene permiso para autorizar este espacio
    $query_permiso_autorizar = "SELECT id_espacio FROM permisos_espacios WHERE id_usuario = ? AND id_espacio = (SELECT id_espacio FROM espacios WHERE nombre = ? AND puede_autorizar = 1)";
    $stmt_permiso = $conn->prepare($query_permiso_autorizar);
    $stmt_permiso->bind_param("is", $usuario['id_usuario'], $lugar); // Se pasa el lugar como nombre del espacio
    $stmt_permiso->execute();
    $result_permiso = $stmt_permiso->get_result();

    if ($rol_id == 1 || $result_permiso->num_rows > 0) {
        // Permitir la acción
        $update_query = ($accion == 'autorizar') 
            ? "UPDATE reservas SET estado = 'confirmado' WHERE fecha_reserva = ? AND hora_inicio = ? AND hora_fin = ? AND lugar = ?"
            : "UPDATE reservas SET estado = 'cancelada' WHERE fecha_reserva = ? AND hora_inicio = ? AND hora_fin = ? AND lugar = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("ssss", $fecha_reserva, $hora_inicio, $hora_fin, $lugar);
        $stmt_update->execute();
    } else {
        echo "No tienes permiso para autorizar reservas.";
    }
    
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizar Reservas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 900px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            margin: 5px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn:hover {
            opacity: 0.9;
        }
        
        p {
            text-align: center;
            color: #666;
        }

        .btn-ir-al-inicio {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            padding: 12px 20px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }

        .btn-ir-al-inicio:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>
<a href="index.php" class="btn-ir-al-inicio">Ir al Inicio</a>
    <div class="container">
        <h1>Autorizar Reservas</h1>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre Usuario</th>
                        <th>Lugar</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['lugar']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_reserva']); ?></td>
                        <td><?php echo htmlspecialchars($row['hora_inicio'] . ' - ' . $row['hora_fin']); ?></td>
                        <td>
                        <a href="autorizar_reservas.php?id=<?php echo $row['esp_numero']; ?>&fecha_reserva=<?php echo $row['fecha_reserva']; ?>&hora_inicio=<?php echo $row['hora_inicio']; ?>&hora_fin=<?php echo $row['hora_fin']; ?>&lugar=<?php echo $row['lugar']; ?>&accion=autorizar" class="btn btn-success">Autorizar</a>
                        <a href="autorizar_reservas.php?id=<?php echo $row['esp_numero']; ?>&fecha_reserva=<?php echo $row['fecha_reserva']; ?>&hora_inicio=<?php echo $row['hora_inicio']; ?>&hora_fin=<?php echo $row['hora_fin']; ?>&lugar=<?php echo $row['lugar']; ?>&accion=rechazar" class="btn btn-danger">Rechazar</a>

                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay reservas pendientes.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>