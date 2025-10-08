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

if (isset($_GET['id'])) {
    $id_espacio = $_GET['id'];

    // Obtener los datos actuales de la sala
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger el nuevo nombre de la sala
    $nombre_sala = mysqli_real_escape_string($conn, $_POST['nombre_sala']);

    // Actualizar la sala en la base de datos
    $update_query = "UPDATE espacios SET nombre = '$nombre_sala' WHERE id_espacio = '$id_espacio'";
    if (mysqli_query($conn, $update_query)) {
        echo "<script>alert('Sala modificada correctamente'); window.location.href = 'administrar_salas.php';</script>";
    } else {
        echo "Error: " . $update_query . "<br>" . mysqli_error($conn);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Sala</title>
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
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-size: 16px;
            color: #555;
            display: block;
            margin-bottom: 8px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .btn-ir-al-inicio {
            display: block;
            width: 200px;
            margin: 20px auto;
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 12px;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn-ir-al-inicio:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<a href="administrar_salas.php" class="btn-ir-al-inicio">Volver a Administrar Salas</a>

<div class="container">
    <h1>Modificar Sala</h1>
    <form method="POST" action="modificar_sala.php?id=<?php echo $id_espacio; ?>">
        <div class="form-group">
            <label for="nombre_sala">Nombre de la Sala:</label>
            <input type="text" id="nombre_sala" name="nombre_sala" value="<?php echo htmlspecialchars($nombre_sala); ?>" required>
        </div>
        <button type="submit">Modificar Sala</button>
    </form>
</div>

</body>
</html>
