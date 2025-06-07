<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $usuarios = cargarDatos('usuarios.txt');
    $usuarioEncontrado = null;
    
    foreach ($usuarios as $usuario) {
        if ($usuario['dni'] === $dni && password_verify($password, $usuario['password'])) {
            $usuarioEncontrado = $usuario;
            break;
        }
    }
    
    if ($usuarioEncontrado) {
        $_SESSION['usuario'] = $usuarioEncontrado;
        registrarLog('Inicio de sesión exitoso');
        redirect('/sumaq-agroexport/', 'Bienvenido ' . $usuarioEncontrado['nombres']);
    } else {
        registrarLog('Intento de inicio de sesión fallido con DNI: ' . $dni);
        redirect('/sumaq-agroexport/auth/login.php', 'DNI o contraseña incorrectos', 'error');
    }
}

$titulo = 'Iniciar Sesión';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="dni" class="form-label">DNI</label>
                        <input type="text" class="form-control" id="dni" name="dni" required 
                               pattern="[0-9]{8}" title="Ingrese un DNI válido (8 dígitos)">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Ingresar
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small>¿No tienes una cuenta? <a href="/sumaq-agroexport/auth/register.php">Regístrate</a></small>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>