<?php
/**
 * Archivo: logout.php
 * Descripción: Cierre limpio de sesión y redirección al formulario de inicio de sesión.
 * Sistema: Clínica Salud Total C.A.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar todas las variables de sesión
$_SESSION = [];

// Eliminar la cookie de sesión en el navegador si aplica
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        (bool) $params['secure'],
        (bool) $params['httponly']
    );
}

// Destruir la sesión en el servidor
session_destroy();

// Redirigir al login con indicador de cierre exitoso
header('Location: index.php?logout=1');
exit;

