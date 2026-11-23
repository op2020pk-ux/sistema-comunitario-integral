<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM calles ca WHERE ca.comunidad_id = c.id) AS total_calles,
               (SELECT COUNT(*) FROM viviendas v WHERE v.comunidad_id = c.id) AS total_viviendas,
               (SELECT COUNT(*) FROM familias f WHERE f.comunidad_id = c.id) AS total_familias
        FROM comunidades c";

if ($comFiltro !== null) {
    $sql .= " WHERE c.id = " . (int)$comFiltro;
}

$sql .= " ORDER BY c.nombre ASC";
$resComunidades = $conexion->query($sql);

if (!$resComunidades) {
    die('Error al consultar comunidades: ' . e($conexion->error));
}

$tituloPagina = 'Comunidades';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Comunidades / Consejos Comunales</h2>
        <p class="text-muted">Registro y consulta territorial de comunidades</p>
    </div>
    <?php if (es_superadmin()): ?>
        <a href="<?= e(base_url('modulos/comunidades/guardar_comunidad.php')) ?>" class="btn btn-primary">+ Nueva Comunidad</a>
    <?php endif; ?>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado_ok'): ?>
    <div class="alert alert-success">Comunidad guardada correctamente.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>RIF / Código</th>
                    <th>Parroquia / Municipio / Estado</th>
                    <th>Calles</th>
                    <th>Viviendas</th>
                    <th>Familias</th>
                    <?php if (es_superadmin()): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($resComunidades->num_rows > 0): ?>
                    <?php while ($com = $resComunidades->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$com['id'] ?></td>
                            <td><strong><?= e($com['nombre']) ?></strong></td>
                            <td><?= e($com['rif_o_codigo'] ?: 'N/A') ?></td>
                            <td><?= e(implode(' / ', array_filter([$com['parroquia'], $com['municipio'], $com['estado']]))) ?></td>
                            <td><?= (int)$com['total_calles'] ?></td>
                            <td><?= (int)$com['total_viviendas'] ?></td>
                            <td><?= (int)$com['total_familias'] ?></td>
                            <?php if (es_superadmin()): ?>
                                <td>
                                    <a href="<?= e(base_url('modulos/comunidades/guardar_comunidad.php?id=' . (int)$com['id'])) ?>" class="btn btn-sm">Editar</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No hay comunidades registradas todavía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>