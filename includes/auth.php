<?php
/**
 * Archivo: includes/auth.php
 * Descripción: Gestión de sesiones, control de acceso por roles y utilidades de seguridad.
 * Sistema: Clínica Salud Total C.A.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que exista una sesión activa de usuario.
 * Si no está autenticado, redirige inmediatamente a index.php.
 */
function verificarAutenticacion(): void
{
    if (empty($_SESSION['id_usuario']) || empty($_SESSION['username'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Determina si el usuario autenticado posee el rol de Administrador.
 *
 * @return bool True si es Administrador, False en caso contrario.
 */
function esAdministrador(): bool
{
    if (empty($_SESSION['id_usuario'])) {
        return false;
    }

    $nombreRol = $_SESSION['nombre_rol'] ?? '';
    $idRol     = (int) ($_SESSION['id_rol'] ?? 0);

    return (strcasecmp($nombreRol, 'Administrador') === 0) || ($idRol === 1);
}

/**
 * Exige que el usuario tenga el rol de Administrador para continuar.
 * Si no tiene permisos, registra un mensaje de alerta y redirige al dashboard.
 */
function requerirAdministrador(): void
{
    verificarAutenticacion();

    if (!esAdministrador()) {
        establecerMensajeFlash(
            'Acceso denegado: Solo los usuarios con rol de Administrador pueden eliminar registros de pacientes.',
            'danger'
        );
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Genera o recupera el token CSRF de la sesión actual para proteger formularios POST.
 *
 * @return string Token hexadecimal seguro.
 */
function generarTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF enviado en una petición POST.
 *
 * @param string|null $token Token recibido del formulario.
 * @return bool True si el token es válido.
 */
function validarTokenCsrf(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Almacena un mensaje temporal (Flash) en la sesión para mostrar en la siguiente vista.
 *
 * @param string $texto Contenido del mensaje.
 * @param string $tipo  Tipo de alerta ('success', 'danger', 'warning', 'info').
 */
function establecerMensajeFlash(string $texto, string $tipo = 'success'): void
{
    $_SESSION['flash'] = [
        'texto' => $texto,
        'tipo'  => $tipo,
    ];
}

/**
 * Obtiene y elimina el mensaje temporal (Flash) almacenado en la sesión.
 *
 * @return array|null Arreglo con claves 'texto' y 'tipo', o null si no existe.
 */
function obtenerMensajeFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Escapa caracteres especiales HTML para prevenir ataques XSS en las vistas.
 *
 * @param string|int|float|null $valor Valor a sanitizar.
 * @return string Cadena segura para imprimir en HTML.
 */
function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

