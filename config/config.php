<?php
// Configuración básica del sistema
session_start();
define('DATA_PATH', __DIR__ . '/../data/');
define('INCLUDES_PATH', __DIR__ . '/../includes/');

// Tipos de usuario
define('ROL_ADMIN', 'admin');
define('ROL_TRABAJADOR', 'trabajador');

// Función para redireccionar
function redirect($url, $mensaje = null) {
    if ($mensaje) {
        $_SESSION['mensaje'] = $mensaje;
    }
    header("Location: $url");
    exit();
}

// Función para registrar logs
function registrarLog($accion, $usuario = null) {
    $usuario = $usuario ?? $_SESSION['usuario']['dni'] ?? 'Sistema';
    $log = date('Y-m-d H:i:s') . " | $usuario | $accion" . PHP_EOL;
    file_put_contents(DATA_PATH . 'logs.txt', $log, FILE_APPEND);
}

// Verificar autenticación
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario'])) {
        redirect('/sumaq-agroexport/auth/login.php', 'Debe iniciar sesión primero');
    }
}

// Verificar rol
function verificarRol($rolRequerido) {
    verificarAutenticacion();
    if ($_SESSION['usuario']['rol'] !== $rolRequerido) {
        redirect('/sumaq-agroexport/', 'No tiene permisos para acceder a esta sección');
    }
}

// Cargar datos desde archivo
function cargarDatos($archivo) {
    $ruta = DATA_PATH . $archivo;
    if (!file_exists($ruta)) return [];
    
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $datos = [];
    
    foreach ($lineas as $linea) {
        $datos[] = json_decode($linea, true);
    }
    
    return $datos;
}

// Guardar datos en archivo
function guardarDatos($archivo, $datos) {
    $ruta = DATA_PATH . $archivo;
    $contenido = '';
    
    foreach ($datos as $dato) {
        $contenido .= json_encode($dato) . PHP_EOL;
    }
    
    file_put_contents($ruta, $contenido, LOCK_EX);
}

// Función para formatear moneda
function formatoMoneda($valor) {
    return 'S/. ' . number_format($valor, 2);
}

// Función para obtener productos
function obtenerProductos() {
    return cargarDatos('productos.txt');
}

// Función para obtener destinos
function obtenerDestinos() {
    return cargarDatos('destinos.txt');
}

// Función para obtener usuarios
function obtenerUsuarios() {
    return cargarDatos('usuarios.txt');
}

// Función para obtener exportaciones
function obtenerExportaciones() {
    return cargarDatos('exportaciones.txt');
}

// Función para mostrar mensajes
function mostrarMensaje() {
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        $tipo = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : 'success';
        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        
        echo "<div class='alert alert-$tipo alert-dismissible fade show' role='alert'>
                $mensaje
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

// Función para validar DNI
function validarDNI($dni) {
    return preg_match('/^[0-9]{8}$/', $dni);
}

// Función para generar ID único
function generarIdUnico() {
    return uniqid('usr_', true);
}

// Función para obtener el nombre de un producto por su ID
function obtenerNombreProducto($idProducto) {
    $productos = obtenerProductos();
    foreach ($productos as $producto) {
        if ($producto['id'] === $idProducto) {
            return $producto['nombre'];
        }
    }
    return 'Producto Desconocido';
}

// Función para obtener el nombre de un destino por su ID
function obtenerNombreDestino($idDestino) {
    $destinos = obtenerDestinos();
    foreach ($destinos as $destino) {
        if ($destino['id'] === $idDestino) {
            return $destino['nombre'];
        }
    }
    return 'Destino Desconocido';
}

// Función para verificar si un destino es especial
function esDestinoEspecial($idDestino) {
    $destinos = obtenerDestinos();
    foreach ($destinos as $destino) {
        if ($destino['id'] === $idDestino) {
            return $destino['especial'] ?? false;
        }
    }
    return false;
}
?>