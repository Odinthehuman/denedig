<?php

include '../funciones/conexQRConejo.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);





// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Mexico_City');

// Opcional: Configurar el conjunto de caracteres a utf8mb4 para manejar caracteres especiales
$conexion->set_charset("utf8mb4");

require_once 'phpqrcode/qrlib.php'; // Asegúrate de tener la biblioteca PHP QR Code en el directorio adecuado

// Función para encriptar un valor
function encryptValue($value, $secretKey) {
    $iv = random_bytes(16); // Vector de inicialización aleatorio
    $ciphertext = openssl_encrypt($value, 'aes-256-cbc', $secretKey, 0, $iv);
    return base64_encode($ciphertext . '::' . base64_encode($iv));
}

// Función para desencriptar un valor
function decryptValue($encryptedValue, $secretKey) {
    list($ciphertext, $iv) = explode('::', base64_decode($encryptedValue), 2);
    $iv = base64_decode($iv);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $secretKey, 0, $iv);
}

// Definir una clave secreta para la encriptación
$secretKey = 'your-secret-key'; // Usa una clave secreta segura

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8') : '';
    $apellido = isset($_POST['apellido']) ? htmlspecialchars(trim($_POST['apellido']), ENT_QUOTES, 'UTF-8') : '';
    $genero = isset($_POST['genero']) ? htmlspecialchars(trim($_POST['genero']), ENT_QUOTES, 'UTF-8') : '';
    $grado_credencial = isset($_POST['grado_credencial']) ? htmlspecialchars(trim($_POST['grado_credencial']), ENT_QUOTES, 'UTF-8') : '';
    $curp = isset($_POST['curp']) ? htmlspecialchars(trim($_POST['curp']), ENT_QUOTES, 'UTF-8') : '';
    $fechaNacimiento = isset($_POST['fecha_nacimiento']) ? htmlspecialchars(trim($_POST['fecha_nacimiento']), ENT_QUOTES, 'UTF-8') : '';
    $fechaAntiguedad = isset($_POST['fecha_antiguedad']) ? htmlspecialchars(trim($_POST['fecha_antiguedad']), ENT_QUOTES, 'UTF-8') : '';
    $sschool = isset($_POST['sschool']) ? htmlspecialchars(trim($_POST['sschool']), ENT_QUOTES, 'UTF-8') : '';
    $validity = isset($_POST['validity']) ? htmlspecialchars(trim($_POST['validity']), ENT_QUOTES, 'UTF-8') : '';
    $id_zona_escolar = isset($_POST['id_zona_escolar']) ? htmlspecialchars(trim($_POST['id_zona_escolar']), ENT_QUOTES, 'UTF-8') : '';
    $id_escuela = isset($_POST['id_escuela']) ? htmlspecialchars(trim($_POST['id_escuela']), ENT_QUOTES, 'UTF-8') : '';

    // Verificar si la escuela existe
    $checkSchoolStmt = $conexion->prepare("SELECT COUNT(*) FROM escuelas WHERE id_escuela = ?");
    $checkSchoolStmt->bind_param("i", $sschool);
    $checkSchoolStmt->execute();
    $checkSchoolStmt->bind_result($count);
    $checkSchoolStmt->fetch();
    $checkSchoolStmt->close();

    if ($count == 0) {
        $sschool = 0;
    }

    // Encriptar campos sensibles
    $encryptedApellido = encryptValue($apellido, $secretKey);
    $encryptedToken = encryptValue('9999', $secretKey);
    $encryptedCurp = encryptValue($curp, $secretKey);

    // Asignar otros valores
    $nivel_usuario = 4;
    $id_chat = '';
    $caducidad_credencial = $validity;
    $estatus_credencial = 1;
    $status = 'activo';

    // Preparar declaración SQL de inserción
    $stmt = $conexion->prepare("INSERT INTO credenciales (nombre_credencial, apellidos_credencial, nivel_usuario, grado_credencial, fecha_antiguedad, fecha_nacimiento, curp_credencial, id_escuela, id_chat, token_verificacion, caducidad_credencial, estatus_credencial, validity, status, genero, id_zona_escolar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }

    // Asociar parámetros con bind_param
    $stmt->bind_param("ssisssssissssssi", $name, $encryptedApellido, $nivel_usuario, $grado_credencial, $fechaAntiguedad, $fechaNacimiento, $encryptedCurp, $id_escuela, $id_chat, $encryptedToken, $caducidad_credencial, $estatus_credencial, $validity, $status, $genero, $id_zona_escolar);

    // Ejecutar la consulta
   if ($stmt->execute()) {
        $credencialId = $stmt->insert_id;

        // Generar QR
        $qrData = encryptValue($credencialId, $secretKey);
        $qrDirFullPath = '../QR/';
        $qrFileName = 'qrcodes/' . $credencialId . '.png';
        $qrFileFullPath = $qrDirFullPath . $qrFileName;

        if (!file_exists($qrDirFullPath)) {
            mkdir($qrDirFullPath, 0777, true);
        }

        QRcode::png($qrData, $qrFileFullPath, QR_ECLEVEL_L, 10);

        // Registrar QR en la base de datos
        $horaActual = new DateTime();
        $horaActual->modify('-1 hour');
        $horaActualFormatted = $horaActual->format('Y-m-d H:i:s');

        $stmtQR = $conexion->prepare("INSERT INTO qr_codes (credential_id, file_path, created_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), created_at = VALUES(created_at)");
        $stmtQR->bind_param("iss", $credencialId, $qrFileName, $horaActualFormatted);

        if ($stmtQR->execute()) {
        // Mensaje de éxito con ícono "1"
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';
        echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">';
        echo '<style>
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh; /* Altura mínima del viewport */
    margin: 0;
}

/* Contenedor principal de contenido */
.container {
    flex: 1; /* Este contenedor toma el espacio disponible */
}

/* Footer en la parte inferior */
footer {
    background-color: #142E61; /* Ajusta al color deseado */
    color: white;
    text-align: center;
    padding: 1rem 0;
}
    .container {
        padding: 40px;
        border-radius: 12px;
        background-color: #ffffff;
        color: #004d99;
        text-align: center;
        font-size: 1.6rem;
        font-weight: bold;
        margin-top: 30px;
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        transform: translateY(50px);
        animation: slideUp 0.6s ease-out forwards;
    }
    .container h3 {
        font-size: 1.8rem;
        margin-bottom: 10px;
        color: #333;
        font-weight: 600;
    }
    .container p {
        font-size: 1.2rem;
        color: #555;
        margin: 15px 0;
        font-weight: 400;
    }
    .icon {
        display: inline-block;
        margin-bottom: 20px;
        color: #00bfae;
        animation: pulse 1.5s infinite;
    }
    .icon i {
        font-size: 4rem;
    }
    .btn {
        background: linear-gradient(135deg, #00bfae, #007bff);
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 50px;
        font-size: 1.2rem;
        cursor: pointer;
        margin-top: 20px;
        transition: all 0.4s ease-in-out;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .btn:hover {
        background: linear-gradient(135deg, #007bff, #00bfae);
        transform: translateY(-5px);
        box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
    }
    .btn:active {
        background: linear-gradient(135deg, #0056b3, #008e8e);
        transform: translateY(2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .btn:focus {
        outline: none;
    }
    a {
        color: #007bff;
        text-decoration: none;
        font-weight: bold;
        font-size: 1.3rem;
        display: inline-block;
        margin: 10px 0;
        transition: color 0.3s ease;
    }
    a:hover {
        color: #0056b3;
    }
    @keyframes slideUp {
        0% { transform: translateY(50px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
</style>';

echo '<div class="container">';
    

        // Ícono con el número 1 (se puede añadir un ícono aquí si lo necesitas)
        echo '<div class="icon">';
        echo '<i class="fa fa-check-circle"></i>'; // Icono de éxito
        echo '</div>';
    
        echo '<h3>Registro creado correctamente y QR generado.</h3>';
        echo '<p>Ahora puedes descargar el QR o ir a la página para más acciones.</p>';
    
        // Enlace para ver el QR

        // Botón para descargar el QR
        echo '<a href="' . $qrFileFullPath . '" download="qr_code_' . $credencialId . '.png">';
        echo '<button class="btn">Descargar QR</button>';
        echo '</a><br>';
    
        // Enlace para redirigir a la página

        // Mensaje adicional
        echo '<p>¡Si ya guardaste tu QR!</p>';
        echo '<p>Serás redirigido a la página donde lo puedes escanear.</p>';
        echo '</div>';

        } else {
            echo "Error al registrar el código QR: " . $stmtQR->error;
        }

        $stmtQR->close();
    } else {
        echo "Error al registrar la credencial: " . $stmt->error;
    }

    $stmt->close();
}

$conexion->close();
?>
              


