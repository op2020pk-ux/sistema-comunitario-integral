<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$comFiltro = comunidad_filtro(null);
$error = '';

$entrega = [
    'familia_id'     => 0,
    'tipo_beneficio' => 'Bolsa de Alimentación CLAP',
    'cantidad'       => 1,
    'fecha_entrega'  => date('Y-m-d'),
    'entregado_por'  => $_SESSION['nombre_completo'] ?? '',
    'observacion'    => ''
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexion->prepare("SELECT * FROM beneficios_entregados WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found && ($comFiltro === null || (int)$found['comunidad_id'] === $comFiltro)) {
        $entrega = $found;
    } else {
        $id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrega['familia_id']     = (int)($_POST['familia_id'] ?? 0);
    $entrega['tipo_beneficio'] = trim($_POST['tipo_beneficio'] ?? '');
    $entrega['cantidad']       = max(1, (int)($_POST['cantidad'] ?? 1));
    $entrega['fecha_entrega']  = $_POST['fecha_entrega'] ?? date('Y-m-d');
    $entrega['entregado_por']  = trim($_POST['entregado_por'] ?? '');
    $entrega['observacion']    = trim($_POST['observacion'] ?? '');

    $stmtFam = $conexion->prepare("SELECT comunidad_id FROM familias WHERE id = ? LIMIT 1");
    $stmtFam->bind_param('i', $entrega['familia_id']);
    $stmtFam->execute();
    $famRow = $stmtFam->get_result()->fetch_assoc();
    $stmtFam->close();

    if (!$famRow || ($comFiltro !== null && (int)$famRow['comunidad_id'] !== $comFiltro)) {
        $error = 'Debes seleccionar una familia válida.';
    } elseif ($entrega['tipo_beneficio'] === '' || $entrega['fecha_entrega'] === '') {
        $error = 'El tipo de beneficio y la fecha son obligatorios.';
    } else {
        $comunidadId = (int)$famRow['comunidad_id'];
        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE beneficios_entregados SET comunidad_id=?, familia_id=?, tipo_beneficio=?, cantidad=?, fecha_entrega=?, entregado_por=?, observacion=? WHERE id=?");
            $stmt->bind_param(
                'iisisssi',
                $comunidadId,
                $entrega['familia_id'],
                $entrega['tipo_beneficio'],
                $entrega['cantidad'],
                $entrega['fecha_entrega'],
                $entrega['entregado_por'],
                $entrega['observacion'],
                $id
            );
        } else {
            $stmt = $conexion->prepare("INSERT INTO beneficios_entregados (comunidad_id, familia_id, tipo_beneficio, cantidad, fecha_entrega, entregado_por, observacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'iisisss',
                $comunidadId,
                $entrega['familia_id'],
                $entrega['tipo_beneficio'],
                $entrega['cantidad'],
                $entrega['fecha_entrega'],
                $entrega['entregado_por'],
                $entrega['observacion']
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ' . base_url('modulos/beneficios/listar_beneficios.php?msg=guardado_ok'));
            exit;
        } else {
            $error = 'Error al guardar entrega: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$sqlFam = "SELECT f.id, f.nombre_familia, f.cantidad_cilindros_gas, c.nombre AS comunidad
           FROM familias f INNER JOIN comunidades c ON f.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sqlFam .= " WHERE f.comunidad_id = " . (int)$comFiltro;
}
$sqlFam .= " ORDER BY f.nombre_familia ASC";
$listaFamilias = $conexion->query($sqlFam);

$tituloPagina = $id > 0 ? 'Editar Entrega' : 'Registrar Entrega';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2><?= e($tituloPagina) ?> de Beneficio</h2>
        <p class="text-muted">Asigna entregas sociales a los núcleos familiares</p>
    </div>
    <a href="<?= e(base_url('modulos/beneficios/listar_beneficios.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-group full-width">
            <label for="familia_id">Familia Beneficiada *</label>
            <select name="familia_id" id="familia_id" required>
                <option value="">-- Selecciona la familia --</option>
                <?php if ($listaFamilias): ?>
                    <?php while ($fm = $listaFamilias->fetch_assoc()): ?>
                        <option value="<?= (int)$fm['id'] ?>" <?= ((int)$entrega['familia_id'] === (int)$fm['id']) ? 'selected' : '' ?>>
                            <?= e($fm['nombre_familia'] . ' (' . $fm['comunidad'] . ') - Cilindros: ' . (int)$fm['cantidad_cilindros_gas']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo_beneficio">Tipo de beneficio *</label>
            <input type="text" name="tipo_beneficio" id="tipo_beneficio" list="sugerenciasBeneficios" required value="<?= e($entrega['tipo_beneficio']) ?>">
            <datalist id="sugerenciasBeneficios">
                <option value="Bolsa de Alimentación CLAP">
                <option value="Cilindro Gas 10kg">
                <option value="Cilindro Gas 18kg">
                <option value="Cilindro Gas 43kg">
                <option value="Jornada Médica / Medicamentos">
                <option value="Kit Escolar / Uniformes">
            </datalist>
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad *</label>
            <input type="number" min="1" name="cantidad" id="cantidad" required value="<?= (int)$entrega['cantidad'] ?>">
        </div>

        <div class="form-group">
            <label for="fecha_entrega">Fecha de entrega *</label>
            <input type="date" name="fecha_entrega" id="fecha_entrega" required value="<?= e($entrega['fecha_entrega']) ?>">
        </div>

        <div class="form-group">
            <label for="entregado_por">Entregado por</label>
            <input type="text" name="entregado_por" id="entregado_por" value="<?= e($entrega['entregado_por']) ?>">
        </div>

        <div class="form-group full-width">
            <label for="observacion">Observación</label>
            <input type="text" name="observacion" id="observacion" value="<?= e($entrega['observacion']) ?>">
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Entrega</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>