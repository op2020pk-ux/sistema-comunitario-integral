<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(null);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $habitanteId        = (int)($_POST['habitante_id'] ?? 0);
    $fechaFallecimiento = $_POST['fecha_fallecimiento'] ?? '';
    $causa              = trim($_POST['causa_fallecimiento'] ?? '');
    $observaciones      = trim($_POST['observaciones'] ?? '');

    if ($habitanteId <= 0 || $fechaFallecimiento === '') {
        $error = 'Debes seleccionar el habitante y la fecha de fallecimiento.';
    } else {
        $stmtH = $conexion->prepare("SELECT comunidad_id, familia_id, activo FROM habitantes WHERE id = ? LIMIT 1");
        $stmtH->bind_param('i', $habitanteId);
        $stmtH->execute();
        $hab = $stmtH->get_result()->fetch_assoc();
        $stmtH->close();

        if (!$hab || ($comFiltro !== null && (int)$hab['comunidad_id'] !== $comFiltro)) {
            $error = 'El habitante seleccionado no es válido.';
        } else {
            // Al insertar en fallecidos, el Trigger `after_insert_fallecido` actualiza `habitantes.activo = FALSE`
            $stmtIns = $conexion->prepare("INSERT INTO fallecidos (comunidad_id, familia_id, habitante_id, fecha_fallecimiento, causa_fallecimiento, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtIns->bind_param(
                'iiisss',
                $hab['comunidad_id'],
                $hab['familia_id'],
                $habitanteId,
                $fechaFallecimiento,
                $causa,
                $observaciones
            );

            if ($stmtIns->execute()) {
                $stmtIns->close();
                header('Location: ' . base_url('modulos/fallecidos/listar_fallecidos.php?msg=registrado_ok'));
                exit;
            } else {
                $error = 'Error al registrar defunción (es posible que ya esté registrado): ' . $stmtIns->error;
                $stmtIns->close();
            }
        }
    }
}

$sqlHab = "SELECT h.id, h.nacionalidad, h.cedula, h.nombres, h.apellidos, f.nombre_familia, c.nombre AS comunidad
           FROM habitantes h
           INNER JOIN familias f ON h.familia_id = f.id
           INNER JOIN comunidades c ON h.comunidad_id = c.id
           WHERE h.activo = 1";
if ($comFiltro !== null) {
    $sqlHab .= " AND h.comunidad_id = " . (int)$comFiltro;
}
$sqlHab .= " ORDER BY h.apellidos ASC, h.nombres ASC";
$habitantesActivos = $conexion->query($sqlHab);

$tituloPagina = 'Registrar Defunción';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Registrar Defunción</h2>
        <p class="text-muted">Al guardar, el sistema desactivará automáticamente al habitante del censo activo</p>
    </div>
    <a href="<?= e(base_url('modulos/fallecidos/listar_fallecidos.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" class="form-grid">
        <div class="form-group full-width">
            <label for="habitante_id">Habitante activo *</label>
            <select name="habitante_id" id="habitante_id" required>
                <option value="">-- Selecciona la persona --</option>
                <?php if ($habitantesActivos): ?>
                    <?php while ($hb = $habitantesActivos->fetch_assoc()): ?>
                        <option value="<?= (int)$hb['id'] ?>">
                            <?= e($hb['apellidos'] . ', ' . $hb['nombres'] . ' (' . ($hb['cedula'] ? $hb['nacionalidad'] . '-' . $hb['cedula'] : 'S/C') . ') - ' . $hb['nombre_familia'] . ' [' . $hb['comunidad'] . ']') ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="fecha_fallecimiento">Fecha de fallecimiento *</label>
            <input type="date" name="fecha_fallecimiento" id="fecha_fallecimiento" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="causa_fallecimiento">Causa de fallecimiento</label>
            <input type="text" name="causa_fallecimiento" id="causa_fallecimiento" placeholder="Ej. Causas naturales, infarto, etc.">
        </div>

        <div class="form-group full-width">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" rows="3"></textarea>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-danger">Registrar Defunción</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>