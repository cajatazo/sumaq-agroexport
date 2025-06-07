<?php
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['usuario'])) {
    registrarLog('Cierre de sesión');
    unset($_SESSION['usuario']);
}

redirect('/sumaq-agroexport/auth/login.php', 'Has cerrado sesión correctamente');
?>