<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$sql = "SELECT be.*, f.nombre_familia, f.jefe_familia_cedula, c.nombre AS comunidad
        FROM beneficios_entregados be
        INNER JOIN familias f ON be.familia_id = f.id
        INNER JOIN comunidades c ON be.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sql .= " WHERE be.comunidad_id = " . (int)$comFiltro;
}
$sql .= " ORDER BY be.fecha_entrega DESC, be.id DESC";
$resBeneficios = $conexion->query($sql);

$tituloPagina = 'Control de Beneficios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Control de Entrega de Beneficios</h2>
        <p class="text-muted">Historial de bolsas CLAP, cilindros de gas, medicamentos y jornadas sociales</p>
    </div>
    <a href="<?= e(base_url('modulos/beneficios/registrar_entrega.php')) ?>" class="btn btn-primary">+ Registrar Entrega</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado_ok'): ?>
    <div class="alert alert-success">Entrega de beneficio registrada correctamente.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comunidad</th>
                    <th>Familia Beneficiada</th>
                    <th>C.I. Jefe</th>
                    <th>Tipo de Beneficio</th>
                    <th>Cantidad</th>
                    <th>Entregado por</th>
                    <th>Observación</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resBeneficios && $resBeneficios->num_rows > 0): ?>
                    <?php while ($b = $resBeneficios->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($b['fecha_entrega']) ?></td>
                            <td><?= e($b['comunidad']) ?></td>
                            <td><strong><?= e($b['nombre_familia']) ?></strong></td>
                            <td><?= e($b['jefe_familia_cedula'] ?: 'S/C') ?></td>
                            <td><?= e($b['tipo_beneficio']) ?></td>
                            <td><?= (int)$b['cantidad'] ?></td>
                            <td><?= e($b['entregado_por'] ?: '-') ?></td>
                            <td><?= e($b['observacion'] ?: '-') ?></td>
                            <td>
                                <a href="<?= e(base_url('modulos/beneficios/registrar_entrega.php?id=' . (int)$b['id'])) ?>" class="btn btn-sm">Editar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No hay entregas registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>