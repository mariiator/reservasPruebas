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

// Obtener todos los usuarios y sus roles
$query = "SELECT u.id_usuario, u.nombre, r.nombre AS rol 
          FROM usuarios u 
          LEFT JOIN roles r ON u.rol_id = r.id";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios</title>
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

        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .btn-añadir-usuario {
            display: block;
            width: 200px;
            margin: 30px auto;
            background-color: #28a745;
            color: white;
            padding: 12px;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn-añadir-usuario:hover {
            background-color: #218838;
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

<a href="añadir_usuario.php" class="btn-añadir-usuario">Añadir Usuario</a>
<a href="index.php" class="btn-ir-al-inicio">Ir al Inicio</a>

<div class="container">
    <h1>Administrar Usuarios</h1>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($user = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($user['nombre']) . "</td>";

                        // Verificar si el rol es NULL o rol_id es 0
                        if (empty($user['rol']) || $user['rol'] == "0") {
                            echo "<td>usuario</td>";
                        } else {
                            echo "<td>" . htmlspecialchars($user['rol']) . "</td>";
                        }

                        echo "<td>
                                <a href='editar_usuario.php?id=" . $user['id_usuario'] . "'><button>Modificar</button></a>
                                <a href='eliminar_usuario.php?id=" . $user['id_usuario'] . "'><button>Eliminar</button></a>
                            </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No hay usuarios disponibles</td></tr>";
                }
            ?>

        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
