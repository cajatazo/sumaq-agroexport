<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - Sumaq Agroexport</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c7c3f;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #333;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
        }
        
        .theme-toggle {
            cursor: pointer;
        }
        
        .export-btn-group .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/sumaq-agroexport/">
                <img src="/sumaq-agroexport/assets/img/logo.png" alt="Sumaq Agroexport Logo" height="40" class="me-2">
                Sumaq Agroexport
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION['usuario'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/sumaq-agroexport/"><i class="bi bi-house"></i> Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/sumaq-agroexport/exportaciones/registrar.php"><i class="bi bi-plus-circle"></i> Nueva Exportación</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/sumaq-agroexport/reportes/"><i class="bi bi-file-earmark-bar-graph"></i> Reportes</a>
                        </li>
                        
                        <?php if ($_SESSION['usuario']['rol'] === ROL_ADMIN): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-gear"></i> Administración
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/sumaq-agroexport/admin/productos.php"><i class="bi bi-basket"></i> Productos</a></li>
                                    <li><a class="dropdown-item" href="/sumaq-agroexport/admin/destinos.php"><i class="bi bi-globe-americas"></i> Destinos</a></li>
                                    <li><a class="dropdown-item" href="/sumaq-agroexport/admin/usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['usuario'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['usuario']['nombres']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text">Rol: <?= ucfirst($_SESSION['usuario']['rol']) ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="/sumaq-agroexport/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link theme-toggle" id="themeToggle">
                                <i class="bi bi-moon-stars" id="themeIcon"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/sumaq-agroexport/auth/login.php"><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <?php if (isset($_SESSION['usuario'])): ?>
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse py-3">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <div class="card bg-light">
                                <div class="card-body text-center py-2">
                                    <i class="bi bi-person-circle fs-3"></i>
                                    <h6 class="card-title mb-0"><?= htmlspecialchars($_SESSION['usuario']['nombres']) ?></h6>
                                    <small class="text-muted"><?= ucfirst($_SESSION['usuario']['rol']) ?></small>
                                </div>
                            </div>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="/sumaq-agroexport/">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'registrar.php' ? 'active' : '' ?>" href="/sumaq-agroexport/exportaciones/registrar.php">
                                <i class="bi bi-plus-circle"></i> Nueva Exportación
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'reportes') !== false ? 'active' : '' ?>" href="/sumaq-agroexport/reportes/">
                                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['usuario']['rol'] === ROL_ADMIN): ?>
                            <li class="nav-item">
                                <hr class="dropdown-divider">
                                <small class="text-muted ps-3">ADMINISTRACIÓN</small>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : '' ?>" href="/sumaq-agroexport/admin/productos.php">
                                    <i class="bi bi-basket"></i> Productos
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'destinos.php' ? 'active' : '' ?>" href="/sumaq-agroexport/admin/destinos.php">
                                    <i class="bi bi-globe-americas"></i> Destinos
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : '' ?>" href="/sumaq-agroexport/admin/usuarios.php">
                                    <i class="bi bi-people"></i> Usuarios
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Content Area -->
            <main class="<?= isset($_SESSION['usuario']) ? 'col-md-9 col-lg-10' : 'col-12' ?> ms-sm-auto px-md-4 py-4">
                <?php mostrarMensaje(); ?>