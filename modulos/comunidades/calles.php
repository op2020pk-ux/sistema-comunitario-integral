<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);
$error = '';
$msg = '';

// Lista comunidades disponibles según rol
if (es_superadmin()) {
    $resCom = $conexion->query("SELECT id, nombre FROM comunidades ORDER BY nombre ASC");
} else {
    $cid = (int)($_SESSION['comunidad_id'] ?? 0);
    $resCom = $conexion->query("SELECT id, nombre FROM comunidades WHERE id = {$cid}");
}
$comunidades = [];
while ($r = $resCom->fetch_assoc()) {
    $comunidades[] = $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && es_admin_comunidad()) {
    $calleId     = (int)($_POST['calle_id'] ?? 0);
    $comIdPost   = es_superadmin() ? (int)($_POST['comunidad_id'] ?? 0) : (int)($_SESSION['comunidad_id'] ?? 0);
    $nombreCalle = trim($_POST['nombre_calle'] ?? '');
    $liderCalle  = trim($_POST['lider_calle'] ?? '');
    $telLider    = trim($_POST['telefono_lider'] ?? '');

    if ($comIdPost <= 0 || $nombreCalle === '') {
        $error = 'Debes seleccionar una comunidad e indicar el nombre de la calle o vereda.';
    } else {
        if ($calleId > 0) {
            $stmt = $conexion->prepare("UPDATE calles SET comunidad_id = ?, nombre_calle = ?, lider_calle = ?, telefono_lider = ? WHERE id = ?");
            $stmt->bind_param('isssi', $comIdPost, $nombreCalle, $liderCalle, $telLider, $calleId);
        } else {
            $stmt = $conexion->prepare("INSERT INTO calles (comunidad_id, nombre_calle, lider_calle, telefono_lider) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isss', $comIdPost, $nombreCalle, $liderCalle, $telLider);
        }
        if ($stmt->execute()) {
            $msg = 'Calle / vereda guardada correctamente.';
        } else {
            $error = 'Error al guardar calle: ' . $stmt->error;
        }
        $stmt->close();
    }
}

$sqlCalles = "SELECT ca.*, c.nombre AS comunidad
              FROM calles ca
              INNER JOIN comunidades c ON ca.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sqlCalles .= " WHERE ca.comunidad_id = " . (int)$comFiltro;
}
$sqlCalles .= " ORDER BY c.nombre ASC, ca.nombre_calle ASC";
$listaCalles = $conexion->query($sqlCalles);

$tituloPagina = 'Calles y Veredas';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Calles, Veredas y Líderes de Calle</h2>
        <p class="text-muted">Subdivisión interna de cada comunidad</p>
    </div>
</div>

<?php if ($msg !== ''): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (es_admin_comunidad()): ?>
    <div class="card">
        <h3>Registrar Calle o Vereda</h3>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="comunidad_id">Comunidad *</label>
                <select name="comunidad_id" id="comunidad_id" required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($comunidades as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($comFiltro === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nombre_calle">Nombre de calle / vereda *</label>
                <input type="text" name="nombre_calle" id="nombre_calle" required placeholder="Ej. Calle Bolívar">
            </div>
            <div class="form-group">
                <label for="lider_calle">Líder de calle / jefe de UBCH o CLAP</label>
                <input type="text" name="lider_calle" id="lider_calle" placeholder="Nombre y apellido">
            </div>
            <div class="form-group">
                <label for="telefono_lider">Teléfono del líder</label>
                <input type="text" name="telefono_lider" id="telefono_lider" placeholder="0414-0000000">
            </div>
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Guardar Calle</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Listado de Calles</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Comunidad</th>
                    <th>Calle / Vereda</th>
                    <th>Líder de Calle</th>
                    <th>Teléfono</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($listaCalles && $listaCalles->num_rows > 0): ?>
                    <?php while ($ca = $listaCalles->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($ca['comunidad']) ?></td>
                            <td><strong><?= e($ca['nombre_calle']) ?></strong></td>
                            <td><?= e($ca['lider_calle'] ?: 'No asignado') ?></td>
                            <td><?= e($ca['telefono_lider'] ?: 'N/A') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay calles registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>