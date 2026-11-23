<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$sql = "SELECT e.*, h.nacionalidad, h.cedula, h.nombres, h.apellidos,
               TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) AS edad,
               f.nombre_familia, c.nombre AS comunidad
        FROM estudiantes e
        INNER JOIN habitantes h ON e.habitante_id = h.id
        INNER JOIN familias f ON h.familia_id = f.id
        INNER JOIN comunidades c ON h.comunidad_id = c.id
        WHERE h.activo = 1";
if ($comFiltro !== null) {
    $sql .= " AND h.comunidad_id = " . (int)$comFiltro;
}
$sql .= " ORDER BY e.nivel_educativo ASC, h.apellidos ASC";
$resEstudiantes = $conexion->query($sql);

$tituloPagina = 'Padrón Estudiantil';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Padrón Escolar y Estudiantil</h2>
        <p class="text-muted">Control académico por nivel educativo y requerimientos de útiles/becas</p>
    </div>
    <a href="<?= e(base_url('modulos/estudiantes/guardar_estudiante.php')) ?>" class="btn btn-primary">+ Vincular Estudiante</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado_ok'): ?>
    <div class="alert alert-success">Datos académicos guardados correctamente.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Estudiante</th>
                    <th>Edad</th>
                    <th>Nivel / Grado</th>
                    <th>Institución</th>
                    <th>Tipo</th>
                    <th>Beca / Útiles</th>
                    <th>Comunidad</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resEstudiantes && $resEstudiantes->num_rows > 0): ?>
                    <?php while ($st = $resEstudiantes->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($st['cedula'] ? ($st['nacionalidad'] . '-' . $st['cedula']) : 'S/C') ?></td>
                            <td><strong><?= e($st['nombres'] . ' ' . $st['apellidos']) ?></strong></td>
                            <td><?= (int)$st['edad'] ?> años</td>
                            <td><?= e($st['nivel_educativo'] . ($st['grado_o_semestre'] ? ' (' . $st['grado_o_semestre'] . ')' : '')) ?></td>
                            <td><?= e($st['nombre_institucion'] ?: 'No indicada') ?></td>
                            <td><?= e($st['tipo_institucion']) ?></td>
                            <td><?= (int)$st['requiere_beca_o_utiles'] === 1 ? 'Sí requiere' : 'No' ?></td>
                            <td><?= e($st['comunidad']) ?></td>
                            <td>
                                <a href="<?= e(base_url('modulos/estudiantes/guardar_estudiante.php?habitante_id=' . (int)$st['habitante_id'])) ?>" class="btn btn-sm">Editar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No hay estudiantes registrados todavía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>