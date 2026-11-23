<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$comFiltro = comunidad_filtro(null);
$error = '';

$vivienda = [
    'comunidad_id'       => $comFiltro ?: 0,
    'calle_id'           => null,
    'numero_casa'        => '',
    'tipo_vivienda'      => 'Casa',
    'tenencia'           => 'Propia',
    'condicion_estructura' => 'Buena',
    'posee_agua'         => 1,
    'posee_electricidad' => 1,
    'posee_gas_directo'  => 0,
    'posee_internet'     => 0,
    'observaciones'      => ''
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexion->prepare("SELECT * FROM viviendas WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found && ($comFiltro === null || (int)$found['comunidad_id'] === $comFiltro)) {
        $vivienda = $found;
    } else {
        $id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vivienda['comunidad_id']       = es_superadmin() ? (int)($_POST['comunidad_id'] ?? 0) : (int)($_SESSION['comunidad_id'] ?? 0);
    $vivienda['calle_id']           = !empty($_POST['calle_id']) ? (int)$_POST['calle_id'] : null;
    $vivienda['numero_casa']        = trim($_POST['numero_casa'] ?? '');
    $vivienda['tipo_vivienda']      = $_POST['tipo_vivienda'] ?? 'Casa';
    $vivienda['tenencia']           = $_POST['tenencia'] ?? 'Propia';
    $vivienda['condicion_estructura'] = $_POST['condicion_estructura'] ?? 'Buena';
    $vivienda['posee_agua']         = isset($_POST['posee_agua']) ? 1 : 0;
    $vivienda['posee_electricidad'] = isset($_POST['posee_electricidad']) ? 1 : 0;
    $vivienda['posee_gas_directo']  = isset($_POST['posee_gas_directo']) ? 1 : 0;
    $vivienda['posee_internet']     = isset($_POST['posee_internet']) ? 1 : 0;
    $vivienda['observaciones']      = trim($_POST['observaciones'] ?? '');

    if ($vivienda['comunidad_id'] <= 0 || $vivienda['numero_casa'] === '') {
        $error = 'Debes seleccionar comunidad e indicar el número de casa/vivienda.';
    } else {
        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE viviendas SET comunidad_id=?, calle_id=?, numero_casa=?, tipo_vivienda=?, tenencia=?, condicion_estructura=?, posee_agua=?, posee_electricidad=?, posee_gas_directo=?, posee_internet=?, observaciones=? WHERE id=?");
            $stmt->bind_param(
                'iissssiiiiis',
                $vivienda['comunidad_id'],
                $vivienda['calle_id'],
                $vivienda['numero_casa'],
                $vivienda['tipo_vivienda'],
                $vivienda['tenencia'],
                $vivienda['condicion_estructura'],
                $vivienda['posee_agua'],
                $vivienda['posee_electricidad'],
                $vivienda['posee_gas_directo'],
                $vivienda['posee_internet'],
                $vivienda['observaciones'],
                $id
            );
        } else {
            $stmt = $conexion->prepare("INSERT INTO viviendas (comunidad_id, calle_id, numero_casa, tipo_vivienda, tenencia, condicion_estructura, posee_agua, posee_electricidad, posee_gas_directo, posee_internet, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'iissssiiiis',
                $vivienda['comunidad_id'],
                $vivienda['calle_id'],
                $vivienda['numero_casa'],
                $vivienda['tipo_vivienda'],
                $vivienda['tenencia'],
                $vivienda['condicion_estructura'],
                $vivienda['posee_agua'],
                $vivienda['posee_electricidad'],
                $vivienda['posee_gas_directo'],
                $vivienda['posee_internet'],
                $vivienda['observaciones']
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ' . base_url('modulos/viviendas/listar_viviendas.php?msg=guardado_ok'));
            exit;
        } else {
            $error = 'Error al guardar vivienda: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$comunidades = [];
$rCom = $conexion->query($comFiltro !== null ? "SELECT id, nombre FROM comunidades WHERE id = {$comFiltro}" : "SELECT id, nombre FROM comunidades ORDER BY nombre ASC");
while ($r = $rCom->fetch_assoc()) $comunidades[] = $r;

$calles = [];
$rCa = $conexion->query($comFiltro !== null ? "SELECT id, comunidad_id, nombre_calle FROM calles WHERE comunidad_id = {$comFiltro} ORDER BY nombre_calle ASC" : "SELECT id, comunidad_id, nombre_calle FROM calles ORDER BY nombre_calle ASC");
while ($r = $rCa->fetch_assoc()) $calles[] = $r;

$tituloPagina = $id > 0 ? 'Editar Vivienda' : 'Registrar Vivienda';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2><?= e($tituloPagina) ?></h2>
        <p class="text-muted">Características físicas y servicios de la vivienda</p>
    </div>
    <a href="<?= e(base_url('modulos/viviendas/listar_viviendas.php')) ?>" class="btn btn-secondary">Volver</a>
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
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)$vivienda['comunidad_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!es_superadmin()): ?>
                <input type="hidden" name="comunidad_id" value="<?= (int)$vivienda['comunidad_id'] ?>">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="calle_id">Calle / Vereda</label>
            <select name="calle_id" id="calle_id">
                <option value="">-- Sin calle asignada --</option>
                <?php foreach ($calles as $ca): ?>
                    <option value="<?= (int)$ca['id'] ?>" <?= ((int)$vivienda['calle_id'] === (int)$ca['id']) ? 'selected' : '' ?>>
                        <?= e($ca['nombre_calle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="numero_casa">Número de casa / apartamento *</label>
            <input type="text" name="numero_casa" id="numero_casa" required value="<?= e($vivienda['numero_casa']) ?>">
        </div>

        <div class="form-group">
            <label for="tipo_vivienda">Tipo de vivienda</label>
            <select name="tipo_vivienda" id="tipo_vivienda">
                <?php foreach (['Casa', 'Apartamento', 'Anexo', 'Rancho', 'Quinta', 'Otro'] as $tp): ?>
                    <option value="<?= e($tp) ?>" <?= ($vivienda['tipo_vivienda'] === $tp) ? 'selected' : '' ?>><?= e($tp) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="tenencia">Tenencia</label>
            <select name="tenencia" id="tenencia">
                <?php foreach (['Propia', 'Alquilada', 'Prestada', 'Invadida', 'Pagándose'] as $tn): ?>
                    <option value="<?= e($tn) ?>" <?= ($vivienda['tenencia'] === $tn) ? 'selected' : '' ?>><?= e($tn) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="condicion_estructura">Condición de estructura</label>
            <select name="condicion_estructura" id="condicion_estructura">
                <?php foreach (['Buena', 'Regular', 'Mala', 'En riesgo'] as $cd): ?>
                    <option value="<?= e($cd) ?>" <?= ($vivienda['condicion_estructura'] === $cd) ? 'selected' : '' ?>><?= e($cd) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group full-width">
            <label>Servicios básicos</label>
            <div class="checks-row">
                <label><input type="checkbox" name="posee_agua" value="1" <?= !empty($vivienda['posee_agua']) ? 'checked' : '' ?>> Agua</label>
                <label><input type="checkbox" name="posee_electricidad" value="1" <?= !empty($vivienda['posee_electricidad']) ? 'checked' : '' ?>> Electricidad</label>
                <label><input type="checkbox" name="posee_gas_directo" value="1" <?= !empty($vivienda['posee_gas_directo']) ? 'checked' : '' ?>> Gas directo</label>
                <label><input type="checkbox" name="posee_internet" value="1" <?= !empty($vivienda['posee_internet']) ? 'checked' : '' ?>> Internet</label>
            </div>
        </div>

        <div class="form-group full-width">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" rows="3"><?= e($vivienda['observaciones']) ?></textarea>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Vivienda</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>