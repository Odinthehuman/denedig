<?php
// generar_pdf_individual.php — Diseño tipo SEP (solo sección visual modificada)
session_start();
if (!isset($_SESSION['id_credencial'])) die("Acceso denegado.");

include '../funciones/conexQRConejo.php';
$secretKey = 'your-secret-key';

$id_alumno = $_GET['id'] ?? die('ID no válido');

// --- Función para desencriptar ---
function decryptData($data, $key) {
    if (empty($data)) return '';
    $parts = explode('::', base64_decode($data), 2);
    if (count($parts) !== 2) return '—';
    [$cipher, $iv] = $parts;
    return openssl_decrypt($cipher, 'aes-256-cbc', $key, 0, base64_decode($iv));
}


// --- Datos del alumno ---
$stmt = mysqli_prepare($conexion, "
    SELECT 
        c.nombre_credencial, 
        c.apellidos_credencial, 
        c.ruta_foto, 
        c.ruta_foto2,
        c.grado_credencial, 
        c.grupo_credencial, 
        c.turno_credencial, 
        c.id_escuela,
        c.curp_credencial,
        e.nombre_escuela,
        e.direccion,
        e.N_SEP AS cct_escuela  
    FROM credenciales c
    JOIN escuelas e ON c.id_escuela = e.id_escuela
    WHERE c.id_credencial = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_alumno);
mysqli_stmt_execute($stmt);
$alum = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$alum) die("Alumno no encontrado.");

$direccion = $alum['direccion'] ?? 'Dirección no disponible';

$nombre_completo = $alum['nombre_credencial'] . ' ' . decryptData($alum['apellidos_credencial'], $secretKey);
$grado   = $alum['grado_credencial'];
$grupo   = $alum['grupo_credencial'];
$turno   = $alum['turno_credencial'];
$escuela = $alum['nombre_escuela'];
$cct = $alum['cct_escuela'];

// ================= FOTO =================
$foto1 = !empty($alum['ruta_foto'])  
    ? $_SERVER['DOCUMENT_ROOT'] . '/sistema_escolar/' . ltrim($alum['ruta_foto'], '/')  
    : '';

$foto2 = !empty($alum['ruta_foto2']) 
    ? $_SERVER['DOCUMENT_ROOT'] . '/sistema_escolar/' . ltrim($alum['ruta_foto2'], '/') 
    : '';

$foto_default = __DIR__ . '/fpdf/R.png';

$foto = file_exists($foto1) ? $foto1 : (file_exists($foto2) ? $foto2 : $foto_default);

// --- Materias ---
$materias = [];
$stmt = mysqli_prepare($conexion, "
    SELECT m.id_materia, m.nombre_materia
    FROM asignacion_materias am
    JOIN materias m ON am.id_materia = m.id_materia
    WHERE am.grado_credencial = ?
      AND am.grupo_credencial = ?
      AND am.turno_credencial = ?
      AND am.id_escuela = ?
    ORDER BY m.N_orden_materia
");
mysqli_stmt_bind_param($stmt, "sssi", $grado, $grupo, $turno, $alum['id_escuela']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) $materias[] = $row;

// --- Calificaciones ---
$calificaciones = [];
$stmt = mysqli_prepare($conexion, "
    SELECT id_materia, primer_parcial, segundo_parcial, tercer_parcial
    FROM calificaciones
    WHERE id_alumno = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_alumno);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $id_materia = (int)$row['id_materia'];
    $calificaciones[$id_materia] = $row;
}

// --- FPDF ---
require_once 'fpdf/fpdf.php';

class BoletaPDF extends FPDF {

    function Header() {
        // (si quieres encabezado, aquí va)
    }

    function Footer() {
        // Posición fija 12 mm antes del borde inferior
        $this->SetY(-12);

        // Color y fuente
        $this->SetTextColor(100, 100, 100);
        $this->SetFont('Arial', 'I', 7);

        // Línea separadora (opcional)
        $this->SetDrawColor(200, 200, 200);
        $this->Line(12, $this->GetY(), 204, $this->GetY()); // 12 = margen izq, 204 = ancho carta - margen der

        // Texto centrado
        $this->SetY(-9);
        $this->Cell(0, 5, utf8_decode('Documento oficial - Generado el ' . date('d/m/Y H:i')), 0, 0, 'C');
    }
}

$pdf = new BoletaPDF('P', 'mm', 'Letter');
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// ================= ENCABEZADO OFICIAL =================

// Rutas de los logos
$logo_sep   = __DIR__ . '/img/logo_sep.png';
$logo_edomx = __DIR__ . '/img/edomex.png';

// Medidas y posiciones
$logo_ancho = 50;
$logo_y     = 8;

// Logo izquierdo (SEP)
if (file_exists($logo_sep)) {
    $pdf->Image($logo_sep, 12, $logo_y, $logo_ancho);
}

// Logo derecho (Estado de México)
if (file_exists($logo_edomx)) {
    $pdf->Image($logo_edomx, 200- $logo_ancho, $logo_y, $logo_ancho);
}

// Texto centrado
$pdf->SetY($logo_y);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, utf8_decode('PRUEBA DE BOLETA'), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 6, utf8_decode('SISTEMA EDUCATIVO NACIONAL'), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 6, utf8_decode('ESTADO DE MÉXICO'), 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 6, utf8_decode('BOLETA DE EVALUACIÓN'), 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 5, utf8_decode('CICLO ESCOLAR 2025-2026'), 0, 1, 'C');

// Espacio después del encabezado
$pdf->Ln(12);
// === DATOS DEL ALUMNO ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 6, utf8_decode('DATOS DEL(AL) ALUMNO(A)'), 0, 1, 'C', true);
$pdf->Ln(6);

// Apellidos
$apellidos = explode(' ', decryptData($alum['apellidos_credencial'], $secretKey));
$primer_apellido  = strtoupper($apellidos[0] ?? '');
$segundo_apellido = strtoupper($apellidos[1] ?? '');
$nombres          = strtoupper($alum['nombre_credencial']);

$x = 12;
$y = $pdf->GetY();

// Medidas
$foto_ancho = 30;
$foto_alto  = 35;
$separacion = 6;
$ancho_texto = 190 - $foto_ancho - $separacion;

// ====== TEXTO ======
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY($x, $y);
$pdf->Cell(40, 6, utf8_decode('Primer apellido:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 6, utf8_decode($primer_apellido), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX($x);
$pdf->Cell(40, 6, utf8_decode('Segundo apellido:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 6, utf8_decode($segundo_apellido), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX($x);
$pdf->Cell(40, 6, utf8_decode('Nombre:'), 0, 0);
$pdf->SetFont('Arial', '',11);
$pdf->Cell($ancho_texto - 40, 6, utf8_decode($nombres), 0, 1);
//curd
$curp = $alum['curp_credencial'] ?? '';
$curp_desencriptado = decryptData($curp, $secretKey);
if ($curp_desencriptado === '' || $curp_desencriptado === '—') {
    $curp_desencriptado = '—';
}
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0,0);
$pdf->SetX($x, $y);
$pdf->Cell(40,6,utf8_decode('CURD:'),0,0);
$pdf->SetFont('Arial', '',11);
$pdf->Cell($ancho_texto -40, 6, utf8_decode( $curp_desencriptado), 0, 1);
$pdf->SetTextColor(0, 0, 0);

// ====== BLOQUE GRADO / GRUPO / TURNO ======
$info_x = $x + 90; // mueve el bloque a la derecha
$info_y = $y;       // mismo nivel que apellidos

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY($info_x, $info_y);
$pdf->Cell(22, 6, utf8_decode('Grado:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(20, 6, utf8_decode($grado), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX($info_x);
$pdf->Cell(22, 6, utf8_decode('Grupo:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(20, 6, utf8_decode($grupo), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX($info_x);
$pdf->Cell(22, 6, utf8_decode('Turno:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(20, 6, utf8_decode($turno), 0, 1); 
// ====== FOTO ======
$foto_y = $y - 5;

$pdf->Rect(
    $x + $ancho_texto + $separacion,
    $foto_y,
    $foto_ancho,
    $foto_alto
);

$pdf->Image(
    $foto,
    $x + $ancho_texto + $separacion,
    $foto_y,
    $foto_ancho,
    $foto_alto
);

$pdf->Ln(13);
// === DATOS DE LA ESCUELA ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 6, utf8_decode('DATOS DE LA ESCUELA'), 0, 1, 'C', true);
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(45, 6, utf8_decode('Nombre de la escuela:'), 0, 0);

$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, utf8_decode($escuela));

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(45, 6, utf8_decode('Dirección:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, utf8_decode($direccion),0,1);

$yDespues = $pdf->GetY();   // ← posición real después del texto

$pdf->SetXY(150, $yDespues - 6); // ajusta según tu diseño
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(15, 6, 'CCT:', 0, 0);

$pdf->SetFont('Arial', 'I', 11);
$pdf->Cell(0, 6, utf8_decode($cct), 0, 1);

$pdf->Ln(8);

// ================== TABLA DE CALIFICACIONES ==================
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(100, 7, utf8_decode('Materias'), 1, 0, 'C', true);
$pdf->Cell(15, 7, 'I', 1, 0, 'C', true);
$pdf->Cell(15, 7, 'II', 1, 0, 'C', true);
$pdf->Cell(15, 7, 'III', 1, 0, 'C', true);
$pdf->Cell(35, 7, utf8_decode('PROMEDIO FINAL'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);

function calcularPromedio($p1, $p2, $p3) {
    if (!is_numeric($p1) || !is_numeric($p2) || !is_numeric($p3)) {
        return '--';
    }

    $promedio = ($p1 + $p2 + $p3) / 3;
    $entero   = floor($promedio);
    $decimal  = $promedio - $entero;

    return ($decimal >= 0.6) ? $entero + 1 : $entero;
}
function setColorPorPromedio($pdf, $prom) {
    if (!is_numeric($prom)) {
        $pdf->SetTextColor(0, 0, 0);
        return;
    }

    if ($prom > 9) {
        $pdf->SetTextColor(25, 135, 84); // verde
    } elseif ($prom <= 6) {
        $pdf->SetTextcolor(220, 53, 69); // rojo
    } else {
        $pdf->SetTextColor(0, 0, 0); // negro
    }
}
foreach ($materias as $mat) {
    $id_materia = (int)$mat['id_materia'];
    $calif = $calificaciones[$id_materia] ?? [];

    $p1 = $calif['primer_parcial'] ?? '--';
    $p2 = $calif['segundo_parcial'] ?? '--';
    $p3 = $calif['tercer_parcial'] ?? '--';

    $prom = calcularPromedio($p1, $p2, $p3);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(100, 6, utf8_decode($mat['nombre_materia']), 1);
    $pdf->Cell(15, 6, $p1, 1, 0, 'C');
    $pdf->Cell(15, 6, $p2, 1, 0, 'C');
    $pdf->Cell(15, 6, $p3, 1, 0, 'C');
    setColorPorPromedio($pdf, $prom);//Llamamos a la funcion para los colores
    $pdf->SetFont('Arial', 'B', 10);//cambia el ancho de la letra en el promedio
    $pdf->Cell(35, 6, $prom, 1, 1, 'C');
    $pdf->SetFont('Arial', '', 9);//regresa el tamaño normal
    $pdf->SetTextColor(0, 0, 0); // reset
}
/**foreach ($materias as $mat) {
    $id_materia = (int)$mat['id_materia'];
    $calif = $calificaciones[$id_materia] ?? [];
    
    $p1 = $calif['primer_parcial'] ?? '—';
    $p2 = $calif['segundo_parcial'] ?? '—';
    $p3 = $calif['tercer_parcial'] ?? '—';
    
    // Logica para sacar el promedio y sea redondeado
    $prom = '—';

if (is_numeric($p1) && is_numeric($p2) && is_numeric($p3)) {
    $promedio = ($p1 + $p2 + $p3) / 3;

    $entero = floor($promedio);
    $decimal = $promedio - $entero;

    if ($decimal >= 0.6) {
        $prom = $entero + 1;
    } else {
        $prom = $entero;
    }
    // Color según la calificación
    if ($prom > 7) {
    $pdf->SetTextColor(25, 135, 84); //verde
    } elseif ($prom == 6) {
    $pdf->SetTextColor(220, 53, 69); // rojo
    } else {
    $pdf->SetTextColor(0, 0, 0); // negro
    }
}
    
    $pdf->Cell(90, 6, utf8_decode($mat['nombre_materia']), 1);
    $pdf->Cell(15, 6, $p1, 1, 0, 'C');
    $pdf->Cell(15, 6, $p2, 1, 0, 'C');
    $pdf->Cell(15, 6, $p3, 1, 0, 'C');
    $pdf->Cell(30, 6, $prom, 1, 1, 'C');
}
**/
$pdf->Ln(1);

// === PROMEDIO FINAL DE GRADO ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(100, 6, utf8_decode('PROMEDIO GENERAL'), 1, 0, 'C', true);
$pdf->Cell(30, 6, '8.1', 1, 1, 'C', true); // Calcula si lo deseas

// === BLOQUE HORIZONTAL: FIRMA DEL PADRE DE FAMILIA + SUGERENCIAS ===
$pdf->Ln(8);

// Posiciones
$yBloque = $pdf->GetY();
$ancho_izquierda = 90;   // Firmas
$ancho_derecha = 90;     // Sugerencias
$altura_firma = 30;      // Altura generosa para firmas

// === FIRMA DEL PADRE DE FAMILIA (Columna izquierda) ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($ancho_izquierda, 6, utf8_decode('FIRMA DEL PADRE DE FAMILIA'), 0, 0, 'C', true);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY(12, $yBloque + 8);
$pdf->Cell($ancho_izquierda / 3, 6, utf8_decode('Primer Parcial'), 1, 0, 'C', true);
$pdf->Cell($ancho_izquierda / 3, 6, utf8_decode('Segundo Parcial'), 1, 0, 'C', true);
$pdf->Cell($ancho_izquierda / 3, 6, utf8_decode('Tercer Parcial'), 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(12, $yBloque + 14);
$pdf->Cell($ancho_izquierda / 3, $altura_firma, '', 1, 0, 'C');
$pdf->Cell($ancho_izquierda / 3, $altura_firma, '', 1, 0, 'C');
$pdf->Cell($ancho_izquierda / 3, $altura_firma, '', 1, 1, 'C');


// === TABLA DE SUGERENCIAS (ÁREAS A LA IZQUIERDA, ESPACIO PARA ESCRIBIR A LA DERECHA) ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(12 + $ancho_izquierda + 6, $yBloque); // 6 = espacio entre columnas
$pdf->Cell($ancho_derecha, 6, utf8_decode('SUGERENCIAS'), 0, 0, 'C', true);

// Definir el ancho de las columnas
$ancho_area = 25; // Ancho para el nombre del área
$ancho_espacio = $ancho_derecha - $ancho_area; // Espacio restante para escribir

// Título de las columnas
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetXY(12 + $ancho_izquierda + 6, $yBloque + 8);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($ancho_area, 6, utf8_decode('Periodo'), 1, 0, 'C', true);
$pdf->Cell($ancho_espacio, 6, utf8_decode('Observaciones'), 1, 1, 'C', true);
$pdf->SetFillColor(255, 255, 255);

// Definir las áreas (como en el ejemplo)
$areas = ['1er Parcial','2do Parcial','3er Parcial'];

// Altura de cada fila
$altura_fila = 10;

// Dibujar 5 FILAS con las áreas y espacios para escribir
for ($i = 0; $i < 3; $i++) {
    $pdf->SetXY(12 + $ancho_izquierda + 6, $yBloque + 14 + ($i * $altura_fila));
    
    // Celda para el nombre del área
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($ancho_area, $altura_fila, utf8_decode($areas[$i]), 1, 0, 'C');
    
    // Celda para escribir las sugerencias
    $pdf->Cell($ancho_espacio, $altura_fila, '', 1, 1);
}

$pdf->Ln(8);
// Salida
$filename = "Boleta_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $nombre_completo) . ".pdf";
$pdf->Output('I', $filename);

mysqli_close($conexion);
?>