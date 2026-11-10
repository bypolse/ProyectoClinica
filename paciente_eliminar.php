<?php
/**
 * Archivo: paciente_eliminar.php
 * Descripción: Eliminación segura de pacientes (restringida exclusivamente al rol Administrador).
 * Sistema: Clínica Salud Total C.A.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Control estricto de roles: solo el Administrador puede ejecutar esta acción
requerirAdministrador();

$idRegistro = (int) ($_POST['id_registro'] ?? $_GET['id'] ?? 0);

if ($idRegistro <= 0) {
    establecerMensajeFlash('Identificador de paciente no válido para eliminar.', 'warning');
    header('Location: dashboard.php');
    exit;
}

// Consultar que el paciente exista antes de proceder
try {
    $stmtBuscar = $pdo->prepare(
        'SELECT id_registro, nombres, apellidos, cedula_identidad, datos_contacto, fecha_registro
         FROM entidad_gestion
         WHERE id_registro = :id
         LIMIT 1'
    );
    $stmtBuscar->execute([':id' => $idRegistro]);
    $paciente = $stmtBuscar->fetch();

    if (!$paciente) {
        establecerMensajeFlash('El registro del paciente indicado no existe o ya fue eliminado.', 'warning');
        header('Location: dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    establecerMensajeFlash('Error al consultar el registro para eliminar: ' . $e->getMessage(), 'danger');
    header('Location: dashboard.php');
    exit;
}

// Procesar la eliminación definitiva cuando se recibe petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = (string) ($_POST['csrf_token'] ?? '');
    if (!validarTokenCsrf($tokenRecibido)) {
        establecerMensajeFlash('Token de seguridad inválido al intentar eliminar el registro.', 'danger');
        header('Location: dashboard.php');
        exit;
    }

    try {
        $stmtDelete = $pdo->prepare('DELETE FROM entidad_gestion WHERE id_registro = :id');
        $stmtDelete->execute([':id' => $idRegistro]);

        establecerMensajeFlash(
            'El expediente del paciente ' . $paciente['nombres'] . ' ' . $paciente['apellidos']
            . ' (C.I.: ' . $paciente['cedula_identidad'] . ') fue eliminado correctamente.',
            'info'
        );
    } catch (PDOException $e) {
        establecerMensajeFlash('Error en la base de datos al eliminar el paciente: ' . $e->getMessage(), 'danger');
    }

    header('Location: dashboard.php');
    exit;
}

// Si se accede por GET (paciente_eliminar.php?id=X), mostrar pantalla de confirmación segura
$tituloPagina = 'Confirmar Eliminación de Paciente';
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-section" aria-labelledby="titulo-confirmar-eliminar">
    <article class="card confirm-card">
        <h1 id="titulo-confirmar-eliminar" class="page-title text-danger">
            Confirmar Eliminación de Paciente
        </h1>
        <p class="page-subtitle">
            ¿Está seguro de que desea eliminar de forma permanente el siguiente registro de <strong>Clínica Salud Total C.A.</strong>?
        </p>

        <div class="patient-summary-box">
            <p><strong>ID Registro:</strong> #<?= (int) $paciente['id_registro'] ?></p>
            <p><strong>Paciente:</strong> <?= e($paciente['nombres'] . ' ' . $paciente['apellidos']) ?></p>
            <p><strong>Cédula de Identidad:</strong> <?= e($paciente['cedula_identidad']) ?></p>
            <p><strong>Datos de Contacto:</strong> <?= e($paciente['datos_contacto'] ?? 'No especificado') ?></p>
            <p><strong>Fecha de Registro:</strong> <?= e(date('d/m/Y H:i', strtotime((string) $paciente['fecha_registro']))) ?></p>
        </div>

        <form action="paciente_eliminar.php" method="POST" class="form-actions">
            <input type="hidden" name="id_registro" value="<?= (int) $paciente['id_registro'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCsrf()) ?>">
            <a href="dashboard.php" class="btn btn-outline">Cancelar y Volver</a>
            <button type="submit" class="btn btn-danger">Confirmar Eliminación Definitiva</button>
        </form>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

