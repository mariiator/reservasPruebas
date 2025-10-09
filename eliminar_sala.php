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

if (isset($_GET['id'])) {
    $id_espacio = $_GET['id'];

    // Obtener los datos de la sala a eliminar
    $query = "SELECT nombre FROM espacios WHERE id_espacio = '$id_espacio'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $nombre_sala = $row['nombre'];
    } else {
        echo "Sala no encontrada.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    // Eliminar la sala de la base de datos
    $delete_query = "DELETE FROM espacios WHERE id_espacio = '$id_espacio'";
    if (mysqli_query($conn, $delete_query)) {
        echo "<script>alert('Sala eliminada correctamente'); window.location.href = 'administrar_salas.php';</script>";
    } else {
        echo "Error: " . $delete_query . "<br>" . mysqli_error($conn);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Sala</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 50%;
            margin: 30px auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }

        h1 {
            color: #333;
        }

        .btn-group {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-eliminar, .btn-cancelar {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 150px;
        }

        .btn-eliminar {
            background-color: #dc3545;
            color: white;
        }

        .btn-eliminar:hover {
            background-color: #c82333;
        }

        .btn-cancelar {
            background-color: #007bff;
            color: white;
            text-decoration: none;
        }

        .btn-cancelar:hover {
            background-color: #0056b3;
        }

        .btn-cancelar:active {
            background-color: #004085;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>¿Estás seguro de que deseas eliminar la sala "<?php echo htmlspecialchars($nombre_sala); ?>"?</h1>
    
    <div class="btn-group">
        <form method="POST">
            <button type="submit" name="confirmar" class="btn-eliminar">Eliminar Sala</button>
        </form>
        <a href="administrar_salas.php" class="btn-cancelar">Cancelar</a>
    </div>
</div>

</body>
</html>
