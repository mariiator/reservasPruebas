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

$usuario = $_SESSION['usuario'];

// Obtener el usuario autenticado con CAS
//COMENTADO POR MARIA$usuario = phpCAS::getUser();

// Obtener la lista de espacios
$sqlEspacios = "SELECT nombre FROM espacios";
$resultadoEspacios = $conn->query($sqlEspacios);

$espacios = [];
if ($resultadoEspacios->num_rows > 0) {
    while ($row = $resultadoEspacios->fetch_assoc()) {
        $espacios[] = $row; // Almacena los nombres de los espacios
    }
} else {
    echo "<script>alert('No hay espacios disponibles.');</script>";
}

// Obtener las reservas ocupadas
$reservasOcupadasArray = [];
$sqlReservasOcupadas = "SELECT id_espacio FROM reservas";
$resultadoReservasOcupadas = $conn->query($sqlReservasOcupadas);
while ($row = $resultadoReservasOcupadas->fetch_assoc()) {
    $reservasOcupadasArray[] = $row['id_espacio'];
}

// Procesar la reserva si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['hora_inicio'], $_POST['lugar'], $_POST['observaciones'], $_POST['fecha'])) {
        $hora_inicio = $conn->real_escape_string($_POST['hora_inicio']);
        $lugar = $conn->real_escape_string($_POST['lugar']);
        $observaciones = $conn->real_escape_string($_POST['observaciones']);
        $fecha_reserva = $conn->real_escape_string($_POST['fecha']);
        $fecha = date('Y-m-d');
        
        // La hora de fin ahora se toma del formulario
        $hora_fin = $conn->real_escape_string($_POST['hora_fin']);

        // Generar id_espacio combinando fecha, hora_inicio, hora_fin y lugar
        $id_espacio = $fecha_reserva . '_' . $hora_inicio . '_' . $hora_fin . '_' . $lugar;

        // Verificar si el usuario existe en la base de datos
        $sqlBuscarUsuario = "SELECT id_usuario FROM usuarios WHERE nombre = ?";
        $stmt = $conn->prepare($sqlBuscarUsuario);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultadoUsuario = $stmt->get_result();

        if ($resultadoUsuario && $resultadoUsuario->num_rows > 0) {
            $row = $resultadoUsuario->fetch_assoc();
            $id_usuario = $row['id_usuario'];
        } else {
            // Si el usuario no existe, insertarlo
            $sqlInsertarUsuario = "INSERT INTO usuarios (nombre) VALUES (?)";
            $stmtInsertarUsuario = $conn->prepare($sqlInsertarUsuario);
            $stmtInsertarUsuario->bind_param("s", $usuario);

            if ($stmtInsertarUsuario->execute()) {
                $id_usuario = $stmtInsertarUsuario->insert_id;
                echo "<script>alert('Usuario creado con éxito.');</script>";
            } else {
                echo "<script>alert('Error al crear el usuario.');</script>";
                exit();
            }
        }

        // Verificar si la franja horaria está ocupada
        if (!in_array($id_espacio, $reservasOcupadasArray)) {
            $sqlInsertarReserva = "INSERT INTO reservas (id_usuario, id_espacio, hora_inicio, hora_fin, fecha_reserva, lugar, observaciones, fecha) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsertarReserva = $conn->prepare($sqlInsertarReserva);

            $stmtInsertarReserva->bind_param("isssssss", $id_usuario, $id_espacio, $hora_inicio, $hora_fin, $fecha_reserva, $lugar, $observaciones, $fecha);

            if ($stmtInsertarReserva->execute()) {
                echo "<script>alert('Reserva creada con éxito.');</script>";
                header("Location: reservar_sala.php");
                exit();
            } else {
                echo "<script>alert('Error al crear la reserva.');</script>";
            }

        } else {
            echo "<script>alert('La franja horaria ya está ocupada.');</script>";
        }
    } else {
        echo "<script>alert('Por favor, completa todos los campos.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Espacios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
            color: #555;
        }
        select, input, textarea, button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            background-color: #5cb85c;
            color: #ffffff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #4cae4c;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
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
        <h1>Reserva de Espacios</h1>
        <form method="POST">
            <label for="lugar">Seleccionar Espacio a Reservar:</label>
            <select id="lugar" name="lugar" required>
                <option value="">-- Selecciona un espacio --</option>
                <?php foreach ($espacios as $espacio): ?>
                    <option value="<?php echo $espacio['nombre']; ?>"><?php echo $espacio['nombre']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" required>

            <label for="hora_inicio">Hora de Inicio:</label>
            <input type="time" id="hora_inicio" name="hora_inicio" required>

            <label for="hora_fin">Hora de Fin:</label>
            <input type="time" id="hora_fin" name="hora_fin" required>

            <label for="observaciones">Titulo:</label>
            <textarea id="observaciones" name="observaciones" required></textarea>

            <button type="submit">Reservar</button>
        </form>
    </div>
</body>
</html>
