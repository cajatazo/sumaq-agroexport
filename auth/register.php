<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $rol = ROL_TRABAJADOR; // Por defecto, todos los nuevos son trabajadores
    
    // Validaciones
    $errores = [];
    
    if (!validarDNI($dni)) {
        $errores[] = 'El DNI debe tener 8 dígitos';
    }
    
    if (strlen($nombres) < 2) {
        $errores[] = 'Los nombres son requeridos';
    }
    
    if (strlen($apellidos) < 2) {
        $errores[] = 'Los apellidos son requeridos';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    }
    
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirm_password) {
        $errores[] = 'Las contraseñas no coinciden';
    }
    
    // Verificar si el DNI ya existe
    $usuarios = cargarDatos('usuarios.txt');
    foreach ($usuarios as $usuario) {
        if ($usuario['dni'] === $dni) {
            $errores[] = 'El DNI ya está registrado';
            break;
        }
    }
    
    if (empty($errores)) {
        $nuevoUsuario = [
            'id' => generarIdUnico(),
            'dni' => $dni,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => $rol,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'activo' => true
        ];
        
        $usuarios[] = $nuevoUsuario;
        guardarDatos('usuarios.txt', $usuarios);
        
        registrarLog('Nuevo usuario registrado: ' . $dni);
        redirect('/sumaq-agroexport/auth/login.php', 'Registro exitoso. Ahora puedes iniciar sesión.');
    } else {
        $_SESSION['mensaje'] = implode('<br>', $errores);
        $_SESSION['mensaje_tipo'] = 'error';
    }
}

$titulo = 'Registro de Usuario';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Registro de Usuario</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" required 
                                   pattern="[0-9]{8}" title="Ingrese un DNI válido (8 dígitos)"
                                   value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombres" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required
                                   value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required
                                   value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Registrarse
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small>¿Ya tienes una cuenta? <a href="/sumaq-agroexport/auth/login.php">Inicia sesión</a></small>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>