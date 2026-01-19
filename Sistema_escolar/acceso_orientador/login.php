                <?php include 'header.php';?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            margin-top: 5% !important;
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #0056b3;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #0056b3;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #003d80;
        }
        #qr-reader {
            width: 100%;
            margin-top: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
    <script src="QR/html5-qrcode.min.js"></script>
</head>
<body>
    <br>
    <div class="container">
        <h1>Inicio de Sesión</h1>
        <form id="qr-form" action="../QR/scan_qr_page.php" method="POST">
            <label for="qr_code">Código QR escaneado:</label>
            <input type="text" id="qr_code" name="qr_code" placeholder="Escanee o ingrese el código QR" required>
        
            <label for="token">Ingrese su token:</label>
            <input type="password" id="token" name="token" placeholder="Ingrese su token de seguridad" required maxlength="4" pattern="\d{4}" title="El token debe contener exactamente 4 dígitos.">
            <input type="submit" value="Acceder">

            <div id="qr-reader"></div>
        </form>
    </div>
<script>
    document.getElementById('qr-form').addEventListener('submit', function(event) {
        const tokenInput = document.getElementById('token');
        const tokenValue = tokenInput.value;

        if (!/^\d{4}$/.test(tokenValue)) {
            event.preventDefault(); // Evita el envío del formulario
            alert("El token debe contener exactamente 4 dígitos.");
            tokenInput.focus();
        }
    });
</script>
    <script>
        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('qr_code').value = decodedText;
        }

        function startScanning() {
            const html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" }, 
                {
                    fps: 10, 
                    qrbox: 250 
                },
                onScanSuccess
            ).catch(err => {
                console.error("Error al iniciar el escaneo: ", err);
            });
        }

        window.onload = () => {
            startScanning();
        }
    </script>
    <br>
</body>
<br>
                <?php include 'footer.php'; ?>

</html>
