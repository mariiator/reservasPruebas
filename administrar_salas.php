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

// Obtener todas las salas y su información
$query = "SELECT id_espacio, nombre, color FROM espacios";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Salas</title>
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
            text-align: center;
        }

        th {
            background-color: #f8f9fa;
            text-align: center;
        }

        a {
            text-decoration: none;
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

        .btn-añadir-sala {
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

        .btn-añadir-sala:hover {
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

        .btn-accion {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            display: inline-block;
            cursor: pointer;
        }

        .btn-accion:hover {
            background-color: #0056b3;
        }

        .btn-eliminar {
            background-color: #dc3545;
        }

        .btn-eliminar:hover {
            background-color: #c82333;
        }

        .color-picker{
            width: 100%;
            max-width: 50px;
            height: 30px;
            border: none;
            cursor: pointer;
        }

        .btn-guardar-color {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            display: inline-block;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-guardar-color:hover {
            background-color: #218838;
        }

    </style>
</head>
<body>

<a href="añadir_salas.php" class="btn-añadir-sala">Añadir Salas</a>
<a href="index.php" class="btn-ir-al-inicio">Ir al Inicio</a>

    <div class="container">
        <h1>Administrar Salas</h1>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Color Actual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td>
                        <input type="color" class="color-picker" data-id="<?php echo $row['id_espacio']; ?>" value="<?php echo $row['color'] ?: '#ffffff'; ?>">
                    </td>
                    <td>
                        <a href="modificar_sala.php?id=<?php echo $row['id_espacio']; ?>">
                            <button class="btn-accion">Modificar</button>
                        </a>
                        <a href="eliminar_sala.php?id=<?php echo $row['id_espacio']; ?>">
                            <button class="btn-accion btn-eliminar">Eliminar</button>
                        </a>
                        <button class="btn-accion btn-guardar-color" data-id="<?php echo $row['id_espacio']; ?>">Guardar Color</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No hay salas disponibles</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const saveButtons = document.querySelectorAll(".btn-guardar-color");

        saveButtons.forEach((button) => {
            button.addEventListener("click", function () {
                const espacioId = this.getAttribute("data-id");
                const colorPicker = document.querySelector(`.color-picker[data-id='${espacioId}']`);
                const color = colorPicker.value;

                // Enviar el color al servidor
                fetch("guardar_color.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ id_espacio: espacioId, color: color }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            alert("Color guardado correctamente.");
                        } else {
                            alert("Error al guardar el color.");
                        }
                    })
                    .catch((error) => console.error("Error:", error));
            });
        });
    });
</script>
</body>
</html>
