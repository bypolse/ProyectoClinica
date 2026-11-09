<?php
/**
 * Archivo: paciente_crear.php
 * Descripción: Registro de nuevos pacientes con validaciones en servidor (PHP/PDO) y cliente (JS).
 * Sistema: Clínica Salud Total C.A. - IUJO (Algoritmo y Programación II)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$tituloPagina = 'Registrar Nuevo Paciente';
$errores      = [];

// Valores iniciales para persistencia del formulario en caso de error
$datos = [
    'nombres'          => '',
    'apellidos'        => '',
    'cedula_identidad' => '',
    'datos_contacto'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificar token CSRF
    $tokenRecibido = (string) ($_POST['csrf_token'] ?? '');
    if (!validarTokenCsrf($tokenRecibido)) {
        $errores[] = 'La sesión del formulario caducó. Por favor, intente enviar el formulario nuevamente.';
    }

    // 2. Capturar y sanear entradas
    $datos['nombres']          = trim((string) ($_POST['nombres'] ?? ''));
    $datos['apellidos']        = trim((string) ($_POST['apellidos'] ?? ''));
    $datos['cedula_identidad'] = strtoupper(trim((string) ($_POST['cedula_identidad'] ?? '')));
    $datos['datos_contacto']   = trim((string) ($_POST['datos_contacto'] ?? ''));

    // Normalizar formato de cédula (si solo ingresó números, anteponer V-; si ingresó V12345, separar con guion)
    if (preg_match('/^[0-9]{5,12}$/', $datos['cedula_identidad'])) {
        $datos['cedula_identidad'] = 'V-' . $datos['cedula_identidad'];
    } elseif (preg_match('/^([VEJPG])([0-9]{5,12})$/', $datos['cedula_identidad'], $coincidencias)) {
        $datos['cedula_identidad'] = $coincidencias[1] . '-' . $coincidencias[2];
    }

    // 3. Validaciones de negocio en Backend (PHP)
    if ($datos['nombres'] === '' || mb_strlen($datos['nombres']) < 2 || mb_strlen($datos['nombres']) > 100) {
        $errores[] = 'El campo Nombres es obligatorio y debe tener entre 2 y 100 caracteres.';
    }

    if ($datos['apellidos'] === '' || mb_strlen($datos['apellidos']) < 2 || mb_strlen($datos['apellidos']) > 100) {
        $errores[] = 'El campo Apellidos es obligatorio y debe tener entre 2 y 100 caracteres.';
    }

    if (!preg_match('/^[VEJPG]-[0-9]{5,12}$/', $datos['cedula_identidad'])) {
        $errores[] = 'La Cédula de Identidad no tiene un formato válido (ejemplo: V-18452910 o E-81234567).';
    }

    if ($datos['datos_contacto'] === '' || mb_strlen($datos['datos_contacto']) < 5 || mb_strlen($datos['datos_contacto']) > 255) {
        $errores[] = 'Los Datos de Contacto (teléfono y seguro médico) son obligatorios (entre 5 y 255 caracteres).';
    }

    // 4. Verificar unicidad de la cédula e insertar en la base de datos con PDO
    if (empty($errores)) {
        try {
            $stmtExiste = $pdo->prepare(
                'SELECT COUNT(*) FROM entidad_gestion WHERE cedula_identidad = :cedula'
            );
            $stmtExiste->execute([':cedula' => $datos['cedula_identidad']]);

            if ((int) $stmtExiste->fetchColumn() > 0) {
                $errores[] = 'Ya existe un paciente registrado con la Cédula de Identidad ' . $datos['cedula_identidad'] . '.';
            } else {
                $sqlInsert = 'INSERT INTO entidad_gestion (
                                  id_usuario_responsable,
                                  nombres,
                                  apellidos,
                                  cedula_identidad,
                                  datos_contacto
                              ) VALUES (
                                  :id_responsable,
                                  :nombres,
                                  :apellidos,
                                  :cedula,
                                  :contacto
                              )';

                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':id_responsable' => (int) $_SESSION['id_usuario'],
                    ':nombres'        => $datos['nombres'],
                    ':apellidos'      => $datos['apellidos'],
                    ':cedula'         => $datos['cedula_identidad'],
                    ':contacto'       => $datos['datos_contacto'],
                ]);

                establecerMensajeFlash(
                    'Paciente ' . $datos['nombres'] . ' ' . $datos['apellidos'] . ' (' . $datos['cedula_identidad'] . ') registrado exitosamente.',
                    'success'
                );

                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $errores[] = 'Error en la base de datos al registrar el paciente: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="form-section" aria-labelledby="titulo-crear-paciente">
    <div class="section-header">
        <div>
            <h1 id="titulo-crear-paciente" class="page-title">Registrar Nuevo Paciente</h1>
            <p class="page-subtitle">
                Complete los datos filiatorios y de contacto. El registro quedará asociado al usuario responsable <strong><?= e($_SESSION['username']) ?></strong>.
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline">&larr; Volver al Listado</a>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger" role="alert">
            <div>
                <strong>Por favor corrija los siguientes errores:</strong>
                <ul class="error-list">
                    <?php foreach ($errores as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <article class="card form-card">
        <form
            action="paciente_crear.php"
            method="POST"
            id="formCrearPaciente"
            onsubmit="return validarFormularioPaciente(this);"
            novalidate
        >
            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCsrf()) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="nombres">Nombres del Paciente <span class="required">*</span></label>
                    <input
                        type="text"
                        id="nombres"
                        name="nombres"
                        class="form-control"
                        placeholder="Ej. María Fernanda"
                        maxlength="100"
                        value="<?= e($datos['nombres']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="apellidos">Apellidos del Paciente <span class="required">*</span></label>
                    <input
                        type="text"
                        id="apellidos"
                        name="apellidos"
                        class="form-control"
                        placeholder="Ej. Gómez Pérez"
                        maxlength="100"
                        value="<?= e($datos['apellidos']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cedula_identidad">Cédula de Identidad <span class="required">*</span></label>
                    <input
                        type="text"
                        id="cedula_identidad"
                        name="cedula_identidad"
                        class="form-control"
                        placeholder="Ej. V-18452910"
                        maxlength="50"
                        value="<?= e($datos['cedula_identidad']) ?>"
                        required
                    >
                    <small class="form-hint">Formato venezolano: V-12345678 o E-12345678 (única por paciente).</small>
                </div>

                <div class="form-group">
                    <label for="responsable_vista">Usuario Responsable del Registro</label>
                    <input
                        type="text"
                        id="responsable_vista"
                        class="form-control"
                        value="<?= e($_SESSION['username'] . ' (' . $_SESSION['nombre_rol'] . ')') ?>"
                        disabled
                    >
                    <small class="form-hint">Asignado automáticamente según su sesión activa.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="datos_contacto">Datos de Contacto (Teléfono y Seguro Médico) <span class="required">*</span></label>
                <input
                    type="text"
                    id="datos_contacto"
                    name="datos_contacto"
                    class="form-control"
                    placeholder="Ej. Tel: 0414-5551234 | Seguro: Seguros Caracas (Póliza #SC-9841)"
                    maxlength="255"
                    value="<?= e($datos['datos_contacto']) ?>"
                    required
                >
                <small class="form-hint">Indique número telefónico y compañía de seguros o si es paciente particular.</small>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Paciente</button>
            </div>
        </form>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

