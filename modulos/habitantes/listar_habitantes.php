<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);
$busqueda  = trim($_GET['q'] ?? '');

$sql = "SELECT h.*, TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) AS edad,
               f.nombre_familia, c.nombre AS comunidad
        FROM habitantes h
        INNER JOIN familias f ON h.familia_id = f.id
        INNER JOIN comunidades c ON h.comunidad_id = c.id
        WHERE h.activo = 1";

$condiciones = [];
$params = [];
$types  = '';

if ($comFiltro !== null) {
    $condiciones[] = "h.comunidad_id = ?";
    $params[] = $comFiltro;
    $types .= 'i';
}

if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $condiciones[] = "(h.cedula LIKE ? OR h.nombres LIKE ? OR h.apellidos LIKE ? OR f.nombre_familia LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if (!empty($condiciones)) {
    $sql .= " AND " . implode(" AND ", $condiciones);
}

$sql .= " ORDER BY h.apellidos ASC, h.nombres ASC";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    die('Error en consulta de habitantes: ' . e($conexion->error));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resHabitantes = $stmt->get_result();
$stmt->close();

$tituloPagina = 'Habitantes Activos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Censo General de Habitantes</h2>
        <p class="text-muted">Personas activas registradas en los núcleos familiares</p>
    </div>
    <a href="<?= e(base_url('modulos/habitantes/registrar_habitante.php')) ?>" class="btn btn-primary">+ Registrar Habitante</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado_ok'): ?>
    <div class="alert alert-success">Datos del habitante guardados correctamente.</div>
<?php endif; ?>

<div class="card">
    <form method="GET" class="inline-form" style="margin-bottom: 1rem;">
        <input type="text" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por cédula, nombre o familia..." style="min-width: 260px;">
        <button type="submit" class="btn btn-secondary">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a href="<?= e(base_url('modulos/habitantes/listar_habitantes.php')) ?>" class="btn btn-outline">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombres y Apellidos</th>
                    <th>Edad / Sexo</th>
                    <th>Parentesco</th>
                    <th>Familia</th>
                    <th>Comunidad</th>
                    <th>Indicadores</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resHabitantes && $resHabitantes->num_rows > 0): ?>
                    <?php while ($h = $resHabitantes->fetch_assoc()): ?>
                        <?php
                        $tags = [];
                        if ($h['es_estudiante']) $tags[] = 'Estudiante';
                        if ($h['posee_discapacidad']) $tags[] = 'Discapacidad';
                        if (!empty($h['enfermedad_cronica'])) $tags[] = 'Crónico';
                        if ($h['es_embarazada']) $tags[] = 'Embarazada';
                        if ($h['es_lactante']) $tags[] = 'Lactante';
                        ?>
                        <tr>
                            <td><?= e($h['cedula'] ? ($h['nacionalidad'] . '-' . $h['cedula']) : 'S/C') ?></td>
                            <td><strong><?= e($h['nombres'] . ' ' . $h['apellidos']) ?></strong></td>
                            <td><?= (int)$h['edad'] ?> años (<?= e($h['sexo']) ?>)</td>
                            <td><?= e($h['parentesco']) ?></td>
                            <td><?= e($h['nombre_familia']) ?></td>
                            <td><?= e($h['comunidad']) ?></td>
                            <td><?= e(!empty($tags) ? implode(', ', $tags) : '-') ?></td>
                            <td class="actions-cell">
                                <a href="<?= e(base_url('modulos/habitantes/editar_habitante.php?id=' . (int)$h['id'])) ?>" class="btn btn-sm">Editar</a>
                                <?php if ($h['es_estudiante']): ?>
                                    <a href="<?= e(base_url('modulos/estudiantes/guardar_estudiante.php?habitante_id=' . (int)$h['id'])) ?>" class="btn btn-sm btn-outline">Estudios</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron habitantes activos.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>