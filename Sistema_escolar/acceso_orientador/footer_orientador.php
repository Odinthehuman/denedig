<!DOCTYPE html>
<html lang="en">
<head>
    <!--footer_orientador.php-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bubbles Animation</title>
    <style>
        /* Estilos generales */
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }

        footer {
            position: relative;
            background-color: #132d61;
            color: white;
            padding: 40px 20px;
            text-align: center;
            overflow: hidden;
        }

        /* Burbujas */
        .bubbles {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            pointer-events: none;
        }

        .bubble {
            position: absolute;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            opacity: 0.7;
            animation: rise 8s infinite ease-in-out;
        }

        @keyframes rise {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-200px) scale(1.1);
                opacity: 0.4;
            }
            100% {
                transform: translateY(-400px) scale(1.3);
                opacity: 0;
            }
        }

        /* Tamaños y posiciones de las burbujas */
        .bubble:nth-child(1) {
            width: 40px;
            height: 40px;
            left: 10%;
            animation-duration: 6s;
            animation-delay: 0s;
        }

        .bubble:nth-child(2) {
            width: 60px;
            height: 60px;
            left: 30%;
            animation-duration: 8s;
            animation-delay: 2s;
        }

        .bubble:nth-child(3) {
            width: 20px;
            height: 20px;
            left: 50%;
            animation-duration: 7s;
            animation-delay: 1s;
        }

        .bubble:nth-child(4) {
            width: 80px;
            height: 80px;
            left: 70%;
            animation-duration: 9s;
            animation-delay: 3s;
        }

        .bubble:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 90%;
            animation-duration: 5s;
            animation-delay: 0s;
        }

        .bubble:nth-child(6) {
            width: 35px;
            height: 35px;
            left: 20%;
            animation-duration: 7s;
            animation-delay: 4s;
        }

        .bubble:nth-child(7) {
            width: 25px;
            height: 25px;
            left: 40%;
            animation-duration: 6s;
            animation-delay: 1.5s;
        }

        /* Estilo del contenido del footer */
        .footer-content {
            max-width: 1200px;
            margin: auto;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .footer-content div {
            flex: 1;
            min-width: 250px;
            margin-bottom: 20px;
            text-align: left;
        }

        h3 {
            margin-bottom: 20px;
        }

        a {
            color: white;
        }

        /* Borde superior del footer */
        .footer-bottom {
            margin-top: 20px;
            border-top: 1px solid #ffffff;
            padding-top: 20px;
        }

    </style>
</head>
<body>

   <footer>
        <!-- Burbujas animadas -->
        <div class="bubbles">
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
        </div>

        <div class="footer-content">
            <!-- Columna 1 -->
            <div>
                <h3>Corporativo</h3>
                <p><strong>Nombre:</strong> DENEDIG SAS DE C.V.</p>
                <p><strong>Tipo:</strong> Desarrolladores en Negocios Digitales</p>
                <p><strong>Giro:</strong> Tecnología</p>
                <p><strong>Sector:</strong> Privado</p>
            </div>
            <!-- Columna 2 -->
            <div>
                <h3>Información Legal</h3>
                <p><strong>Entidad Receptora:</strong> DENEDIG SAS DE C.V.</p>
                <p><strong>Gerente General:</strong> Mtra. Nieva Noemi López Franco</p>
                <p><strong>Preparación Profesional:</strong> Maestría en Docencia y Administración de la Educación Superior</p>
            </div>
            <!-- Columna 3 -->
            <div>
                <h3>Contacto</h3>
                <p><strong>Correo electrónico:</strong> <a href="mailto:noemif2102@gmail.com">noemif2102@gmail.com</a></p>
                <p><strong>Domicilio:</strong> Leibnitz #270, Colonia Anzures, C.P. 11590, Ciudad México</p>
                <p><strong>Teléfono:</strong> <a href="tel:+525573895088">5573895088</a></p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 DENEDIG SAS DE C.V. Todos los derechos reservados.</p>
        </div>
    </footer>

</body>
</html>
