<?php
// Manejar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $rol_id = (int)$_POST['rol_id']; // Asegurarse de que el rol es un número entero

    // Insertar el nuevo usuario en la base de datos
    $sql = "INSERT INTO usuarios (nombre, rol_id) VALUES ('$nombre', '$rol_id')";

    if ($conn->query($sql) === TRUE) {
        $mensaje = "Usuario añadido correctamente.";
        $mensaje_clase = "message";
    } else {
        $mensaje = "Error al añadir el usuario: " . $conn->error;
        $mensaje_clase = "message error";
    }
}

// Consulta para obtener los roles
$sqlRoles = "SELECT id, nombre FROM roles";
$resultadoRoles = $conn->query($sqlRoles);

if (!$resultadoRoles) {
    die("Error en la consulta de roles: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 50%;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 50px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            display: inline-block;
        }

        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin: 8px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            margin-top: 20px;
            color: green;
            font-size: 16px;
        }

        .message.error {
            color: red;
        }

        .back-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            margin-top: 20px;
            cursor: pointer;
        }

        .back-btn:hover {
            background-color: #e53935;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Añadir Nuevo Usuario</h2>
        
        <?php if (isset($mensaje)): ?>
            <div class="<?php echo $mensaje_clase; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required><br><br>

            <label for="rol_id">Rol:</label>
            <select id="rol_id" name="rol_id" required>
                <?php
                if ($resultadoRoles->num_rows > 0) {
                    while ($row = $resultadoRoles->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . $row['nombre'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay roles disponibles</option>";
                }
                ?>
            </select><br><br>

            <button type="submit">Añadir Usuario</button>
        </form>

        <button class="back-btn" onclick="window.location.href='administrar_usuarios.php'">Volver a la Administración de Usuarios</button>
    </div>
</body>
</html>
