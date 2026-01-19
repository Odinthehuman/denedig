<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.4/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ---------------------------------------MENÚ--------------------------------------- */
        .navbar-custom {
            background-color: #e7ecfa; /* Fondo del navbar */
            color: #132d61; /* Texto en color */
        }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link {
            color: #132d61; /* Texto en color */
        }
        .navbar-custom .nav-link:hover {
            color: #004d99; /* Color del enlace al pasar el ratón */
        }
        .offcanvas-custom {
            background-color: #e7ecfa; /* Fondo del offcanvas */
            color: #132d61; /* Texto en color */
        }
        .offcanvas-custom .nav-link {
            color: #132d61; /* Texto en color */
        }
        .offcanvas-custom .nav-link:hover {
            color: #004d99; /* Color del enlace al pasar el ratón */
        }
        .navbar-toggler {
            border-color: #132d61; /* Color del borde del toggler */
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.5 3.5h13a.5.5 0 010 1h-13a.5.5 0 010-1zm0 4h13a.5.5 0 010 1h-13a.5.5 0 010-1zm0 4h13a.5.5 0 010 1h-13a.5.5 0 010-1z' fill='%23132d61'/%3E%3C/svg%3E");
        }
    </style>
</head>
<body>

    <!-- Menú -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="https://denedig.online/">
                <img src="../assets/images/alianzas/denedig.png" alt="Logo de Denedig" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="offcanvas offcanvas-end offcanvas-custom" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menú</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-house"></i> Inicio
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>


