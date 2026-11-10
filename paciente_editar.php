<?php
/**
 * Archivo: paciente_editar.php
 * Descripción: Edición y actualización de datos de un paciente existente mediante PDO.
 * Sistema: Clínica Salud Total C.A.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$tituloPagina = 'Editar Paciente';
$errores      = [];

// Obtener ID del registro desde GET o POST
$idRegistro = (int) ($_GET['id'] ?? $_POST['id_registro'] ?? 0);

if ($idRegistro <= 0) {
    establecerMensajeFlash('Identificador de paciente no válido.', 'warning');
    header('Location: dashboard.php');
    exit;
}

// Consultar datos actuales del paciente
try {
    $sqlConsulta = 'SELECT p.id_registro,
                           p.id_usuario_responsable,
                           p.nombres,
                           p.apellidos,
                           p.cedula_identidad,
                           p.datos_contacto,
                           p.fecha_registro,
                           u.username AS responsable_username
                    FROM entidad_gestion p
                    LEFT JOIN usuarios u ON p.id_usuario_responsable = u.id_usuario
                    WHERE p.id_registro = :id
                    LIMIT 1';

    $stmtConsulta = $pdo->prepare($sqlConsulta);
    $stmtConsulta->execute([':id' => $idRegistro]);
    $pacienteActual = $stmtConsulta->fetch();

    if (!$pacienteActual) {
        establecerMensajeFlash('El paciente solicitado no existe o fue eliminado.', 'danger');
        header('Location: dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    establecerMensajeFlash('Error al consultar el paciente: ' . $e->getMessage(), 'danger');
    header('Location: dashboard.php');
    exit;
}

// Inicializar datos del formulario con los valores actuales de la base de datos
$datos = [
    'nombres'          => (string) $pacienteActual['nombres'],
    'apellidos'        => (string) $pacienteActual['apellidos'],
    'cedula_identidad' => (string) $pacienteActual['cedula_identidad'],
    'datos_contacto'   => (string) ($pacienteActual['datos_contacto'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = (string) ($_POST['csrf_token'] ?? '');
    if (!validarTokenCsrf($tokenRecibido)) {
        $errores[] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
    }

    $datos['nombres']          = trim((string) ($_POST['nombres'] ?? ''));
    $datos['apellidos']        = trim((string) ($_POST['apellidos'] ?? ''));
    $datos['cedula_identidad'] = strtoupper(trim((string) ($_POST['cedula_identidad'] ?? '')));
    $datos['datos_contacto']   = trim((string) ($_POST['datos_contacto'] ?? ''));

    // Normalizar formato de cédula
    if (preg_match('/^[0-9]{5,12}$/', $datos['cedula_identidad'])) {
        $datos['cedula_identidad'] = 'V-' . $datos['cedula_identidad'];
    } elseif (preg_match('/^([VEJPG])([0-9]{5,12})$/', $datos['cedula_identidad'], $coincidencias)) {
        $datos['cedula_identidad'] = $coincidencias[1] . '-' . $coincidencias[2];
    }

    // Validaciones en servidor
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

    if (empty($errores)) {
        try {
            // Verificar que la cédula no pertenezca a otro paciente distinto
            $stmtDuplicado = $pdo->prepare(
                'SELECT COUNT(*) FROM entidad_gestion WHERE cedula_identidad = :cedula AND id_registro != :id'
            );
            $stmtDuplicado->execute([
                ':cedula' => $datos['cedula_identidad'],
                ':id'     => $idRegistro,
            ]);

            if ((int) $stmtDuplicado->fetchColumn() > 0) {
                $errores[] = 'Otro paciente ya se encuentra registrado con la Cédula de Identidad ' . $datos['cedula_identidad'] . '.';
            } else {
                $sqlUpdate = 'UPDATE entidad_gestion
                              SET nombres          = :nombres,
                                  apellidos        = :apellidos,
                                  cedula_identidad = :cedula,
                                  datos_contacto   = :contacto
                              WHERE id_registro = :id';

                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':nombres'   => $datos['nombres'],
                    ':apellidos' => $datos['apellidos'],
                    ':cedula'    => $datos['cedula_identidad'],
                    ':contacto'  => $datos['datos_contacto'],
                    ':id'        => $idRegistro,
                ]);

                establecerMensajeFlash(
                    'Los datos del paciente #' . $idRegistro . ' (' . $datos['nombres'] . ' ' . $datos['apellidos'] . ') se actualizaron correctamente.',
                    'success'
                );

                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $errores[] = 'Error en la base de datos al actualizar el paciente: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="form-section" aria-labelledby="titulo-editar-paciente">
    <div class="section-header">
        <div>
            <h1 id="titulo-editar-paciente" class="page-title">
                Editar Paciente #<?= $idRegistro ?>
            </h1>
            <p class="page-subtitle">
                Modifique los datos filiatorios o de contacto del expediente seleccionado.
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline">&larr; Volver al Listado</a>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger" role="alert">
            <div>
                <strong>Se encontraron errores en la actualización:</strong>
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
            action="paciente_editar.php?id=<?= $idRegistro ?>"
            method="POST"
            id="formEditarPaciente"
            onsubmit="return validarFormularioPaciente(this);"
            novalidate
        >
            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCsrf()) ?>">
            <input type="hidden" name="id_registro" value="<?= $idRegistro ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="nombres">Nombres del Paciente <span class="required">*</span></label>
                    <input
                        type="text"
                        id="nombres"
                        name="nombres"
                        class="form-control"
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
                        maxlength="50"
                        value="<?= e($datos['cedula_identidad']) ?>"
                        required
                    >
                    <small class="form-hint">Formato: V-12345678 o E-12345678.</small>
                </div>

                <div class="form-group">
                    <label for="info_auditoria">Registrado Originalmente Por</label>
                    <input
                        type="text"
                        id="info_auditoria"
                        class="form-control"
                        value="<?= e(($pacienteActual['responsable_username'] ?? 'Sistema') . ' — ' . date('d/m/Y H:i', strtotime((string) $pacienteActual['fecha_registro']))) ?>"
                        disabled
                    >
                    <small class="form-hint">Trazabilidad histórica del registro.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="datos_contacto">Datos de Contacto (Teléfono y Seguro Médico) <span class="required">*</span></label>
                <input
                    type="text"
                    id="datos_contacto"
                    name="datos_contacto"
                    class="form-control"
                    maxlength="255"
                    value="<?= e($datos['datos_contacto']) ?>"
                    required
                >
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

