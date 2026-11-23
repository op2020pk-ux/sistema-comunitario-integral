<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';
exigir_rol(['SuperAdmin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$error = '';

$com = [
    'nombre'               => '',
    'rif_o_codigo'         => '',
    'parroquia'            => '',
    'municipio'            => '',
    'estado'               => '',
    'direccion_referencia' => ''
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexion->prepare("SELECT * FROM comunidades WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found) {
        $com = $found;
    } else {
        $id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $com['nombre']               = trim($_POST['nombre'] ?? '');
    $com['rif_o_codigo']         = trim($_POST['rif_o_codigo'] ?? '');
    $com['parroquia']            = trim($_POST['parroquia'] ?? '');
    $com['municipio']            = trim($_POST['municipio'] ?? '');
    $com['estado']               = trim($_POST['estado'] ?? '');
    $com['direccion_referencia'] = trim($_POST['direccion_referencia'] ?? '');

    $rifVal = $com['rif_o_codigo'] !== '' ? $com['rif_o_codigo'] : null;

    if ($com['nombre'] === '') {
        $error = 'El nombre de la comunidad es obligatorio.';
    } else {
        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE comunidades SET nombre = ?, rif_o_codigo = ?, parroquia = ?, municipio = ?, estado = ?, direccion_referencia = ? WHERE id = ?");
            $stmt->bind_param('ssssssi', $com['nombre'], $rifVal, $com['parroquia'], $com['municipio'], $com['estado'], $com['direccion_referencia'], $id);
        } else {
            $stmt = $conexion->prepare("INSERT INTO comunidades (nombre, rif_o_codigo, parroquia, municipio, estado, direccion_referencia) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $com['nombre'], $rifVal, $com['parroquia'], $com['municipio'], $com['estado'], $com['direccion_referencia']);
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ' . base_url('modulos/comunidades/listar_comunidades.php?msg=guardado_ok'));
            exit;
        } else {
            $error = 'Error al guardar la comunidad: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$tituloPagina = $id > 0 ? 'Editar Comunidad' : 'Nueva Comunidad';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2><?= e($tituloPagina) ?></h2>
        <p class="text-muted">Datos territoriales del consejo comunal o sector</p>
    </div>
    <a href="<?= e(base_url('modulos/comunidades/listar_comunidades.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-group">
            <label for="nombre">Nombre de la comunidad *</label>
            <input type="text" id="nombre" name="nombre" required value="<?= e($com['nombre']) ?>">
        </div>

        <div class="form-group">
            <label for="rif_o_codigo">RIF o Código SITUR / Consejo Comunal</label>
            <input type="text" id="rif_o_codigo" name="rif_o_codigo" value="<?= e($com['rif_o_codigo']) ?>">
        </div>

        <div class="form-group">
            <label for="parroquia">Parroquia</label>
            <input type="text" id="parroquia" name="parroquia" value="<?= e($com['parroquia']) ?>">
        </div>

        <div class="form-group">
            <label for="municipio">Municipio</label>
            <input type="text" id="municipio" name="municipio" value="<?= e($com['municipio']) ?>">
        </div>

        <div class="form-group">
            <label for="estado">Estado</label>
            <input type="text" id="estado" name="estado" value="<?= e($com['estado']) ?>">
        </div>

        <div class="form-group full-width">
            <label for="direccion_referencia">Dirección de referencia / Linderos</label>
            <textarea id="direccion_referencia" name="direccion_referencia" rows="3"><?= e($com['direccion_referencia']) ?></textarea>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Comunidad</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>