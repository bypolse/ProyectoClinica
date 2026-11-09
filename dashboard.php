<?php
/**
 * Archivo: dashboard.php
 * Descripción: Panel principal con tarjetas de estadísticas, buscador y tabla CRUD de pacientes.
 * Sistema: Clínica Salud Total C.A. - IUJO (Algoritmo y Programación II)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$tituloPagina = 'Panel de Control de Pacientes';
$busqueda     = trim((string) ($_GET['q'] ?? ''));

// Inicialización de contadores estadísticos
$totalPacientes       = 0;
$registradosHoy       = 0;
$registradosPorMi     = 0;
$listaPacientes       = [];
$errorConsulta        = '';

try {
    // 1. Total general de pacientes registrados
    $stmtTotal = $pdo->query('SELECT COUNT(*) FROM entidad_gestion');
    $totalPacientes = (int) $stmtTotal->fetchColumn();

    // 2. Pacientes registrados en la fecha actual
    $stmtHoy = $pdo->query('SELECT COUNT(*) FROM entidad_gestion WHERE DATE(fecha_registro) = CURDATE()');
    $registradosHoy = (int) $stmtHoy->fetchColumn();

    // 3. Pacientes registrados por el usuario en sesión
    $stmtMios = $pdo->prepare('SELECT COUNT(*) FROM entidad_gestion WHERE id_usuario_responsable = :id_user');
    $stmtMios->execute([':id_user' => (int) $_SESSION['id_usuario']]);
    $registradosPorMi = (int) $stmtMios->fetchColumn();

    // 4. Listado de pacientes (con filtro de búsqueda opcional mediante Prepared Statement)
    if ($busqueda !== '') {
        $sqlListado = 'SELECT p.id_registro,
                              p.id_usuario_responsable,
                              p.nombres,
                              p.apellidos,
                              p.cedula_identidad,
                              p.datos_contacto,
                              p.fecha_registro,
                              u.username AS responsable_username
                       FROM entidad_gestion p
                       LEFT JOIN usuarios u ON p.id_usuario_responsable = u.id_usuario
                       WHERE p.nombres LIKE :q1
                          OR p.apellidos LIKE :q2
                          OR p.cedula_identidad LIKE :q3
                          OR p.datos_contacto LIKE :q4
                          OR u.username LIKE :q5
                       ORDER BY p.fecha_registro DESC, p.id_registro DESC';

        $stmtListado = $pdo->prepare($sqlListado);
        $terminoLike = '%' . $busqueda . '%';
        $stmtListado->execute([
            ':q1' => $terminoLike,
            ':q2' => $terminoLike,
            ':q3' => $terminoLike,
            ':q4' => $terminoLike,
            ':q5' => $terminoLike,
        ]);
    } else {
        $sqlListado = 'SELECT p.id_registro,
                              p.id_usuario_responsable,
                              p.nombres,
                              p.apellidos,
                              p.cedula_identidad,
                              p.datos_contacto,
                              p.fecha_registro,
                              u.username AS responsable_username
                       FROM entidad_gestion p
                       LEFT JOIN usuarios u ON p.id_usuario_responsable = u.id_usuario
                       ORDER BY p.fecha_registro DESC, p.id_registro DESC';

        $stmtListado = $pdo->prepare($sqlListado);
        $stmtListado->execute();
    }

    $listaPacientes = $stmtListado->fetchAll();
} catch (PDOException $e) {
    $errorConsulta = 'Ocurrió un error al consultar los registros de pacientes: ' . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="dashboard-header no-print" aria-label="Encabezado de gestión">
    <div>
        <h1 class="page-title">Panel de Gestión de Pacientes</h1>
        <p class="page-subtitle">
            Control filiatorio, pólizas de seguro médico y trazabilidad de atención de <strong>Clínica Salud Total C.A.</strong>
        </p>
    </div>
    <div class="header-actions">
        <a href="paciente_crear.php" class="btn btn-primary">
            + Registrar Paciente
        </a>
        <a href="exportar.php<?= $busqueda !== '' ? '?q=' . urlencode($busqueda) : '' ?>" class="btn btn-secondary" title="Descargar reporte en formato Excel/CSV">
            Exportar CSV / Excel
        </a>
        <button type="button" class="btn btn-outline" onclick="window.print();" title="Imprimir o guardar como PDF">
            Imprimir / PDF
        </button>
    </div>
</section>

<?php if ($errorConsulta !== ''): ?>
    <div class="alert alert-danger" role="alert">
        <span><?= e($errorConsulta) ?></span>
    </div>
<?php endif; ?>

<!-- Tarjetas de Estadísticas -->
<section class="stats-grid no-print" aria-label="Indicadores estadísticos">
    <article class="stat-card">
        <div class="stat-label">Total de Pacientes</div>
        <div class="stat-value"><?= $totalPacientes ?></div>
        <div class="stat-desc">Expedientes activos en el sistema</div>
    </article>

    <article class="stat-card stat-card-accent">
        <div class="stat-label">Ingresos de Hoy</div>
        <div class="stat-value"><?= $registradosHoy ?></div>
        <div class="stat-desc">Pacientes registrados el <?= date('d/m/Y') ?></div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Mis Registros</div>
        <div class="stat-value"><?= $registradosPorMi ?></div>
        <div class="stat-desc">Atendidos por <strong><?= e($_SESSION['username']) ?></strong></div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Nivel de Acceso</div>
        <div class="stat-value stat-role"><?= e($_SESSION['nombre_rol']) ?></div>
        <div class="stat-desc">
            <?= esAdministrador() ? 'Permisos completos (CRUD + Eliminación)' : 'Gestión operativa (Sin eliminación)' ?>
        </div>
    </article>
</section>

<!-- Barra de Búsqueda y Tabla CRUD -->
<section class="card" aria-labelledby="tabla-pacientes-titulo">
    <div class="card-toolbar no-print">
        <h2 id="tabla-pacientes-titulo" class="card-title">
            Listado de Pacientes
            <span class="badge-count"><?= count($listaPacientes) ?> registro(s)</span>
        </h2>

        <form action="dashboard.php" method="GET" class="search-form" role="search">
            <input
                type="search"
                name="q"
                class="form-control search-input"
                placeholder="Buscar por cédula, nombre, seguro o responsable..."
                value="<?= e($busqueda) ?>"
                aria-label="Buscar pacientes"
            >
            <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="dashboard.php" class="btn btn-outline btn-sm">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cédula</th>
                    <th>Paciente (Nombres y Apellidos)</th>
                    <th>Datos de Contacto y Seguro Médico</th>
                    <th>Responsable</th>
                    <th>Fecha de Registro</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($listaPacientes)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <?php if ($busqueda !== ''): ?>
                                No se encontraron pacientes que coincidan con la búsqueda <strong>"<?= e($busqueda) ?>"</strong>.
                            <?php else: ?>
                                Aún no existen pacientes registrados en la base de datos.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($listaPacientes as $paciente): ?>
                        <?php
                            $idRegistro     = (int) $paciente['id_registro'];
                            $nombreCompleto = trim($paciente['nombres'] . ' ' . $paciente['apellidos']);
                            $fechaFormateada = date('d/m/Y H:i', strtotime((string) $paciente['fecha_registro']));
                        ?>
                        <tr>
                            <td class="col-id">#<?= $idRegistro ?></td>
                            <td>
                                <span class="cedula-badge"><?= e($paciente['cedula_identidad']) ?></span>
                            </td>
                            <td class="col-paciente">
                                <strong><?= e($paciente['apellidos']) ?></strong>, <?= e($paciente['nombres']) ?>
                            </td>
                            <td><?= e($paciente['datos_contacto'] ?: 'No especificado') ?></td>
                            <td>
                                <span class="user-tag">
                                    <?= e($paciente['responsable_username'] ?? 'Sistema') ?>
                                </span>
                            </td>
                            <td class="col-fecha"><?= e($fechaFormateada) ?></td>
                            <td class="no-print col-acciones">
                                <div class="action-buttons">
                                    <a
                                        href="paciente_editar.php?id=<?= $idRegistro ?>"
                                        class="btn btn-secondary btn-sm"
                                        title="Editar datos del paciente"
                                    >
                                        Editar
                                    </a>

                                    <?php if (esAdministrador()): ?>
                                        <form
                                            action="paciente_eliminar.php"
                                            method="POST"
                                            class="inline-form"
                                            onsubmit="return confirmarEliminacion(<?= e((string) json_encode($nombreCompleto)) ?>, <?= e((string) json_encode($paciente['cedula_identidad'])) ?>);"
                                        >
                                            <input type="hidden" name="id_registro" value="<?= $idRegistro ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCsrf()) ?>">
                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                                title="Eliminar registro (Solo Administrador)"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="action-locked" title="Solo el Administrador puede eliminar pacientes">
                                            Bloqueado
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

