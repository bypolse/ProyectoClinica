<?php
/**
 * Archivo: includes/header.php
 * Descripción: Encabezado HTML5 semántico y barra de navegación principal.
 * Sistema: Clínica Salud Total C.A. - IUJO (Algoritmo y Programación II)
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$tituloPagina = $tituloPagina ?? 'Sistema de Gestión de Pacientes';
$paginaActual = basename($_SERVER['PHP_SELF'] ?? '');
$usuarioActual = $_SESSION['username'] ?? 'Invitado';
$rolActual     = $_SESSION['nombre_rol'] ?? 'Sin Rol';
$flash         = obtenerMensajeFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema Web de Gestión de Pacientes - Clínica Salud Total C.A. (IUJO)">
    <title><?= e($tituloPagina) ?> | Clínica Salud Total C.A.</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header no-print">
        <div class="container navbar-wrapper">
            <a href="dashboard.php" class="brand-logo" title="Ir al Panel Principal">
                <span class="brand-icon" aria-hidden="true">+</span>
                <div class="brand-text">
                    <span class="brand-title">Clínica Salud Total C.A.</span>
                    <span class="brand-subtitle">Gestión Integral de Pacientes</span>
                </div>
            </a>

            <?php if (!empty($_SESSION['id_usuario'])): ?>
                <nav class="main-nav" aria-label="Navegación Principal">
                    <a href="dashboard.php" class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>">
                        Panel Principal
                    </a>
                    <a href="paciente_crear.php" class="nav-link <?= $paginaActual === 'paciente_crear.php' ? 'active' : '' ?>">
                        + Nuevo Paciente
                    </a>
                    <a href="exportar.php" class="nav-link" title="Descargar listado en formato CSV/Excel">
                        Exportar CSV
                    </a>
                </nav>

                <div class="user-panel">
                    <div class="user-info">
                        <span class="user-name"><?= e($usuarioActual) ?></span>
                        <span class="role-badge <?= esAdministrador() ? 'role-admin' : 'role-empleado' ?>">
                            <?= e($rolActual) ?>
                        </span>
                    </div>
                    <a href="logout.php" class="btn btn-logout" title="Cerrar sesión de forma segura">
                        Salir
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Encabezado exclusivo para vista de impresión / PDF -->
    <div class="print-only print-header">
        <h1>Clínica Salud Total C.A.</h1>
        <p>Reporte Oficial de Pacientes Registrados — Generado el <?= date('d/m/Y H:i') ?> por <?= e($usuarioActual) ?> (<?= e($rolActual) ?>)</p>
        <hr>
    </div>

    <main class="container main-content">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= e($flash['tipo']) ?> no-print" role="alert">
                <span><?= e($flash['texto']) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove();" aria-label="Cerrar alerta">&times;</button>
            </div>
        <?php endif; ?>

