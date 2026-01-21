<?php
//boleta_alumnos.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../funciones/conexQRConejo.php';
require_once '../QR/phpqrcode/qrlib.php';

session_start();

if (!isset($_SESSION['id_credencial'])) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_credencial'];
date_default_timezone_set('America/Mexico_City');


// Función para convertir letra de grupo a número romano
function grupoToRomano($letra) {
    $letra = strtoupper($letra);
    $grupos = [
        'A' => 'I',
        'B' => 'II',
        'C' => 'III',
        'D' => 'IV',
        'E' => 'V',
        'F' => 'VI',
        'G' => 'VII',
        'H' => 'VIII',
        'I' => 'IX',
        'J' => 'X'
    ];
    
    return $grupos[$letra] ?? $letra; // Si no está en el rango A-J, devuelve la letra original
}

// Obtener el id_escuela del usuario
$query_escuela = "SELECT id_escuela FROM credenciales WHERE id_credencial = '$id_usuario'";
$result_escuela = mysqli_query($conexion, $query_escuela);

if ($result_escuela && mysqli_num_rows($result_escuela) > 0) {
    $row_escuela = mysqli_fetch_assoc($result_escuela);
    $id_escuela = $row_escuela['id_escuela'];
} else {
    die("Error al obtener el id_escuela: " . mysqli_error($conexion));
}

// Obtener el nombre de la escuela
$query_nombre_escuela = "SELECT nombre_escuela FROM escuelas WHERE id_escuela = '$id_escuela'";
$result_nombre_escuela = mysqli_query($conexion, $query_nombre_escuela);

if ($result_nombre_escuela && mysqli_num_rows($result_nombre_escuela) > 0) {
    $row_nombre_escuela = mysqli_fetch_assoc($result_nombre_escuela);
    $nombre_escuela = $row_nombre_escuela['nombre_escuela'];
} else {
    die("Error al obtener el nombre de la escuela: " . mysqli_error($conexion));
}

// Obtener parámetros de la URL
$grado = isset($_GET['grado']) ? $_GET['grado'] : '';
$grupo = isset($_GET['grupo']) ? $_GET['grupo'] : '';
$turno = isset($_GET['turno']) ? $_GET['turno'] : '';

// Convertir grupo a romano para mostrar
$grupo_romano = grupoToRomano($grupo);

if (empty($grado) || empty($grupo) || empty($turno)) {
    die("Grado, grupo y turno no especificados.");
}

// Obtener TODAS las materias del grado (no solo las que tienen calificaciones)
$query_materias = "
    SELECT id_materia, nombre_materia 
    FROM materias 
    WHERE grado_materia = '$grado' 
    AND turno_materia = '$turno'
    AND id_escuela = '$id_escuela'
    AND estado_materia = 0
    ORDER BY N_orden_materia
";
$result_materias = mysqli_query($conexion, $query_materias);

if (!$result_materias) {
    die("Error al obtener las materias: " . mysqli_error($conexion));
}

// Obtener los alumnos del grupo
$query_alumnos = "
    SELECT c.id_credencial, c.nombre_credencial, c.apellidos_credencial
    FROM credenciales c
    WHERE c.grado_credencial = '$grado' 
    AND c.grupo_credencial = '$grupo'
    AND c.turno_credencial = '$turno'
    AND c.id_escuela = '$id_escuela'
    AND c.nivel_usuario = 7
    ORDER BY c.apellidos_credencial, c.nombre_credencial
";
$result_alumnos = mysqli_query($conexion, $query_alumnos);

if (!$result_alumnos) {
    die("Error al obtener los alumnos: " . mysqli_error($conexion));
}

$secretKey = 'your-secret-key';

function decryptData($encryptedData, $secretKey) {
    if (empty($encryptedData)) {
        return '';
    }
    $parts = explode('::', base64_decode($encryptedData), 2);
    if (count($parts) !== 2) {
        return 'Datos encriptados inválidos';
    }
    list($ciphertext, $iv) = $parts;
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $secretKey, 0, base64_decode($iv));
}
?>

<?php include 'header_orientador.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Calificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'League Spartan', sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
        }
        .all {
            padding: 20px;
            margin-top: 4%;
        }
        .boleta {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .boleta h2 {
            color: #007bff;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #007bff;
            color: white;
            vertical-align: middle;
        }
        .table td {
            text-align: center;
            vertical-align: middle;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .text-center {
            text-align: center;
        }
        .mt-4 {
            margin-top: 1.5rem;
        }
        .no-data {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="all">
        <div class="boleta">
            <h2 class="text-center">Boleta de Calificaciones</h2>
            <h4 class="text-center">Escuela: <?php echo htmlspecialchars($nombre_escuela); ?></h4>
            <h5 class="text-center">Grado: <?php echo htmlspecialchars($grado); ?> - Grupo: <?php echo htmlspecialchars($grupo_romano); ?></h5>
            <h5 class="text-center">Turno: <?php echo htmlspecialchars($turno); ?></h5>

            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th rowspan="2">Alumnos</th>
                            <?php 
                            // Primera fila: nombres de materias
                            mysqli_data_seek($result_materias, 0);
                            while ($row_materia = mysqli_fetch_assoc($result_materias)) { ?>
                                <th colspan="3"><?php echo htmlspecialchars($row_materia['nombre_materia']); ?></th>
                            <?php } ?>
                        </tr>
                        <tr>
                            <?php
                            // Segunda fila: períodos de evaluación
                            mysqli_data_seek($result_materias, 0);
                            while ($row_materia = mysqli_fetch_assoc($result_materias)) {
                                echo "<th>1°P</th><th>2°P</th><th>3°P</th>";
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        while ($row_alumno = mysqli_fetch_assoc($result_alumnos)) { 
                            $nombre_completo = htmlspecialchars($row_alumno['nombre_credencial'] . ' ' . 
                                htmlspecialchars(decryptData($row_alumno['apellidos_credencial'], $secretKey)));
                        ?>
                            <tr>
                                <td class="text-start"><?php echo $nombre_completo; ?></td>
                                <?php
                                mysqli_data_seek($result_materias, 0);
                                while ($row_materia = mysqli_fetch_assoc($result_materias)) {
                                    $query_calificaciones = "
                                        SELECT primer_parcial, segundo_parcial, tercer_parcial
                                        FROM calificaciones
                                        WHERE id_alumno = '{$row_alumno['id_credencial']}'
                                        AND id_materia = '{$row_materia['id_materia']}'
                                        AND grado_credencial = '$grado'
                                        AND grupo_credencial = '$grupo'
                                        AND turno_credencial = '$turno'
                                        LIMIT 1
                                    ";
                                    $result_calificaciones = mysqli_query($conexion, $query_calificaciones);
                                    
                                    if ($result_calificaciones && mysqli_num_rows($result_calificaciones) > 0) {
                                        $row_calificaciones = mysqli_fetch_assoc($result_calificaciones);
                                        echo "<td>" . ($row_calificaciones['primer_parcial'] ?? 'N/A') . "</td>";
                                        echo "<td>" . ($row_calificaciones['segundo_parcial'] ?? 'N/A') . "</td>";
                                        echo "<td>" . ($row_calificaciones['tercer_parcial'] ?? 'N/A') . "</td>";
                                    } else {
                                        // Mostrar celdas vacías si no hay calificaciones
                                        echo "<td class='no-data'>-</td>";
                                        echo "<td class='no-data'>-</td>";
                                        echo "<td class='no-data'>-</td>";
                                    }
                                }
                                ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>

<?php
mysqli_close($conexion);
?>
<?php include 'footer_orientador.php'; ?>