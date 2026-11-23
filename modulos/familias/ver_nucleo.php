<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$familiaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$comFiltro = comunidad_filtro(null);

$sqlFam = "SELECT f.*, c.nombre AS comunidad, v.numero_casa, v.tipo_vivienda, ca.nombre_calle
           FROM familias f
           INNER JOIN comunidades c ON f.comunidad_id = c.id
           INNER JOIN viviendas v ON f.vivienda_id = v.id
           LEFT JOIN calles ca ON v.calle_id = ca.id
           WHERE f.id = ?";
if ($comFiltro !== null) {
    $sqlFam .= " AND f.comunidad_id = " . (int)$comFiltro;
}
$stmtF = $conexion->prepare($sqlFam);
$stmtF->bind_param('i', $familiaId);
$stmtF->execute();
$familia = $stmtF->get_result()->fetch_assoc();
$stmtF->close();

if (!$familia) {
    header('Location: ' . base_url('modulos/familias/listar_familias.php'));
    exit;
}

$stmtH = $conexion->prepare("SELECT *, TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) AS edad FROM habitantes WHERE familia_id = ? ORDER BY (parentesco = 'Jefe de Familia') DESC, activo DESC, id ASC");
$stmtH->bind_param('i', $familiaId);
$stmtH->execute();
$integrantes = $stmtH->get_result();
$stmtH->close();

$tituloPagina = 'Núcleo Familiar: ' . $familia['nombre_familia'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2><?= e($familia['nombre_familia']) ?></h2>
        <p class="text-muted">
            Comunidad: <strong><?= e($familia['comunidad']) ?></strong> &mdash;
            Vivienda: <strong><?= e(($familia['nombre_calle'] ? $familia['nombre_calle'] . ' - ' : '') . 'Casa #' . $familia['numero_casa']) ?></strong>
        </p>
    </div>
    <div class="actions-row">
        <a href="<?= e(base_url('modulos/habitantes/registrar_habitante.php?familia_id=' . (int)$familia['id'])) ?>" class="btn btn-primary">+ Agregar Integrante</a>
        <a href="<?= e(base_url('carnet/carnet_jefe_familia.php?familia_id=' . (int)$familia['id'])) ?>" class="btn btn-secondary">Carnet Familiar</a>
        <a href="<?= e(base_url('modulos/familias/listar_familias.php')) ?>" class="btn btn-outline">Volver</a>
    </div>
</div>

<div class="card">
    <h3>Integrantes del Núcleo Familiar</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombre y Apellido</th>
                    <th>Parentesco</th>
                    <th>Edad</th>
                    <th>Sexo</th>
                    <th>Salud / Condición</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($integrantes && $integrantes->num_rows > 0): ?>
                    <?php while ($h = $integrantes->fetch_assoc()): ?>
                        <?php
                        $condiciones = [];
                        if ($h['posee_discapacidad']) $condiciones[] = 'Discapacidad' . ($h['tipo_discapacidad'] ? ': ' . $h['tipo_discapacidad'] : '');
                        if (!empty($h['enfermedad_cronica'])) $condiciones[] = 'Enf. crónica: ' . $h['enfermedad_cronica'];
                        if ($h['es_embarazada']) $condiciones[] = 'Embarazada';
                        if ($h['es_lactante']) $condiciones[] = 'Lactante';
                        ?>
                        <tr>
                            <td><?= e($h['cedula'] ? ($h['nacionalidad'] . '-' . $h['cedula']) : 'Sin Cédula') ?></td>
                            <td><strong><?= e($h['nombres'] . ' ' . $h['apellidos']) ?></strong></td>
                            <td><?= e($h['parentesco']) ?></td>
                            <td><?= (int)$h['edad'] ?> años</td>
                            <td><?= e($h['sexo']) ?></td>
                            <td><?= e(!empty($condiciones) ? implode(' | ', $condiciones) : 'Sin novedad') ?></td>
                            <td><?= (int)$h['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <a href="<?= e(base_url('modulos/habitantes/editar_habitante.php?id=' . (int)$h['id'])) ?>" class="btn btn-sm">Editar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Aún no hay integrantes registrados en esta familia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>