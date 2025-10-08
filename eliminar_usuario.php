<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


require __DIR__ . '/config_local.php';

// Si no hay sesión CAS, redirige a CAS
//COMENTADO POR MARIAphpCAS::forceAuthentication();

// Mitiga fijación de sesión tras autenticación
if (empty($_SESSION['session_hardened'])) {
  session_regenerate_id(true);
  $_SESSION['session_hardened'] = true;
}

// Verificar si se ha proporcionado un ID de usuario
if (isset($_GET['id']) && isset($_GET['confirmar']) && $_GET['confirmar'] == 'si') {
    $id_usuario = (int)$_GET['id'];

    // Eliminar el usuario de la base de datos
    $sqlDelete = "DELETE FROM usuarios WHERE id_usuario = $id_usuario";

    if ($conn->query($sqlDelete) === TRUE) {
        $mensaje = "Usuario eliminado correctamente.";
        $mensaje_clase = "message";
    } else {
        $mensaje = "Error al eliminar el usuario: " . $conn->error;
        $mensaje_clase = "message error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Usuario</title>
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

        h1 {
            text-align: center;
            color: #333;
            font-size: 24px;
        }

        .message {
            padding: 10px;
            background-color: #28a745;
            color: white;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message.error {
            background-color: #dc3545;
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

        .btn-volver {
            display: inline-block;
            padding: 12px 20px;
            background-color: #ffc107;
            color: white;
            font-size: 16px;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .btn-volver:hover {
            background-color: #e0a800;
        }

        .btn-eliminar {
            background-color: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .btn-eliminar:hover {
            background-color: #c82333;
        }
    </style>
    <script>
        function confirmarEliminacion(id) {
            var respuesta = confirm("¿Estás seguro de que quieres eliminar a este usuario?");
            if (respuesta) {
                window.location.href = "eliminar_usuario.php?id=" + id + "&confirmar=si";
            } else {
                window.location.href = "administrar_usuarios.php";
            }
        }
    </script>
</head>
<body>

<a href="index.php" class="btn-ir-al-inicio">Ir al Inicio</a>

<div class="container">
    <h1>Eliminar Usuario</h1>

    <?php if (isset($mensaje)): ?>
        <div class="<?php echo $mensaje_clase; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <a href="javascript:void(0);" class="btn-eliminar" onclick="confirmarEliminacion(<?php echo $_GET['id']; ?>)">Eliminar Usuario</a>
    <a href="administrar_usuarios.php" class="btn-volver">Volver a la administración de usuarios</a>
</div>

</body>
</html>
