<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$sql = "SELECT v.*, c.nombre AS comunidad, ca.nombre_calle,
               (SELECT COUNT(*) FROM familias f WHERE f.vivienda_id = v.id) AS total_familias
        FROM viviendas v
        INNER JOIN comunidades c ON v.comunidad_id = c.id
        LEFT JOIN calles ca ON v.calle_id = ca.id";
if ($comFiltro !== null) {
    $sql .= " WHERE v.comunidad_id = " . (int)$comFiltro;
}
$sql .= " ORDER BY c.nombre ASC, ca.nombre_calle ASC, v.numero_casa ASC";
$resViviendas = $conexion->query($sql);

$tituloPagina = 'Viviendas';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Viviendas y Servicios Básicos</h2>
        <p class="text-muted">Inventario de casas, apartamentos, anexos y condiciones estructurales</p>
    </div>
    <a href="<?= e(base_url('modulos/viviendas/guardar_vivienda.php')) ?>" class="btn btn-primary">+ Registrar Vivienda</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado_ok'): ?>
    <div class="alert alert-success">Vivienda registrada/actualizada correctamente.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Comunidad</th>
                    <th>Calle</th>
                    <th>N° Casa</th>
                    <th>Tipo / Tenencia</th>
                    <th>Estructura</th>
                    <th>Servicios</th>
                    <th>Familias</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resViviendas && $resViviendas->num_rows > 0): ?>
                    <?php while ($v = $resViviendas->fetch_assoc()): ?>
                        <?php
                        $servicios = [];
                        if ($v['posee_agua']) $servicios[] = 'Agua';
                        if ($v['posee_electricidad']) $servicios[] = 'Luz';
                        if ($v['posee_gas_directo']) $servicios[] = 'Gas directo';
                        if ($v['posee_internet']) $servicios[] = 'Internet';
                        ?>
                        <tr>
                            <td><?= e($v['comunidad']) ?></td>
                            <td><?= e($v['nombre_calle'] ?: 'Sin calle') ?></td>
                            <td><strong><?= e($v['numero_casa']) ?></strong></td>
                            <td><?= e($v['tipo_vivienda']) ?> / <?= e($v['tenencia']) ?></td>
                            <td><?= e($v['condicion_estructura']) ?></td>
                            <td><?= e(!empty($servicios) ? implode(', ', $servicios) : 'Ninguno') ?></td>
                            <td><?= (int)$v['total_familias'] ?></td>
                            <td>
                                <a href="<?= e(base_url('modulos/viviendas/guardar_vivienda.php?id=' . (int)$v['id'])) ?>" class="btn btn-sm">Editar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No hay viviendas censadas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>