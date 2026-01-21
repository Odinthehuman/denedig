<?php
date_default_timezone_set('America/Mexico_City'); // Establecer la zona horaria de CDMX
//scan_qr_orientador.php
$servername = "localhost";
$username = "u880452948_Conejo";
$password = "Jjn8econ[9";
$dbname = "u880452948_S_Escolar";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$qr_code = isset($_GET['qr_code']) ? trim($_GET['qr_code']) : '';
if (empty($qr_code)) {
    die("Código QR no proporcionado.");
}

$secretKey = 'your-secret-key';

function decryptId($encryptedId, $secretKey) {
    list($ciphertext, $iv) = explode('::', base64_decode($encryptedId), 2);
    $iv = base64_decode($iv);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $secretKey, 0, $iv);
}

$credential_id = decryptId($qr_code, $secretKey);
if ($credential_id === false || intval($credential_id) <= 0) {
    die("ID no válido.");
}

// Obtener fecha y hora desde PHP
$now = new DateTime();
$now_formatted = $now->format('Y-m-d H:i:s');
$now_hour = $now->format('H:i:s'); 
$today_date = $now->format('Y-m-d'); // Formato correcto para MySQL

// Consulta para obtener datos del estudiante (credencial)
$stmt = $conn->prepare("SELECT id_escuela, caducidad_credencial, estatus_credencial, nombre_credencial, grado_credencial, grupo_credencial, nivel_usuario
                        FROM credenciales 
                        WHERE id_credencial = ?");
$stmt->bind_param("i", $credential_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Registro no encontrado.");
}

$row = $result->fetch_assoc();
$id_escuela_estudiante = $row['id_escuela'];
$validity = $row['caducidad_credencial'];
$status = $row['estatus_credencial'];
$nombre_credencial = $row['nombre_credencial'];
$grado_credencial = $row['grado_credencial'];
$grupo_credencial = $row['grupo_credencial'];
$nivel_usuario = $row['nivel_usuario'];

if ($status !== 1 || $validity < $today_date) {
    die("El registro no está activo o la fecha ha pasado.");
}

// Verificar que el estudiante tiene nivel 7 y pertenece a la misma escuela que el orientador
session_start();
if (!isset($_SESSION['id_credencial']) || !isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 4) {
    die("Acceso denegado.");
}

// Obtener id_escuela del orientador desde la base de datos
$id_credencial_orientador = $_SESSION['id_credencial'];
$stmt = $conn->prepare("SELECT id_escuela FROM credenciales WHERE id_credencial = ?");
$stmt->bind_param("i", $id_credencial_orientador);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Orientador no encontrado.");
}

$row = $result->fetch_assoc();
$id_escuela_orientador = $row['id_escuela'];

if ($nivel_usuario != 7) {
    die("No eres alumno o no tienes permiso para registrar la entrada o salida.");
}

if ($id_escuela_estudiante != $id_escuela_orientador) {
    die("Este estudiante no pertenece a tu escuela.");
}

// Procedemos con la lógica de registro de entrada/salida
$stmt = $conn->prepare("SELECT id_entrada_salida_alumno, hora_entrada, hora_salida 
                        FROM entrada_salida_alumno 
                        WHERE id_credencial = ? AND fecha = ? 
                        ORDER BY id_entrada_salida_alumno DESC 
                        LIMIT 1");
$stmt->bind_param("is", $credential_id, $today_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO entrada_salida_alumno (id_credencial, id_escuela, fecha, hora_entrada) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $credential_id, $id_escuela_estudiante, $today_date, $now_formatted);
    $stmt->execute();
    
    $mensaje = "Entrada registrada: $nombre_credencial ($grado_credencial - $grupo_credencial) a las $now_hour el $today_date";
    $stmt = $conn->prepare("INSERT INTO notificaciones (id_estudiante, mensaje, estatus_notificacion, tipo, fecha) VALUES (?, ?, 0, 'Entrada', ?)");
    $stmt->bind_param("iss", $credential_id, $mensaje, $now_formatted);
    $stmt->execute();
    
    mostrarMensaje($mensaje, '#d4edda', '#155724', '#c3e6cb', 'entrada_salida.php');
    exit();
} else {
    $row = $result->fetch_assoc();
    $last_entrada_time = new DateTime($row['hora_entrada']);
    $current_time = new DateTime($now_formatted);
    $interval = $last_entrada_time->diff($current_time);

    if ($interval->i >= 1) {
        if (empty($row['hora_salida'])) {
            $stmt = $conn->prepare("UPDATE entrada_salida_alumno SET hora_salida = ? WHERE id_entrada_salida_alumno = ?");
            $stmt->bind_param("si", $now_formatted, $row['id_entrada_salida_alumno']);
            $stmt->execute();

            $mensaje = "Salida registrada: $nombre_credencial ($grado_credencial - $grupo_credencial) a las $now_hour el $today_date";
            $stmt = $conn->prepare("INSERT INTO notificaciones (id_estudiante, mensaje, estatus_notificacion, tipo, fecha) VALUES (?, ?, 0, 'Salida', ?)");
            $stmt->bind_param("iss", $credential_id, $mensaje, $now_formatted);
            $stmt->execute();

            mostrarMensaje($mensaje, '#d4edda', '#155724', '#c3e6cb', 'entrada_salida.php');
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO entrada_salida_alumno (id_credencial, id_escuela, fecha, hora_entrada) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $credential_id, $id_escuela_estudiante, $today_date, $now_formatted);
            $stmt->execute();
            
            $mensaje = "Entrada registrada: $nombre_credencial ($grado_credencial - $grupo_credencial) a las $now_hour el $today_date";
            $stmt = $conn->prepare("INSERT INTO notificaciones (id_estudiante, mensaje, estatus_notificacion, tipo, fecha) VALUES (?, ?, 0, 'Entrada', ?)");
            $stmt->bind_param("iss", $credential_id, $mensaje, $now_formatted);
            $stmt->execute();
            
            mostrarMensaje($mensaje, '#d4edda', '#155724', '#c3e6cb', 'entrada_salida.php');
            exit();
        }
    } else {
        mostrarMensaje('QR registrado recientemente', '#f8d7da', '#721c24', '#f5c6cb', 'entrada_salida.php');
        exit();
    }
}

function mostrarMensaje($mensaje, $bgColor, $textColor, $borderColor, $redirectUrl) {
    echo "<div style='padding: 20px; background-color: $bgColor; color: $textColor; border: 2px solid $borderColor; border-radius: 10px; font-size: 24px; font-weight: bold; text-align: center; margin: 40px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.2); width: 80%; max-width: 600px; margin-left: auto; margin-right: auto; position: relative; top: 20%;'>
        <strong>¡$mensaje!</strong>
    </div>";
    echo "<script>
            setTimeout(function() {
                window.location.href = '$redirectUrl';
            }, 500);
          </script>";
}

$conn->close();
?>
