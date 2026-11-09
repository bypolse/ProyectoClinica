<?php
/**
 * Archivo: index.php
 * Descripción: Inicio de sesión seguro con PDO Prepared Statements y password_verify().
 * Sistema: Clínica Salud Total C.A. - IUJO (Algoritmo y Programación II)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Si el usuario ya tiene sesión activa, redirigir directamente al panel principal
if (!empty($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$errorLogin   = '';
$usernameForm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameForm = trim((string) ($_POST['username'] ?? ''));
    $passwordForm = (string) ($_POST['password'] ?? '');

    if ($usernameForm === '' || $passwordForm === '') {
        $errorLogin = 'Por favor, ingrese su nombre de usuario y contraseña.';
    } else {
        try {
            $sql = 'SELECT u.id_usuario, u.id_rol, u.username, u.password, u.estado, r.nombre_rol
                    FROM usuarios u
                    LEFT JOIN roles r ON u.id_rol = r.id_rol
                    WHERE u.username = :username
                    LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $usernameForm]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($passwordForm, $usuario['password'])) {
                if ((int) $usuario['estado'] !== 1) {
                    $errorLogin = 'Esta cuenta de usuario se encuentra inactiva. Contacte al Administrador.';
                } else {
                    // Prevenir ataques de fijación de sesión
                    session_regenerate_id(true);

                    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
                    $_SESSION['id_rol']     = (int) ($usuario['id_rol'] ?? 2);
                    $_SESSION['username']   = $usuario['username'];
                    $_SESSION['nombre_rol'] = $usuario['nombre_rol'] ?? 'Empleado';

                    $saludoRol = esAdministrador()
                        ? 'Acceso total habilitado (Administrador).'
                        : 'Acceso de gestión básica habilitado (Empleado).';

                    establecerMensajeFlash(
                        'Bienvenido(a), ' . $usuario['username'] . '. ' . $saludoRol,
                        'success'
                    );

                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $errorLogin = 'Credenciales incorrectas. Verifique su usuario y contraseña.';
            }
        } catch (PDOException $e) {
            $errorLogin = 'Error al procesar la autenticación en la base de datos: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Clínica Salud Total C.A.</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
    <main class="login-wrapper">
        <section class="login-card" aria-labelledby="login-heading">
            <div class="login-header">
                <div class="login-logo" aria-hidden="true">+</div>
                <h1 id="login-heading">Clínica Salud Total C.A.</h1>
                <p>Sistema Web de Gestión de Pacientes</p>
            </div>

            <?php if ($errorLogin !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <span><?= e($errorLogin) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout']) && $_GET['logout'] === '1'): ?>
                <div class="alert alert-info" role="status">
                    <span>Su sesión ha finalizado correctamente.</span>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST" id="formLogin" onsubmit="return validarLogin(this);" novalidate>
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Ej. admin o empleado"
                        value="<?= e($usernameForm) ?>"
                        required
                        autocomplete="username"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Ingrese su contraseña"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div id="loginErrorJs" class="field-error" style="display: none; margin-bottom: 12px;"></div>

                <button type="submit" class="btn btn-primary btn-block">
                    Ingresar al Sistema
                </button>
            </form>

            <aside class="login-credentials-box" aria-label="Credenciales de prueba académica">
                <h2>Credenciales de Prueba (Semilla SQL)</h2>
                <div class="credential-items">
                    <div class="credential-pill">
                        <strong>Administrador:</strong> <code>admin</code> / <code>admin123</code>
                    </div>
                    <div class="credential-pill">
                        <strong>Empleado:</strong> <code>empleado</code> / <code>123456</code>
                    </div>
                </div>
            </aside>
        </section>
    </main>

    <script>
        function validarLogin(form) {
            var usuario = form.username.value.trim();
            var clave   = form.password.value;
            var cajaErr = document.getElementById('loginErrorJs');

            if (usuario.length === 0 || clave.length === 0) {
                cajaErr.textContent = 'Debe completar tanto el usuario como la contraseña.';
                cajaErr.style.display = 'block';
                return false;
            }

            cajaErr.style.display = 'none';
            return true;
        }
    </script>
</body>
</html>

