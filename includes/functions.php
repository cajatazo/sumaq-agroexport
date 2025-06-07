<?php
require_once __DIR__ . '/../config/config.php';

// Funciones adicionales de utilidad

function obtenerNombreProducto($id) {
    $productos = obtenerProductos();
    foreach ($productos as $producto) {
        if ($producto['id'] == $id) {
            return $producto['nombre'];
        }
    }
    return 'Desconocido';
}

function obtenerNombreDestino($id) {
    $destinos = obtenerDestinos();
    foreach ($destinos as $destino) {
        if ($destino['id'] == $id) {
            return $destino['nombre'];
        }
    }
    return 'Desconocido';
}

function esDestinoEspecial($idDestino) {
    $destino = obtenerNombreDestino($idDestino);
    $destinosEspeciales = ['EE.UU.', 'China', 'Alemania', 'Japón'];
    return in_array($destino, $destinosEspeciales);
}

function generarIdUnico() {
    return uniqid('', true);
}

function validarDNI($dni) {
    return preg_match('/^[0-9]{8}$/', $dni);
}

function mostrarMensaje() {
    if (isset($_SESSION['mensaje'])) {
        $tipo = $_SESSION['mensaje_tipo'] ?? 'success';
        echo '<div class="alert alert-' . htmlspecialchars($tipo) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['mensaje']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        unset($_SESSION['mensaje']);
        unset($_SESSION['mensaje_tipo']);
    }
}
?>