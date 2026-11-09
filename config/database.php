<?php
/**
 * Archivo: config/database.php
 * Descripción: Configuración y conexión centralizada a MySQL mediante PDO.
 * Sistema: Clínica Salud Total C.A. - IUJO (Algoritmo y Programación II)
 */

declare(strict_types=1);

// Parámetros de conexión (valores por defecto para XAMPP / WampServer)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'clinica_gestion');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtiene una instancia única de conexión PDO a la base de datos.
 *
 * @return PDO Instancia activa de conexión PDO.
 */
function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retornar arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas nativas reales
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            http_response_code(500);
            die(
                '<div style="font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 24px; '
                . 'border-left: 5px solid #d9534f; background: #fdf7f7; color: #333; border-radius: 6px; '
                . 'box-shadow: 0 2px 10px rgba(0,0,0,0.08);">'
                . '<h2 style="margin-top: 0; color: #b52b27;">Error de Conexión a la Base de Datos</h2>'
                . '<p>No se pudo establecer comunicación con el servidor MySQL de <strong>Clínica Salud Total C.A.</strong></p>'
                . '<p style="font-size: 0.9rem; color: #555;">Verifique que el servicio MySQL esté activo en XAMPP/WampServer y que haya importado el archivo <code>schema.sql</code>.</p>'
                . '<hr style="border: none; border-top: 1px solid #e5d5d5; margin: 15px 0;">'
                . '<small style="color: #777;">Detalle técnico (PDOException): ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small>'
                . '</div>'
            );
        }
    }

    return $pdo;
}

// Inicializar variable $pdo global para disponibilidad directa en los módulos que incluyan este archivo
$pdo = obtenerConexion();
