<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//Puesto por MARIA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
require __DIR__ . '/config.php';
require __DIR__ . '/config_local.php';

// Si no hay sesión CAS, redirige a CAS
//COMENTADO POR MARIAphpCAS::forceAuthentication();

// Mitiga fijación de sesión tras autenticación
if (empty($_SESSION['session_hardened'])) {
  session_regenerate_id(true);
  $_SESSION['session_hardened'] = true;
}

// Usuario autenticado
/*COMENTADO POR MARIA
$casuser      = phpCAS::getUser();        // normalmente el uid
$attrs     = phpCAS::getAttributes();  // atributos extra (mail, displayName, etc.)
*/

//COMENTADO POR MARIA$usuario = phpCAS::getUser();
// PARA PRUEBAS MARIA
$usuario = 'usuario_local';

// Verificar si el usuario ya esta en la base de datos y obtener rol y permisos
$sql = "
    SELECT usuarios.rol_id 
    FROM usuarios
    WHERE usuarios.nombre = '$usuario'";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$id_rol = 0;

if ($row = $result->fetch_assoc()) {
    $id_rol = $row['rol_id'];
} else {
    // Si el usuario no existe, insertarlo en la base de datos
    $fecha_registro = date('Y-m-d H:i:s');
    $sql_insert = "INSERT INTO usuarios (nombre, fecha_registro) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ss", $usuario, $fecha_registro);
    $stmt_insert->execute();
    $stmt_insert->close();
}

$stmt->close();

$sql = "
    SELECT usuarios.rol_id, 
           MAX(permisos_espacios.puede_autorizar) AS puede_autorizar, 
           MAX(permisos_espacios.puede_borrar) AS puede_borrar
    FROM usuarios
    INNER JOIN permisos_espacios ON permisos_espacios.id_usuario = usuarios.id_usuario
    WHERE usuarios.nombre = '$usuario'";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$permiso_autorizar = false;
$permiso_borrar = false;

if ($row = $result->fetch_assoc()) {
    $permiso_autorizar = isset($row['puede_autorizar']) && $row['puede_autorizar'] == 1;
    $permiso_borrar = isset($row['puede_borrar']) && $row['puede_borrar'] == 1;
} else {
    // Si el usuario no existe, insertarlo en la base de datos
    $fecha_registro = date('Y-m-d H:i:s');
    $sql_insert = "INSERT INTO usuarios (nombre, fecha_registro) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ss", $usuario, $fecha_registro);
    $stmt_insert->execute();
    $stmt_insert->close();
}

$stmt->close();



// Obtener colores de los espacios
$sql_colores = "SELECT nombre, color, id_espacio FROM espacios";
$result_colores = $conn->query($sql_colores);

$espacios_colores = [];
while ($row = $result_colores->fetch_assoc()) {
    $espacios_colores[] = $row;
}

$conn->close();

$hora_actual = date('H');
if ($hora_actual < 12) {
    $saludo = "Buenos días";
} elseif ($hora_actual < 18) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Calendario de Eventos</title>
  <script src="./dist/index.global.js"></script>
  <head>
	<script src="jquery-3.7.1.min.js"></script>
  </head>
  <script>
    
	$(document).ready(function(){

	   
	  var calendarEl = document.getElementById('calendar');
  
      var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
		height: 800, 
        initialView: 'timeGridWeek',
		slotMinTime: "8:00",
		firstDay: 1,
        nowIndicator: true,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        navLinks: true,
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
		eventClassNames: function(arg) {
		   my_class = arg.event.espacio;
		   if (arg.view.type != 'resourceTimeGridDay') {
			  if (arg.event.espacio == "Patio Columnas") {
				 my_class = "";
			  }
		   }
		   return my_class;
		},
        events: function(info, successCallback, failureCallback) {
		  var ids = "calendario.php?id=0";
		  var cs = document.querySelectorAll(".cs");
		  cs.forEach(function (v) {
			if (v.checked) {
				ids = ids.concat(",", v.value);
			}
		  });
          fetch(ids)
            .then(response => {
              if (!response.ok) {
                throw new Error('Error al obtener los datos del servidor.');
              }
              return response.json();
            })
            .then(data => {
              successCallback(data);
            })
            .catch(error => {
              console.error('Error cargando eventos:', error);
              failureCallback(error);
            });
        },
		eventDidMount: function (arg) {
			var cs = document.querySelectorAll(".cs");
			cs.forEach(function (v) {
				if (arg.event.extendedProps.id_espacio == v.value) {
					if (v.checked) {
						arg.el.style.display = "block";
					} else {
						arg.el.style.display = "none";
					}		
				}
				
			});
		}
      });
	  
	  
      calendar.render();
	  
	  var csx = document.querySelectorAll(".cs");
	  csx.forEach(function (el) {
		
		el.addEventListener("change", function () {
			calendar.refetchEvents();
		});
		el.checked=true;
	  });
	  
	  calendar.refetchEvents();
	
	});
	  
  </script>
  
  <style>
    body {
      margin: 0;
      font-family: 'Arial', Helvetica, sans-serif;
      background-color: #f5f5f5;
      color: #333;
    }

    h1 {
      color: #007bff; 
      font-size: 2.5em; 
      font-weight: bold; 
      text-align: center; 
      text-transform: uppercase; 
      margin-top: 20px;
      margin-bottom: 10px; 
      letter-spacing: 2px;
      background: linear-gradient(to right, #007bff, #0056b3);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent; 
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .welcome p {
      color: #444;
      font-size: 1.2em; 
      font-weight: 500;
      text-align: center;
      background-color: #e9f7ef;
      padding: 15px;
      margin: 20px auto; 
      border: 1px solid #28a745; 
      border-radius: 10px;
      max-width: 600px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .usuario-autenticado {
      color: #28a745;
      font-weight: bold;
      font-size: 1.1em;
    }

    .calendar-container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    #calendar {
      margin: 0 auto;
    }

    .admin-container {
      position: absolute;
      top: 0px;
      right: 5px;
      display: flex;
      gap: 10px;
      z-index: 1000;
    }

    .admin-button {
      padding: 10px 14px; 
      font-size: 14px; 
      font-weight: bold;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); 
    }

    .admin-button.reservar {
      background-color: #007bff;
    }

    .admin-button.salas {
      background-color: #28a745;
    }

    .admin-button.autorizar {
      background-color: #ffc107;
      color: #333;
    }

    .admin-button.usuarios {
      background-color: #dc3545;
    }

    .admin-button.borrar {
      background-color: #6c757d;
      color: #fff;
    }

    .admin-button.borrar:hover {
      background-color: #495057;
      transform: scale(1.05);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .admin-button:hover {
      opacity: 0.9;
      transform: scale(1.05);
    }

    .admin-button.reservar:hover {
      background-color: #0056b3;
    }

    .admin-button.salas:hover {
      background-color: #218838;
    }

    .admin-button.autorizar:hover {
      background-color: #e0a800;
    }

    .admin-button.usuarios:hover {
      background-color: #c82333;
    }

    .legend {
      position: fixed;
      top: 5px;
      left: 10px;
      display: block;
      gap: 15px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.9)); /* Fondo degradado */
      padding: 15px;
      border-radius: 12px; 
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); 
      z-index: 1000;
      transition: all 0.3s ease; 
    }

    .legend-item {
      display: flex;
      margin-bottom: 10px;
      font-size: 1.1em;
      color: #333;
      transition: transform 0.3s ease-in-out, color 0.3s ease-in-out;
    }
	

    .legend-color {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 2px solid #fff; 
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
	.fc-event.hidden {
		display: none;
	}

    
    span{
      margin-top: 5px;
    }

  </style>
</head>
<body>
<div class="legend">

    <?php foreach ($espacios_colores as $espacio): ?>
		<div class="legend-item"> <input class="cs" style="accent-color: <?php echo htmlspecialchars($espacio['color']); ?>" value="<?php echo $espacio['id_espacio'] ?>" type="checkbox" ><span style="color: <?php echo htmlspecialchars($espacio['color']); ?>"><?php echo htmlspecialchars($espacio['nombre']); ?></span>
		</div>
    
	<?php endforeach; ?>
</div>
<br><br>
<div class="container">
    <h1>Reservas Confirmadas</h1>
    <div class="welcome">
        <p>
            <?php echo $saludo . ", <span class='usuario-autenticado'>" . htmlspecialchars($usuario) . "</span>. ¡Bienvenido/a al sistema de reservas!"; ?>
        </p>
      </div>

      <div class="admin-container">
        <button type="button" class="admin-button reservar" onclick="window.location.href='reservar_sala.php'">Reservar Salas</button>	
        <!-- Botones segun el rol y permisos -->
        <?php if ($id_rol == 1): ?>
            <button type="button" class="admin-button salas" onclick="window.location.href='administrar_salas.php'">Administrar Salas</button>
            <button type="button" class="admin-button autorizar" onclick="window.location.href='autorizar_reservas.php'">Autorizar Reservas</button>
            <button type="button" class="admin-button usuarios" onclick="window.location.href='administrar_usuarios.php'">Administrar Usuarios</button>
            <button type="button" class="admin-button borrar" onclick="window.location.href='borrar_reserva.php'">Borrar Reserva</button>
        <?php else: ?>
            <?php if ($permiso_autorizar): ?>
                <button type="button" class="admin-button autorizar" onclick="window.location.href='autorizar_reservas.php'">Autorizar Reservas</button>
            <?php endif; ?>
            <?php if ($permiso_borrar): ?>
                <button type="button" class="admin-button borrar" onclick="window.location.href='borrar_reserva.php'">Borrar Reserva</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="calendar-container">
        <div id="calendar"></div>
    </div>
</div>
</body>
</html>
