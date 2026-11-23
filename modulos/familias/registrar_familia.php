<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$comFiltro = comunidad_filtro(null);
$error = '';

$familia = [
    'comunidad_id'           => $comFiltro ?: 0,
    'vivienda_id'            => 0,
    'nombre_familia'         => '',
    'jefe_familia_cedula'    => '',
    'cantidad_cilindros_gas' => 1,
    'ingreso_mensual_aprox'  => '0.00',
    'fecha_censo'            => date('Y-m-d')
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexion->prepare("SELECT * FROM familias WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found && ($comFiltro === null || (int)$found['comunidad_id'] === $comFiltro)) {
        $familia = $found;
    } else {
        $id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $familia['comunidad_id']           = es_superadmin() ? (int)($_POST['comunidad_id'] ?? 0) : (int)($_SESSION['comunidad_id'] ?? 0);
    $familia['vivienda_id']            = (int)($_POST['vivienda_id'] ?? 0);
    $familia['nombre_familia']         = trim($_POST['nombre_familia'] ?? '');
    $familia['jefe_familia_cedula']    = trim($_POST['jefe_familia_cedula'] ?? '');
    $familia['cantidad_cilindros_gas'] = max(0, (int)($_POST['cantidad_cilindros_gas'] ?? 1));
    $familia['ingreso_mensual_aprox']  = is_numeric($_POST['ingreso_mensual_aprox'] ?? 0) ? (float)$_POST['ingreso_mensual_aprox'] : 0.00;
    $familia['fecha_censo']            = !empty($_POST['fecha_censo']) ? $_POST['fecha_censo'] : date('Y-m-d');

    $cedJefe = $familia['jefe_familia_cedula'] !== '' ? $familia['jefe_familia_cedula'] : null;

    if ($familia['comunidad_id'] <= 0 || $familia['vivienda_id'] <= 0 || $familia['nombre_familia'] === '') {
        $error = 'Debes seleccionar comunidad, vivienda y nombre de la familia.';
    } else {
        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE familias SET comunidad_id=?, vivienda_id=?, nombre_familia=?, jefe_familia_cedula=?, cantidad_cilindros_gas=?, ingreso_mensual_aprox=?, fecha_censo=? WHERE id=?");
            $stmt->bind_param(
                'iissidsi',
                $familia['comunidad_id'],
                $familia['vivienda_id'],
                $familia['nombre_familia'],
                $cedJefe,
                $familia['cantidad_cilindros_gas'],
                $familia['ingreso_mensual_aprox'],
                $familia['fecha_censo'],
                $id
            );
        } else {
            $stmt = $conexion->prepare("INSERT INTO familias (comunidad_id, vivienda_id, nombre_familia, jefe_familia_cedula, cantidad_cilindros_gas, ingreso_mensual_aprox, fecha_censo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'iissids',
                $familia['comunidad_id'],
                $familia['vivienda_id'],
                $familia['nombre_familia'],
                $cedJefe,
                $familia['cantidad_cilindros_gas'],
                $familia['ingreso_mensual_aprox'],
                $familia['fecha_censo']
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ' . base_url('modulos/familias/listar_familias.php?msg=guardado_ok'));
            exit;
        } else {
            $error = 'Error al guardar familia: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$comunidades = [];
$rCom = $conexion->query($comFiltro !== null ? "SELECT id, nombre FROM comunidades WHERE id = {$comFiltro}" : "SELECT id, nombre FROM comunidades ORDER BY nombre ASC");
while ($r = $rCom->fetch_assoc()) $comunidades[] = $r;

$viviendas = [];
$sqlViv = "SELECT v.id, v.numero_casa, c.nombre AS comunidad, ca.nombre_calle
           FROM viviendas v
           INNER JOIN comunidades c ON v.comunidad_id = c.id
           LEFT JOIN calles ca ON v.calle_id = ca.id";
if ($comFiltro !== null) {
    $sqlViv .= " WHERE v.comunidad_id = " . (int)$comFiltro;
}
$sqlViv .= " ORDER BY c.nombre ASC, v.numero_casa ASC";
$rViv = $conexion->query($sqlViv);
while ($r = $rViv->fetch_assoc()) $viviendas[] = $r;

$tituloPagina = $id > 0 ? 'Editar Familia' : 'Registrar Familia';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2><?= e($tituloPagina) ?></h2>
        <p class="text-muted">Vincula un núcleo familiar a una vivienda censada</p>
    </div>
    <a href="<?= e(base_url('modulos/familias/listar_familias.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-group">
            <label for="comunidad_id">Comunidad *</label>
            <select name="comunidad_id" id="comunidad_id" required <?= !es_superadmin() ? 'disabled' : '' ?>>
                <option value="">-- Selecciona --</option>
                <?php foreach ($comunidades as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)$familia['comunidad_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!es_superadmin()): ?>
                <input type="hidden" name="comunidad_id" value="<?= (int)$familia['comunidad_id'] ?>">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="vivienda_id">Vivienda *</label>
            <select name="vivienda_id" id="vivienda_id" required>
                <option value="">-- Selecciona vivienda --</option>
                <?php foreach ($viviendas as $v): ?>
                    <option value="<?= (int)$v['id'] ?>" <?= ((int)$familia['vivienda_id'] === (int)$v['id']) ? 'selected' : '' ?>>
                        <?= e($v['comunidad'] . ' - ' . ($v['nombre_calle'] ? $v['nombre_calle'] . ' - ' : '') . 'Casa #' . $v['numero_casa']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="nombre_familia">Nombre de la familia *</label>
            <input type="text" name="nombre_familia" id="nombre_familia" required value="<?= e($familia['nombre_familia']) ?>" placeholder="Ej. Familia Pérez Gómez">
        </div>

        <div class="form-group">
            <label for="jefe_familia_cedula">Cédula del Jefe de Familia</label>
            <input type="text" name="jefe_familia_cedula" id="jefe_familia_cedula" value="<?= e($familia['jefe_familia_cedula']) ?>" placeholder="Ej. 18456789">
        </div>

        <div class="form-group">
            <label for="cantidad_cilindros_gas">Cantidad de cilindros de gas</label>
            <input type="number" min="0" name="cantidad_cilindros_gas" id="cantidad_cilindros_gas" value="<?= (int)$familia['cantidad_cilindros_gas'] ?>">
        </div>

        <div class="form-group">
            <label for="ingreso_mensual_aprox">Ingreso mensual aproximado</label>
            <input type="number" step="0.01" min="0" name="ingreso_mensual_aprox" id="ingreso_mensual_aprox" value="<?= e((string)$familia['ingreso_mensual_aprox']) ?>">
        </div>

        <div class="form-group">
            <label for="fecha_censo">Fecha de censo</label>
            <input type="date" name="fecha_censo" id="fecha_censo" value="<?= e($familia['fecha_censo']) ?>">
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Familia</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>