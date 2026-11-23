<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$sql = "SELECT * FROM vista_fallecidos_detalle";
if ($comFiltro !== null) {
    $sql .= " WHERE comunidad_id = " . (int)$comFiltro;
}
$sql .= " ORDER BY fecha_fallecimiento DESC, fallecimiento_id DESC";
$resFallecidos = $conexion->query($sql);

$tituloPagina = 'Registro de Fallecidos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Historial de Familiares Fallecidos</h2>
        <p class="text-muted">Registro histórico con año, mes y edad al momento del fallecimiento</p>
    </div>
    <a href="<?= e(base_url('modulos/fallecidos/registrar_fallecido.php')) ?>" class="btn btn-primary">+ Registrar Defunción</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'registrado_ok'): ?>
    <div class="alert alert-success">Defunción registrada. El habitante fue desactivado automáticamente del censo activo.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombre del Fallecido</th>
                    <th>Familia / Comunidad</th>
                    <th>Fecha</th>
                    <th>Año / Mes</th>
                    <th>Edad al Fallecer</th>
                    <th>Causa</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resFallecidos && $resFallecidos->num_rows > 0): ?>
                    <?php while ($fa = $resFallecidos->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($fa['cedula']) ?></td>
                            <td><strong><?= e($fa['nombre_fallecido']) ?></strong></td>
                            <td><?= e($fa['familia']) ?> &mdash; <small><?= e($fa['comunidad']) ?></small></td>
                            <td><?= e($fa['fecha_fallecimiento']) ?></td>
                            <td><?= (int)$fa['anio_fallecimiento'] ?> / <?= e($fa['mes_fallecimiento']) ?></td>
                            <td><?= (int)$fa['edad_al_fallecer'] ?> años</td>
                            <td><?= e($fa['causa_fallecimiento'] ?: 'No especificada') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay defunciones registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>