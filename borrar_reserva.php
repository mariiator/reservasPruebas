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

// Obtener usuario autenticado y su información
//COMENTADO POR MARIA$id_usuario_actual = phpCAS::getUser();
$query_usuario = "SELECT id_usuario, rol_id FROM usuarios WHERE nombre = ?";
$stmt_usuario = $conn->prepare($query_usuario);
if (!$stmt_usuario) {
    die("Error preparando la consulta: " . $conn->error);
}
$stmt_usuario->bind_param("s", $id_usuario_actual);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    die("Error: El usuario no está registrado en la tabla usuarios.");
}
$usuario = $result_usuario->fetch_assoc();
$id_usuario = $usuario['id_usuario'];
$rol_id = $usuario['rol_id'];

$mensaje = '';

// Procesar la solicitud de eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['esp_numero'])) {
    $esp_numero = $_POST['esp_numero'];

    // Obtener id_espacio de la reserva
    $stmt_reserva = $conn->prepare("SELECT id_espacio FROM reservas WHERE esp_numero = ?");
    $stmt_reserva->bind_param("i", $esp_numero);
    $stmt_reserva->execute();
    $result_reserva = $stmt_reserva->get_result();

    if ($result_reserva->num_rows === 0) {
        $mensaje = "La reserva no existe.";
    } else {
        $reserva = $result_reserva->fetch_assoc();
        $id_espacio = $reserva['id_espacio'];

        // Verificar permisos del usuario
        $stmt_permiso = $conn->prepare("
            SELECT 1 FROM permisos_espacios
            WHERE id_usuario = ? AND id_espacio = ? AND puede_borrar = 1
        ");
        $stmt_permiso->bind_param("ii", $id_usuario, $id_espacio);
        $stmt_permiso->execute();
        $result_permiso = $stmt_permiso->get_result();

        if ($rol_id == 1 || $result_permiso->num_rows > 0) {
            $stmt_delete = $conn->prepare("DELETE FROM reservas WHERE esp_numero = ?");
            $stmt_delete->bind_param("i", $esp_numero);
            $stmt_delete->execute();
            $mensaje = $stmt_delete->affected_rows > 0 ? "Reserva eliminada correctamente." : "No se pudo eliminar la reserva.";
        } else {
            $mensaje = "No tienes permisos para eliminar esta reserva.";
        }
    }
}

// Obtener espacios permitidos
$espacios_permitidos = [];
if ($rol_id != 1) {
    $stmt_permisos = $conn->prepare("
        SELECT id_espacio FROM permisos_espacios WHERE id_usuario = ? AND puede_borrar = 1
    ");
    $stmt_permisos->bind_param("i", $id_usuario);
    $stmt_permisos->execute();
    $result_permisos = $stmt_permisos->get_result();
    while ($permiso = $result_permisos->fetch_assoc()) {
        $espacios_permitidos[] = $permiso['id_espacio'];
    }
}

// Filtros de lugar y fecha
$condiciones = [];
$params = [];
$types = '';

if ($rol_id != 1 && empty($espacios_permitidos)) {
    // Sin permisos, no muestra reservas
    $reservas = [];
} else {
    // Filtro por lugar
    if (!empty($_GET['lugar'])) {
        $stmt_lugar = $conn->prepare("SELECT id_espacio FROM espacios WHERE nombre = ?");
        $stmt_lugar->bind_param("s", $_GET['lugar']);
        $stmt_lugar->execute();
        $result_lugar = $stmt_lugar->get_result();
        if ($result_lugar->num_rows > 0) {
            $esp = $result_lugar->fetch_assoc();
            $condiciones[] = "id_espacio = ?";
            $params[] = $esp['id_espacio'];
            $types .= 'i';
        } else {
            // Lugar no válido, no muestra nada
            $reservas = [];
        }
    }

    // Filtro por fecha
    if (!empty($_GET['fecha'])) {
        $condiciones[] = "fecha_reserva = ?";
        $params[] = $_GET['fecha'];
        $types .= 's';
    }

    // Filtrar según permisos (solo usuarios no admin)
    if ($rol_id != 1 && !empty($espacios_permitidos)) {
        $placeholders = implode(',', array_fill(0, count($espacios_permitidos), '?'));
        $condiciones[] = "id_espacio IN ($placeholders)";
        foreach ($espacios_permitidos as $id) {
            $params[] = $id;
            $types .= 'i';
        }
    }

    $sql = "SELECT * FROM reservas";
    if (!empty($condiciones)) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }
    $sql .= " ORDER BY fecha_reserva, hora_inicio";

    $stmt_reservas = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt_reservas->bind_param($types, ...$params);
    }
    $stmt_reservas->execute();
    $reservas = $stmt_reservas->get_result();
}

// Obtener lugares disponibles para el filtro
$lugares_disponibles = [];
if ($rol_id == 1) {
    $result_lugares = $conn->query("SELECT DISTINCT lugar FROM reservas");
    while ($l = $result_lugares->fetch_assoc()) {
        $lugares_disponibles[] = $l['lugar'];
    }
} else {
    if (!empty($espacios_permitidos)) {
        $ids = implode(',', $espacios_permitidos);
        $result_lugares = $conn->query("SELECT nombre FROM espacios WHERE id_espacio IN ($ids)");
        while ($l = $result_lugares->fetch_assoc()) {
            $lugares_disponibles[] = $l['nombre'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrar Reserva</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 800px;
        margin: 20px auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    h1 {
        text-align: center;
        color: #dc3545;
    }

    .mensaje {
        text-align: center;
        padding: 10px;
        background-color: #28a745;
        color: #fff;
        margin-bottom: 20px;
        border-radius: 8px;
    }

    .mensaje.error {
        background-color: #dc3545;
    }

    form {
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    label {
        font-weight: bold;
        color: #555;
        margin-right: 5px;
    }

    select, input[type="date"], button {
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    select {
        background-color: #fff;
        color: #555;
    }

    select:hover, input[type="date"]:hover, button:hover {
        border-color: #007bff;
    }

    button {
        background-color: #dc3545; 
        color: #fff;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s;
        padding: 8px 16px;
        font-size: 14px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    table th, table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: center;
    }

    table th {
        background-color: #007bff;
        color: #fff;
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

    .filtrar{
        background-color: green;
    }
    
</style>

<script>
    function confirmarEliminacion() {
        return confirm("¿Estás seguro de que deseas borrar esta reserva?");
    }
</script>
</head>
<body>
    <a href="index.php" class="btn-ir-al-inicio">Ir al Inicio</a>
    <h1>Borrar Reserva</h1>
    <?php if ($mensaje): ?>
        <p><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="GET" action="">
        <label for="lugar">Lugar:</label>
        <select name="lugar" id="lugar">
            <option value="">-- Todos los lugares --</option>
            <?php foreach ($lugares_disponibles as $lugar): ?>
                <option value="<?= htmlspecialchars($lugar) ?>" <?= isset($_GET['lugar']) && $_GET['lugar'] === $lugar ? 'selected' : '' ?>><?= htmlspecialchars($lugar) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="fecha">Fecha:</label>
        <input type="date" name="fecha" id="fecha" value="<?= isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : '' ?>">

        <button type="submit" class="filtrar">Filtrar</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Lugar</th>
                <th>Fecha</th>
                <th>Hora de Inicio</th>
                <th>Hora de Fin</th>
                <th>Observaciones</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($reservas): ?>
                <?php while ($reserva = $reservas->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($reserva['lugar']) ?></td>
                        <td><?= htmlspecialchars($reserva['fecha_reserva']) ?></td>
                        <td><?= htmlspecialchars($reserva['hora_inicio']) ?></td>
                        <td><?= htmlspecialchars($reserva['hora_fin']) ?></td>
                        <td><?= htmlspecialchars($reserva['observaciones']) ?></td>
                        <td>
                            <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de que deseas borrar esta reserva?');">
                                <input type="hidden" name="esp_numero" value="<?= $reserva['esp_numero'] ?>">
                                <input type="hidden" name="lugar" value="<?= $reserva['lugar'] ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No hay reservas disponibles.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>