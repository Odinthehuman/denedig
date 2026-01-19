<?php    
// Configuración de la base de datos
include '../funciones/conexQRConejo.php';

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Iniciar la sesión
session_start();

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Mexico_City');

// Obtener el código QR y token de la solicitud POST
$qr_code = isset($_POST['qr_code']) ? trim($_POST['qr_code']) : '';
$token = isset($_POST['token']) ? trim($_POST['token']) : '';

if (empty($qr_code) || empty($token)) {
    die("Código QR o token no proporcionado.");
}

// Definir la clave secreta
$secretKey = 'your-secret-key';

// Función para desencriptar el ID del usuario
function decryptId($encryptedId, $secretKey) {
    list($ciphertext, $iv) = explode('::', base64_decode($encryptedId), 2);
    $iv = base64_decode($iv);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $secretKey, 0, $iv);
}

// Desencriptar el ID del usuario del código QR
$credential_id = decryptId($qr_code, $secretKey);

// Verificar que el ID es válido
if ($credential_id === false || intval($credential_id) <= 0) {
    die("ID no válido.");
}

// Consultar la base de datos para obtener el nivel de usuario
$stmt = $conn->prepare("SELECT nivel_usuario, id_escuela, nombre_credencial, acceso_computo FROM credenciales WHERE id_credencial = ?");
$stmt->bind_param("i", $credential_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Registro no encontrado.");
}

$row = $result->fetch_assoc();
$nivel_usuario = $row['nivel_usuario'];
$id_escuela = $row['id_escuela'];
$nombre_credencial = $row['nombre_credencial'];
$acceso_computo = $row['acceso_computo'];




// Almacenar el `credential_id` y el `nivel_usuario` en la sesión
$_SESSION['credential_id'] = $credential_id;
$_SESSION['id_credencial'] = $credential_id;
$_SESSION['nivel_usuario'] = $nivel_usuario;

// Almacenar el credential_id y el nivel_usuario en la sesión EXTRA 'acceso_computo'
$_SESSION['credential_id'] = $credential_id;
$_SESSION['id_credencial'] = $credential_id;
$_SESSION['nivel_usuario'] = $nivel_usuario;
$_SESSION['id_escuela'] = $id_escuela; // <--- Almacenamos el id_escuela en la sesion
$_SESSION['nombre_credencial'] = $nombre_credencial; // <--- Almacenamos el nombre del perfil para Sistema Computo
$_SESSION['acceso_computo'] = $acceso_computo; // <-- Almacenamos el acceso_computo para Sistema Computo

// Consultar el estado del registro y verificar el token
$stmt = $conn->prepare("SELECT nombre_credencial, apellidos_credencial, token_verificacion FROM credenciales WHERE id_credencial = ?");
$stmt->bind_param("i", $credential_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Registro no encontrado.");
}

$row = $result->fetch_assoc();
$user_name = $row['nombre_credencial'];
$encryptedToken = $row['token_verificacion'];
$last_name = $row['apellidos_credencial'];

// Desencriptar el token almacenado
$decryptedToken = decryptId($encryptedToken, $secretKey);
$descryptlastname = decryptId($last_name, $secretKey);

// Verificar si el token ingresado coincide con el token almacenado
if ($decryptedToken !== $token) {
    header('Location: ../not_found.php');
    exit();
}

// Redirigir según el nivel_usuario
switch ($nivel_usuario) {
    case 1:
        header('Location: ../acceso_super_admin/index_super_admin.php');
        break;
    case 2:
        header('Location: ../acceso_supervisor/index_supervisor.php');
        break;
    case 3:
        header('Location: ../acceso_director/index_director.php');
        break;
    case 4:
        header('Location: ../acceso_orientador/index_orientador.php');
        break;
    case 5:
        header('Location: ../acceso_secretario/index_secretario.php');
        break;
    case 6:
        header('Location: ../acceso_maestro/index_maestro.php');
        break;
    case 7:
        header('Location: ../acceso_alumno/index_alumno.php');
        break;
    case 8:
        header('Location: ../acceso_familiares/inicio.php');
        break;
    case 9:
        header('Location: ../acceso_visitante/index_visitante.php');
        break;
    case 10:
        header('Location: ../acceso_checador/index_checador.php');
        break;
    case 11:
        header('Location: ../acceso_subdirector/index_subdirector.php');
        break;
    case 12:
        header('Location: ../acceso_bibliotecario/index_bibliotecario.php');
        break;
    case 13:
        header('Location: ../acceso_intendencia/index_intendencia.php');
        break;
    case 14:
        header('Location: ../acceso_mantenimiento/index_mantenimiento.php');
        break;
    case 15:
        header('Location: ../acceso_auxiliardirectivo/index_auxiliardirectivo.php');
        break;
    case 16:
        header('Location: ../acceso_controlescolar/index_controlescolar.php');
        break;
    case 17:
        header('Location: ../acceso_auxiliarsubdirector/index_auxiliarsubdirector.php');
        break;
    case 18:
        header('Location: ../acceso_auxiliarsecretario/index_auxiliarsecretario.php');
        break;
        default:
        die("Usuario no válido.");
}

$conn->close();
exit();

// Obtener la hora actual y restar una hora
$now = new DateTime();
$now->sub(new DateInterval('PT1H')); // Restar 1 hora
$now_formatted = $now->format('Y-m-d H:i:s');
$now_hour = $now->format('H:i:s'); // Solo la hora y minutos

// Consultar el estado del registro y verificar el token
$stmt = $conn->prepare("SELECT nombre_credencial, caducidad_credencial, estatus_credencial, token_verificacion FROM credenciales WHERE id_credencial = ?");
$stmt->bind_param("i", $credential_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Registro no encontrado.");
}

$row = $result->fetch_assoc();
$name = $row['nombre_credencial'];
$validity = $row['caducidad_credencial'];
$status = $row['estatus_credencial'];
$encryptedToken = $row['token_verificacion'];

// Desencriptar el token almacenado
$decryptedToken = decryptId($encryptedToken, $secretKey);

// Verificar si el token ingresado coincide con el token almacenado
if ($decryptedToken !== $token) {
    die("Token incorrecto.");
}

// Verificar si la validez y el estado permiten registrar
if ($status !== 1 || $validity < date('d-m-Y')) {
    die("El registro no está activo o la fecha ha pasado.");
}

// Consultar si ya existe un registro de entrada para hoy
$stmt = $conn->prepare("SELECT id, hora_entrada, hora_salida FROM registros WHERE credential_id = ? AND fecha = CURDATE()");
$stmt->bind_param("i", $credential_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No hay registro para hoy, se debe crear uno
    $stmt = $conn->prepare("INSERT INTO registros (credential_id, fecha, hora_entrada) VALUES (?, CURDATE(), ?)");
    $stmt->bind_param("is", $credential_id, $now_formatted);
    $stmt->execute();

    // Mostrar mensaje de éxito para la entrada
    echo '<div style="
        padding: 20px; 
        background-color: #d4edda; 
        color: #155724; 
        border: 2px solid #c3e6cb; 
        border-radius: 10px; 
        font-size: 24px; 
        font-weight: bold; 
        text-align: center; 
        margin: 40px 0; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
        width: 80%; 
        max-width: 600px; 
        margin-left: auto; 
        margin-right: auto;
        position: relative; 
        top: 20%;
        background-image: linear-gradient(to right, #d4edda, #c3e6cb);">
    <strong>¡Entrada registrada correctamente!</strong>
    </div>';

    // Enviar notificación a Telegram
    sendTelegramNotification($name, $now_hour, 'entrada');

    echo '<script>
        setTimeout(function() {
            window.location.href = "entrada-salida.html";
        }, 5000);
    </script>';

    exit();

} else {
    // Ya hay un registro para hoy
    $row = $result->fetch_assoc();

    if (empty($row['hora_salida'])) {
        // Verificar el intervalo entre la entrada y la hora actual
        $last_entrada_time = new DateTime($row['hora_entrada']);
        $current_time = new DateTime($now_formatted);
        $interval = $last_entrada_time->diff($current_time);

        if ($interval->i >= 3) {
            // Registrar la salida si han pasado más de 3 minutos
            $stmt = $conn->prepare("UPDATE registros SET hora_salida = ? WHERE credential_id = ? AND fecha = CURDATE()");
            $stmt->bind_param("si", $now_formatted, $credential_id);
            $stmt->execute();

            // Mostrar mensaje de éxito para la salida
            echo '<div style="
                padding: 20px; 
                background-color: #d4edda; 
                color: #155724; 
                border: 2px solid #c3e6cb; 
                border-radius: 10px; 
                font-size: 24px; 
                font-weight: bold; 
                text-align: center; 
                margin: 40px 0; 
                box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
                width: 80%; 
                max-width: 600px; 
                margin-left: auto; 
                margin-right: auto;
                position: relative; 
                top: 20%;
                background-image: linear-gradient(to right, #d4edda, #c3e6cb);
            ">
            <strong>¡Salida registrada correctamente!</strong>
            </div>';

            // Enviar notificación a Telegram
            sendTelegramNotification($name, $now_hour, 'salida');

            echo '<script>
                    setTimeout(function() {
                        window.location.href = "entrada-salida.html";
                    }, 5000);
                  </script>';
            exit();
        } else {
            // Mostrar mensaje de espera para registrar salida
            echo '<div style="
                padding: 20px; 
                background-color: #fff3cd; 
                color: #856404; 
                border: 2px solid #ffeeba; 
                border-radius: 10px; 
                font-size: 24px; 
                font-weight: bold; 
                text-align: center; 
                margin: 40px 0; 
                box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
                width: 80%; 
                max-width: 600px; 
                margin-left: auto; 
                margin-right: auto;
                position: relative; 
                top: 20%;
                background-image: linear-gradient(to right, #fff3cd, #ffeeba);
            ">
            <strong>Esperando a registrar la salida. Por favor, espere un poco más.</strong>
            </div>';
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "entrada-salida.html";
                    }, 5000);
                  </script>';
            exit();
        }
    } else {
        // Mostrar mensaje de que la salida ya fue registrada
        echo '<div style="
            padding: 20px; 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 2px solid #f5c6cb; 
            border-radius: 10px; 
            font-size: 24px; 
            font-weight: bold; 
            text-align: center; 
            margin: 40px 0; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
            width: 80%;
            max-width: 600px; 
            margin-left: auto; 
            margin-right: auto;
            position: relative; 
            top: 20%;
            background-image: linear-gradient(to right, #f8d7da, #f5c6cb);
        ">
        <strong>La salida ya ha sido registrada para hoy.</strong>
        </div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "entrada-salida.html";
                }, 5000);
              </script>';
        exit();
    }
}

// Función para enviar notificaciones a Telegram
function sendTelegramNotification($name, $time, $type) {
    $botToken = 'your-telegram-bot-token';
    $chatId = 'your-chat-id';
    $message = ($type == 'entrada') ? "Entrada registrada para $name a las $time" : "Salida registrada para $name a las $time";
    $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($message);
    file_get_contents($url);
}

// Cerrar la conexión
$conn->close();
?>
