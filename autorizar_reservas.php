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
        SELECT r.fecha, r.hora_inicio, r.hora_fin, r.lugar, r.id_usuario, u.nombre, r.estado, e.id_espacio AS esp_numero
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
            SELECT r.fecha, r.hora_inicio, r.hora_fin, r.lugar, r.id_usuario, u.nombre, r.estado, e.id_espacio AS esp_numero, r.observaciones
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
if (isset($_GET['id']) && isset($_GET['accion']) && isset($_GET['fecha_inicio']) && isset($_GET['hora_inicio']) && isset($_GET['hora_fin']) && isset($_GET['lugar'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'] ?? null;
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
        if (!empty($fecha_fin)) {
            // Reserva periódica: actualizamos todas las fechas entre inicio y fin
            $update_query = ($accion == 'autorizar')
                ? "UPDATE reservas SET estado = 'confirmado' WHERE lugar = ? AND fecha >= ? AND fecha <= ? AND hora_inicio = ? AND hora_fin = ?"
                : "UPDATE reservas SET estado = 'cancelada' WHERE lugar = ? AND fecha >= ? AND fecha <= ? AND hora_inicio = ? AND hora_fin = ?";

            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("sssss", $lugar, $fecha_inicio, $fecha_fin, $hora_inicio, $hora_fin);
        } else {
            // Reserva normal: solo esa fecha
            $update_query = ($accion == 'autorizar')
                ? "UPDATE reservas SET estado = 'confirmado' WHERE fecha = ? AND hora_inicio = ? AND hora_fin = ? AND lugar = ?"
                : "UPDATE reservas SET estado = 'cancelada' WHERE fecha = ? AND hora_inicio = ? AND hora_fin = ? AND lugar = ?";

            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ssss", $fecha_inicio, $hora_inicio, $hora_fin, $lugar);
        }

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
            padding: 9px;
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
                    <?php
                    $reservas_agregadas = [];

                    // Recorrer todas las reservas pendientes
                    while ($reserva = mysqli_fetch_assoc($result)) {
                        // Generamos una clave para agrupar reservas del mismo lugar, hora y observaciones
                        $grupo_key = ($reserva['lugar'] ?? '') . '_' . ($reserva['hora_inicio'] ?? '') . '_' . ($reserva['hora_fin'] ?? '') . '_' . ($reserva['observaciones'] ?? '');

                        if (!isset($reservas_agregadas[$grupo_key])) {
                            // Primera vez que encontramos este grupo: inicializamos
                            $reservas_agregadas[$grupo_key] = [
                                'nombre' => $reserva['nombre'] ?? '',
                                'lugar' => $reserva['lugar'] ?? '',
                                'hora_inicio' => $reserva['hora_inicio'] ?? '',
                                'hora_fin' => $reserva['hora_fin'] ?? '',
                                'observaciones' => $reserva['observaciones'] ?? '',
                                'fecha_inicio' => $reserva['fecha'] ?? '',
                                'fecha_fin' => null,
                                'esp_numero' => $reserva['esp_numero'] ?? ''
                            ];
                        } else {
                            // Actualizamos fecha_fin solo si hay varias fechas
                            if (!empty($reserva['fecha']) && $reserva['fecha'] > $reservas_agregadas[$grupo_key]['fecha_inicio']) {
                                $reservas_agregadas[$grupo_key]['fecha_fin'] = $reserva['fecha'];
                            }
                        }
                    }

                    // Mostrar tabla
                    foreach ($reservas_agregadas as $reserva):
                        $fecha_inicio = $reserva['fecha_inicio'];
                        $fecha_fin = $reserva['fecha_fin'];
                        $fecha_mostrar = $fecha_inicio;

                        if (!empty($fecha_fin)) {
                            $fecha_mostrar .= " - " . $fecha_fin;
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reserva['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($reserva['lugar']); ?></td>
                        <td><?php echo htmlspecialchars($fecha_mostrar); ?></td>
                        <td><?php echo htmlspecialchars($reserva['hora_inicio'].' - '.$reserva['hora_fin']); ?></td>
                        <td>
                            <a href="autorizar_reservas.php?id=<?php echo $reserva['esp_numero']; ?>&fecha_inicio=<?php echo $reserva['fecha_inicio']; ?>&fecha_fin=<?php echo $reserva['fecha_fin']; ?>&hora_inicio=<?php echo $reserva['hora_inicio']; ?>&hora_fin=<?php echo $reserva['hora_fin']; ?>&lugar=<?php echo $reserva['lugar']; ?>&accion=autorizar" class="btn btn-success">Autorizar</a>
                            <a href="autorizar_reservas.php?id=<?php echo $reserva['esp_numero']; ?>&fecha_inicio=<?php echo $reserva['fecha_inicio']; ?>&fecha_fin=<?php echo $reserva['fecha_fin']; ?>&hora_inicio=<?php echo $reserva['hora_inicio']; ?>&hora_fin=<?php echo $reserva['hora_fin']; ?>&lugar=<?php echo $reserva['lugar']; ?>&accion=rechazar" class="btn btn-danger">Rechazar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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