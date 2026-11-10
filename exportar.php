<?php
/**
 * Archivo: exportar.php
 * Descripción: Exportación nativa a CSV/Excel del listado de pacientes y usuario responsable.
 * Sistema: Clínica Salud Total C.A.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$busqueda = trim((string) ($_GET['q'] ?? ''));

try {
    if ($busqueda !== '') {
        $sql = 'SELECT p.id_registro,
                       p.cedula_identidad,
                       p.nombres,
                       p.apellidos,
                       p.datos_contacto,
                       p.fecha_registro,
                       u.username AS usuario_responsable,
                       r.nombre_rol AS rol_responsable
                FROM entidad_gestion p
                LEFT JOIN usuarios u ON p.id_usuario_responsable = u.id_usuario
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                WHERE p.nombres LIKE :q1
                   OR p.apellidos LIKE :q2
                   OR p.cedula_identidad LIKE :q3
                   OR p.datos_contacto LIKE :q4
                   OR u.username LIKE :q5
                ORDER BY p.fecha_registro DESC, p.id_registro DESC';

        $stmt = $pdo->prepare($sql);
        $like = '%' . $busqueda . '%';
        $stmt->execute([
            ':q1' => $like,
            ':q2' => $like,
            ':q3' => $like,
            ':q4' => $like,
            ':q5' => $like,
        ]);
    } else {
        $sql = 'SELECT p.id_registro,
                       p.cedula_identidad,
                       p.nombres,
                       p.apellidos,
                       p.datos_contacto,
                       p.fecha_registro,
                       u.username AS usuario_responsable,
                       r.nombre_rol AS rol_responsable
                FROM entidad_gestion p
                LEFT JOIN usuarios u ON p.id_usuario_responsable = u.id_usuario
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                ORDER BY p.fecha_registro DESC, p.id_registro DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $registros = $stmt->fetchAll();

    // Configurar cabeceras HTTP para descarga directa en Excel / CSV
    $nombreArchivo = 'reporte_pacientes_salud_total_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $salida = fopen('php://output', 'wb');

    if ($salida === false) {
        throw new RuntimeException('No fue posible abrir el flujo de salida para generar el archivo CSV.');
    }

    // Insertar BOM UTF-8 para que Microsoft Excel reconozca tildes y eñes automáticamente
    fwrite($salida, "\xEF\xBB\xBF");

    // Escribir fila de encabezados de columnas (usando punto y coma o coma estándar)
    fputcsv($salida, [
        'ID Registro',
        'Cedula de Identidad',
        'Nombres',
        'Apellidos',
        'Datos de Contacto y Seguro Medico',
        'Fecha de Registro',
        'Usuario Responsable',
        'Rol del Responsable',
    ]);

    // Escribir filas de datos de cada paciente
    foreach ($registros as $fila) {
        fputcsv($salida, [
            $fila['id_registro'],
            $fila['cedula_identidad'],
            $fila['nombres'],
            $fila['apellidos'],
            $fila['datos_contacto'] ?? 'No especificado',
            date('d/m/Y H:i:s', strtotime((string) $fila['fecha_registro'])),
            $fila['usuario_responsable'] ?? 'Sistema',
            $fila['rol_responsable'] ?? 'No asignado',
        ]);
    }

    fclose($salida);
    exit;
} catch (PDOException $e) {
    establecerMensajeFlash('Error al generar el reporte CSV: ' . $e->getMessage(), 'danger');
    header('Location: dashboard.php');
    exit;
}

