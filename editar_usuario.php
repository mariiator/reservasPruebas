<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config_local.php';

// Verificar que se ha pasado un ID en la URL
if (isset($_GET['id'])) {
    $id_usuario = (int)$_GET['id'];

    // Obtener los datos del usuario a editar
    $sqlUsuario = "SELECT * FROM usuarios WHERE id_usuario = $id_usuario";
    $resultadoUsuario = $conn->query($sqlUsuario);

    if ($resultadoUsuario->num_rows > 0) {
        $usuario = $resultadoUsuario->fetch_assoc();
    } else {
        die("Usuario no encontrado.");
    }
} else {
    die("ID de usuario no proporcionado.");
}

// Manejar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $rol_id = (int)$_POST['rol_id'];

    // Actualizar los datos del usuario
    $sqlUpdate = "UPDATE usuarios SET nombre = '$nombre', rol_id = $rol_id WHERE id_usuario = $id_usuario";

    if ($conn->query($sqlUpdate) === TRUE) {
        $mensaje = "Usuario actualizado correctamente.";
        $mensaje_clase = "message";
    } else {
        $mensaje = "Error al actualizar el usuario: " . $conn->error;
        $mensaje_clase = "message error";
    }

// Verificar si el usuario tiene permisos de autorizar o borrar
if (isset($_POST['espacios'])) {
    $espacios = $_POST['espacios'];  // Lista de espacios a los que se le asignan permisos

    // Eliminar los permisos previos
    $sqlDelete = "DELETE FROM permisos_espacios WHERE id_usuario = $id_usuario";
    $conn->query($sqlDelete);

    // Insertar los nuevos permisos
    foreach ($espacios as $espacio) {
        $puede_borrar = isset($_POST['puede_borrar'][$espacio]) ? 1 : 0;
        $puede_autorizar = isset($_POST['puede_autorizar'][$espacio]) ? 1 : 0;

        $sqlPermisos = "INSERT INTO permisos_espacios (id_usuario, id_espacio, puede_borrar, puede_autorizar)
                        VALUES ($id_usuario, $espacio, $puede_borrar, $puede_autorizar)";
        $conn->query($sqlPermisos);
    }

    $mensaje .= " y permisos actualizados correctamente.";
} else {
    // Si no hay espacios seleccionados, eliminar todos los permisos del usuario
    $sqlDelete = "DELETE FROM permisos_espacios WHERE id_usuario = $id_usuario";
    $conn->query($sqlDelete);
    $mensaje .= " y permisos eliminados correctamente.";
}
}


// Obtener roles para el select
$sqlRoles = "SELECT id, nombre FROM roles";
$resultadoRoles = $conn->query($sqlRoles);

if (!$resultadoRoles) {
    die("Error en la consulta de roles: " . $conn->error);
}

// Obtener espacios disponibles
$sqlEspacios = "SELECT id_espacio, nombre FROM espacios";
$resultadoEspacios = $conn->query($sqlEspacios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <style>

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: 30px auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h2 {
            text-align: center;
            color: #333;
            font-size: 24px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            text-align: center;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        form {
            display: flex;
            flex-direction: column;
            width: 100%;
            margin: 0 auto;
        }

        label {
            font-size: 16px;
            margin-bottom: 5px;
        }

        input, select, button {
            padding: 10px;
            margin: 8px 0 20px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        input[type="text"], select {
            width: 100%;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-btn {
            background-color: #28a745;
            color: white;
            padding: 12px;
            text-align: center;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            display: block;
            width: 200px;
            margin: 0 auto;
        }

        .back-btn:hover {
            background-color: #218838;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        input[type="checkbox"] {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #ccc;
            background-color: #fff;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        input[type="checkbox"]:checked {
            border-color: #007bff;
            background-color: #007bff;
        }

        input[type="checkbox"]:checked::before {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            width: 14px;
            height: 14px;
            background-color: #fff;
            border-radius: 50%;
            transform: scale(1);
            transition: transform 0.2s ease;
        }

        label {
            font-size: 16px;
            color: #333;
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-left: 10px;
        }

        .space-section {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .space-section h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .space-section label {
            margin-left: 0;
        }

        .space-section input {
            margin-right: 10px;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>Editar Usuario</h2>

    <?php if (isset($mensaje)): ?>
        <div class="<?php echo $mensaje_clase; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo $usuario['nombre']; ?>" required><br><br>

        <label for="rol_id">Rol:</label>
        <select id="rol_id" name="rol_id" required>
            <?php
            while ($row = $resultadoRoles->fetch_assoc()) {
                $selected = ($row['id'] == $usuario['rol_id']) ? 'selected' : '';
                echo "<option value='" . $row['id'] . "' $selected>" . $row['nombre'] . "</option>";
            }
            ?>
        </select><br><br>

        <h3>Asignar permisos para los espacios</h3>

        <?php
        while ($espacio = $resultadoEspacios->fetch_assoc()) {
            // Verificar si el usuario ya tiene permisos
            $sqlPermisosUsuario = "SELECT * FROM permisos_espacios WHERE id_usuario = $id_usuario AND id_espacio = " . $espacio['id_espacio'];
            $resultadoPermisos = $conn->query($sqlPermisosUsuario);
            $permiso = $resultadoPermisos->num_rows > 0 ? $resultadoPermisos->fetch_assoc() : null;
        ?>
            <div class="space-section">
                <label>
                    <input type="checkbox" name="espacios[]" value="<?php echo $espacio['id_espacio']; ?>"
                        <?php echo isset($permiso) ? 'checked' : ''; ?>>
                    <?php echo $espacio['nombre']; ?>
                </label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="puede_autorizar[<?php echo $espacio['id_espacio']; ?>]" value="1" 
                            <?php echo isset($permiso['puede_autorizar']) && $permiso['puede_autorizar'] ? 'checked' : ''; ?>>
                        Puede autorizar
                    </label>
                    <label>
                        <input type="checkbox" name="puede_borrar[<?php echo $espacio['id_espacio']; ?>]" value="1" 
                            <?php echo isset($permiso['puede_borrar']) && $permiso['puede_borrar'] ? 'checked' : ''; ?>>
                        Puede borrar
                    </label>
                </div>
            </div>
        <?php } ?>

        <button type="submit">Actualizar Usuario</button>
    </form>

    <button class="back-btn" onclick="window.location.href='administrar_usuarios.php'">Volver a la lista</button>
</div>

</body>
</html>

<?php
$conn->close();
?>
