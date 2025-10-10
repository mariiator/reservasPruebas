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
    // Campos básicos obligatorios
    $camposObligatorios = ['hora_inicio', 'hora_fin', 'lugar', 'observaciones', 'fecha_inicio'];
    $faltanCampos = false;

    foreach ($camposObligatorios as $campo) {
        if (empty($_POST[$campo])) {
            $faltanCampos = true;
            break;
        }
    }

    // Si es reserva periódica, verificar también fecha_fin y días
    if (isset($_POST['reserva_periodica'])) {
        if (empty($_POST['fecha_fin']) || empty($_POST['dias'])) {
            $faltanCampos = true;
        }
    }

    if (!$faltanCampos) {
        // Aquí sigue todo tu código actual de inserción...
        $hora_inicio = $conn->real_escape_string($_POST['hora_inicio']);
        $hora_fin = $conn->real_escape_string($_POST['hora_fin']);
        $lugar = $conn->real_escape_string($_POST['lugar']);
        $observaciones = $conn->real_escape_string($_POST['observaciones']);
        $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
        $fecha = date('Y-m-d');
        
        // La hora de fin ahora se toma del formulario
        $hora_fin = $conn->real_escape_string($_POST['hora_fin']);

        // Generar id_espacio combinando fecha, hora_inicio, hora_fin y lugar
        $id_espacio = $fecha_inicio . '_' . $hora_inicio . '_' . $hora_fin . '_' . $lugar;

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

        // Si es reserva periódica
        if (isset($_POST['reserva_periodica'])) {

            $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
            $fecha_fin = $conn->real_escape_string($_POST['fecha_fin']);
            $diasSeleccionados = $_POST['dias'] ?? [];

            if ($fechaInicio > $fechaFin) {
                echo "<script>alert('La fecha de fin no puede ser anterior a la de inicio.');</script>";
                exit();
            }

            $reservasInsertadas = 0;
            $reservasOcupadas = [];

            // Recorremos todas las fechas entre inicio y fin
            while ($fechaInicio <= $fechaFin) {
                $diaSemana = $fechaInicio->format('N'); // 1 = Lunes ... 7 = Domingo

                if (in_array($diaSemana, $diasSeleccionados)) {
                    $fechaActual = $fechaInicio->format('Y-m-d');

                    // Generar id_espacio único
                    $id_espacio = $fechaActual . '_' . $hora_inicio . '_' . $hora_fin . '_' . $lugar;

                    // Comprobar si está ocupado
                    $sqlCheck = "SELECT COUNT(*) AS total FROM reservas 
                                WHERE lugar = ? AND fecha_inicio = ? 
                                AND ((hora_inicio < ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin > ?) OR (hora_inicio >= ? AND hora_fin <= ?))";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param("ssssssss", 
                        $lugar, $fechaActual, 
                        $hora_fin, $hora_inicio, 
                        $hora_inicio, $hora_fin,
                        $hora_inicio, $hora_fin
                    );
                    $stmtCheck->execute();
                    $resCheck = $stmtCheck->get_result()->fetch_assoc();

                    if ($resCheck['total'] == 0) {
                        // Insertar reserva
                        $sqlInsert = "INSERT INTO reservas 
                            (id_usuario, id_espacio, fecha_inicio, hora_inicio, hora_fin, lugar, observaciones, fecha_inicio)
                            VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
                        $stmtInsert = $conn->prepare($sqlInsert);
                        $stmtInsert->bind_param("issssss", $id_usuario, $id_espacio, $fechaActual, $hora_inicio, $hora_fin, $lugar, $observaciones);
                        $stmtInsert->execute();
                        $reservasInsertadas++;
                    } else {
                        $reservasOcupadas[] = $fechaActual;
                    }
                }

                $fechaInicio->modify('+1 day');
            }

            // Mensajes resumen
            if ($reservasInsertadas > 0) {
                $msg = "Se han creado $reservasInsertadas reservas periódicas.";
                if (!empty($reservasOcupadas)) {
                    $msg .= " Las siguientes fechas estaban ocupadas: " . implode(', ', $reservasOcupadas);
                }
                echo "<script>alert('$msg'); window.location='reservar_sala.php';</script>";
            } else {
                echo "<script>alert('No se pudo crear ninguna reserva. Todas las fechas estaban ocupadas.');</script>";
            }

        // Si es reserva simple (NO periódica)
        } else {

            $id_espacio = $fecha_inicio . '_' . $hora_inicio . '_' . $hora_fin . '_' . $lugar;

            // Comprobar si está ocupado
            $sqlCheck = "SELECT COUNT(*) AS total FROM reservas 
                        WHERE lugar = ? AND fecha_inicio = ? 
                        AND ((hora_inicio < ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin > ?) OR (hora_inicio >= ? AND hora_fin <= ?))";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("ssssssss", 
                $lugar, $fecha_inicio, 
                $hora_fin, $hora_inicio, 
                $hora_inicio, $hora_fin,
                $hora_inicio, $hora_fin
            );
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result()->fetch_assoc();

            if ($resCheck['total'] == 0) {
                $sqlInsertarReserva = "INSERT INTO reservas 
                    (id_usuario, id_espacio, fecha_inicio, hora_inicio, hora_fin, lugar, observaciones, fecha_inicio)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
                $stmtInsertarReserva = $conn->prepare($sqlInsertarReserva);
                $stmtInsertarReserva->bind_param("issssss", $id_usuario, $id_espacio, $fecha_inicio, $hora_inicio, $hora_fin, $lugar, $observaciones);
                $stmtInsertarReserva->execute();

                echo "<script>alert('Reserva creada con éxito.'); window.location='reservar_sala.php';</script>";
            } else {
                echo "<script>alert('La franja horaria ya está ocupada en esta sala.');</script>";
            }
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
        <form method="POST" id="formReserva">
            <label for="lugar">Seleccionar Espacio a Reservar:</label>
            <select id="lugar" name="lugar" required>
                <option value="">-- Selecciona un espacio --</option>
                <?php foreach ($espacios as $espacio): ?>
                    <option value="<?php echo $espacio['nombre']; ?>"><?php echo $espacio['nombre']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="fecha_inicio">Fecha Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required>

            <label>
                <input type="checkbox" id="reserva_periodica" name="reserva_periodica">
                Reserva periódica
            </label>

            <div id="opciones_periodicas" style="display:none;">
                <label for="fecha_fin">Fecha Fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin"><br><br>

                <label>Días de la semana:</label><br>
                <label><input type="checkbox" name="dias[]" value="1"> Lunes</label>
                <label><input type="checkbox" name="dias[]" value="2"> Martes</label>
                <label><input type="checkbox" name="dias[]" value="3"> Miércoles</label>
                <label><input type="checkbox" name="dias[]" value="4"> Jueves</label>
                <label><input type="checkbox" name="dias[]" value="5"> Viernes</label>
                <label><input type="checkbox" name="dias[]" value="6"> Sábado</label>
                <label><input type="checkbox" name="dias[]" value="7"> Domingo</label>
            </div>

            <label for="hora_inicio">Hora de Inicio:</label>
            <input type="time" id="hora_inicio" name="hora_inicio" required>

            <label for="hora_fin">Hora de Fin:</label>
            <input type="time" id="hora_fin" name="hora_fin" required>

            <label for="observaciones">Título:</label>
            <textarea id="observaciones" name="observaciones" required></textarea>

            <button type="submit">Reservar</button>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('reserva_periodica');
        const opciones = document.getElementById('opciones_periodicas');
        const form = document.getElementById('formReserva');

        // Mostrar/ocultar opciones periódicas
        checkbox.addEventListener('change', function() {
            opciones.style.display = this.checked ? 'block' : 'none';
        });

        // Validación al enviar
        form.addEventListener('submit', function(e) {
            if (checkbox.checked) {
                const fechaFin = document.getElementById('fecha_fin').value;
                const dias = document.querySelectorAll('#opciones_periodicas input[name="dias[]"]:checked');

                if (!fechaFin) {
                    e.preventDefault();
                    alert('Debes seleccionar la fecha fin para una reserva periódica.');
                    return;
                }
                if (dias.length === 0) {
                    e.preventDefault();
                    alert('Debes seleccionar al menos un día de la semana para una reserva periódica.');
                    return;
                }
            }
        });
    });
    </script>
</body>
</html>
