<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$sql = "SELECT f.*, c.nombre AS comunidad, v.numero_casa, ca.nombre_calle,
               (SELECT COUNT(*) FROM habitantes h WHERE h.familia_id = f.id AND h.activo = 1) AS integrantes_activos
        FROM familias f
        INNER JOIN comunidades c ON f.comunidad_id = c.id
        INNER JOIN viviendas v ON f.vivienda_id = v.id
        LEFT JOIN calles ca ON v.calle_id = ca.id";
if ($comFiltro !== null) {
    $sql .= " WHERE f.comunidad_id = " . (int)$comFiltro;
}
$sql .= " ORDER BY c.nombre ASC, f.nombre_familia ASC";
$resFamilias = $conexion->query($sql);

$tituloPagina = 'Núcleos Familiares';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Núcleos Familiares</h2>
        <p class="text-muted">Familias censadas vinculadas a cada vivienda</p>
    </div>
    <a href="<?= e(base_url('modulos/familias/registrar_familia.php')) ?>" class="btn btn-primary">+ Nueva Familia</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado_ok'): ?>
    <div class="alert alert-success">Núcleo familiar guardado correctamente.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Familia</th>
                    <th>C.I. Jefe</th>
                    <th>Comunidad</th>
                    <th>Vivienda</th>
                    <th>Cilindros</th>
                    <th>Integrantes</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resFamilias && $resFamilias->num_rows > 0): ?>
                    <?php while ($fam = $resFamilias->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= e($fam['nombre_familia']) ?></strong></td>
                            <td><?= e($fam['jefe_familia_cedula'] ?: 'Pendiente') ?></td>
                            <td><?= e($fam['comunidad']) ?></td>
                            <td><?= e(($fam['nombre_calle'] ? $fam['nombre_calle'] . ' - ' : '') . 'Casa #' . $fam['numero_casa']) ?></td>
                            <td><?= (int)$fam['cantidad_cilindros_gas'] ?></td>
                            <td><?= (int)$fam['integrantes_activos'] ?></td>
                            <td class="actions-cell">
                                <a href="<?= e(base_url('modulos/familias/ver_nucleo.php?id=' . (int)$fam['id'])) ?>" class="btn btn-sm">Ver Núcleo</a>
                                <a href="<?= e(base_url('modulos/familias/registrar_familia.php?id=' . (int)$fam['id'])) ?>" class="btn btn-sm btn-outline">Editar</a>
                                <a href="<?= e(base_url('carnet/carnet_jefe_familia.php?familia_id=' . (int)$fam['id'])) ?>" class="btn btn-sm btn-secondary">Carnet</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay familias registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>