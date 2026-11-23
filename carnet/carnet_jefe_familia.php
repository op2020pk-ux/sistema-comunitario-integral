<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);
$familiaId = isset($_GET['familia_id']) ? (int)$_GET['familia_id'] : 0;

$sqlFamilias = "SELECT f.id, f.nombre_familia, f.jefe_familia_cedula, c.nombre AS comunidad
                FROM familias f
                INNER JOIN comunidades c ON f.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sqlFamilias .= " WHERE f.comunidad_id = " . (int)$comFiltro;
}
$sqlFamilias .= " ORDER BY f.nombre_familia ASC";
$listaFamilias = $conexion->query($sqlFamilias);

if (!$listaFamilias) {
    die('Error al cargar núcleos familiares: ' . e($conexion->error));
}

$familiaData = null;
$jefeData = null;
$totalIntegrantes = 0;

if ($familiaId > 0) {
    $sqlFam = "SELECT f.*, c.nombre AS comunidad, c.parroquia, c.municipio, c.estado AS estado_geo,
                      v.numero_casa, v.tipo_vivienda, ca.nombre_calle
               FROM familias f
               INNER JOIN comunidades c ON f.comunidad_id = c.id
               INNER JOIN viviendas v ON f.vivienda_id = v.id
               LEFT JOIN calles ca ON v.calle_id = ca.id
               WHERE f.id = ?";
    if ($comFiltro !== null) {
        $sqlFam .= " AND f.comunidad_id = " . (int)$comFiltro;
    }

    $stmtF = $conexion->prepare($sqlFam);
    if (!$stmtF) {
        die('Error al preparar consulta de familia: ' . e($conexion->error));
    }
    $stmtF->bind_param('i', $familiaId);
    $stmtF->execute();
    $familiaData = $stmtF->get_result()->fetch_assoc();
    $stmtF->close();

    if ($familiaData) {
        $stmtJ = $conexion->prepare("SELECT nacionalidad, cedula, nombres, apellidos, telefono, foto FROM habitantes WHERE familia_id = ? AND activo = 1 ORDER BY (parentesco = 'Jefe de Familia') DESC, id ASC LIMIT 1");
        if ($stmtJ) {
            $stmtJ->bind_param('i', $familiaId);
            $stmtJ->execute();
            $jefeData = $stmtJ->get_result()->fetch_assoc();
            $stmtJ->close();
        }

        $stmtC = $conexion->prepare("SELECT COUNT(*) AS total FROM habitantes WHERE familia_id = ? AND activo = 1");
        if ($stmtC) {
            $stmtC->bind_param('i', $familiaId);
            $stmtC->execute();
            $totalIntegrantes = (int)($stmtC->get_result()->fetch_assoc()['total'] ?? 0);
            $stmtC->close();
        }
    }
}

$fotoCarnetUrl = '';
if (!empty($jefeData['foto']) && file_exists(BASE_PATH . '/' . $jefeData['foto'])) {
    $fotoCarnetUrl = base_url($jefeData['foto']);
} else {
    $cedBusqueda = $jefeData['cedula'] ?? ($familiaData['jefe_familia_cedula'] ?? '');
    if ($cedBusqueda !== '') {
        $stmtUsrFoto = $conexion->prepare("SELECT foto FROM usuarios WHERE cedula = ? AND foto IS NOT NULL AND foto != '' LIMIT 1");
        if ($stmtUsrFoto) {
            $stmtUsrFoto->bind_param('s', $cedBusqueda);
            $stmtUsrFoto->execute();
            $usrFotoRow = $stmtUsrFoto->get_result()->fetch_assoc();
            $stmtUsrFoto->close();

            if (!empty($usrFotoRow['foto']) && file_exists(BASE_PATH . '/' . $usrFotoRow['foto'])) {
                $fotoCarnetUrl = base_url($usrFotoRow['foto']);
            }
        }
    }
}

$qrPayload = '';
if ($familiaData) {
    $cedulaJefe = $jefeData['cedula'] ?? ($familiaData['jefe_familia_cedula'] ?? 'N/A');
    $nombreJefe = $jefeData ? ($jefeData['nombres'] . ' ' . $jefeData['apellidos']) : $familiaData['nombre_familia'];
    $qrPayload = json_encode([
        'familia_id'  => (int)$familiaData['id'],
        'familia'     => $familiaData['nombre_familia'],
        'jefe'        => $nombreJefe,
        'cedula_jefe' => $cedulaJefe,
        'comunidad'   => $familiaData['comunidad'],
        'vivienda'    => $familiaData['numero_casa'],
        'integrantes' => $totalIntegrantes
    ], JSON_UNESCAPED_UNICODE);
}

$tituloPagina = 'Carnet de Jefe de Familia';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header no-print">
    <div>
        <h2>Carnet de Jefe de Familia</h2>
        <p class="text-muted">Credencial familiar ampliada con fotografía, datos completos y código QR</p>
    </div>
    <?php if ($familiaData): ?>
        <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Imprimir Carnet</button>
    <?php endif; ?>
</div>

<div class="card no-print">
    <form method="GET" class="inline-form">
        <label for="familia_id">Seleccionar núcleo familiar:</label>
        <select name="familia_id" id="familia_id" required>
            <option value="">-- Elige una familia --</option>
            <?php while ($fm = $listaFamilias->fetch_assoc()): ?>
                <option value="<?= (int)$fm['id'] ?>" <?= ($familiaId === (int)$fm['id']) ? 'selected' : '' ?>>
                    <?= e($fm['nombre_familia']) ?> (<?= e($fm['comunidad']) ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Cargar Carnet</button>
    </form>
</div>

<?php if ($familiaData): ?>
    <div class="carnet-wrapper">
        <div class="carnet-card carnet-familia carnet-ampliado">
            <div class="carnet-top">
                <img src="<?= e(base_url($configSistema['logo_sistema'])) ?>" alt="Logo" class="carnet-logo" onerror="this.style.display='none';">
                <div>
                    <div class="carnet-org"><?= e($configSistema['nombre_sistema']) ?></div>
                    <div class="carnet-sub">Carnet de Jefe de Familia</div>
                </div>
            </div>

            <div class="carnet-body carnet-body-ampliado">
                <div class="carnet-photo-box">
                    <?php if ($fotoCarnetUrl !== ''): ?>
                        <img src="<?= e($fotoCarnetUrl) ?>" alt="Foto del carnet" class="carnet-photo">
                    <?php else: ?>
                        <div class="carnet-photo-placeholder">Sin Foto</div>
                    <?php endif; ?>
                </div>

                <div class="carnet-info">
                    <h3 class="carnet-name">
                        <?= e($jefeData ? ($jefeData['nombres'] . ' ' . $jefeData['apellidos']) : $familiaData['nombre_familia']) ?>
                    </h3>
                    <p><strong>Núcleo:</strong> <?= e($familiaData['nombre_familia']) ?></p>
                    <p><strong>Cédula:</strong> <?= e(($jefeData['nacionalidad'] ?? 'V') . '-' . ($jefeData['cedula'] ?? ($familiaData['jefe_familia_cedula'] ?? 'S/C'))) ?></p>
                    <?php if (!empty($jefeData['telefono'])): ?>
                        <p><strong>Teléfono:</strong> <?= e($jefeData['telefono']) ?></p>
                    <?php endif; ?>
                    <p><strong>Comunidad:</strong> <?= e($familiaData['comunidad']) ?></p>
                    <p><strong>Calle / Casa:</strong> <?= e($familiaData['nombre_calle'] ?: 'Sin calle') ?> - Casa #<?= e($familiaData['numero_casa']) ?></p>
                    <p><strong>Integrantes activos:</strong> <?= $totalIntegrantes ?></p>
                </div>

                <div class="carnet-qr-box">
                    <div id="qrCanvasContainer" data-qr="<?= e($qrPayload) ?>"></div>
                    <small>Código Familiar #<?= (int)$familiaData['id'] ?></small>
                </div>
            </div>

            <div class="carnet-footer">
                <span><?= e($familiaData['municipio'] ?: '') ?><?= (!empty($familiaData['municipio']) && !empty($familiaData['estado_geo'])) ? ', ' : '' ?><?= e($familiaData['estado_geo'] ?: '') ?></span>
                <span>CENSO ACTIVO</span>
            </div>
        </div>
    </div>
<?php elseif ($familiaId > 0): ?>
    <div class="alert alert-error">No se encontró el núcleo familiar seleccionado o no pertenece a tu comunidad.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>