<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(null);
$familiaId = isset($_GET['familia_id']) ? (int)$_GET['familia_id'] : 0;
$tipoDoc   = $_GET['tipo'] ?? 'planilla'; // planilla | residencia

$sqlFamilias = "SELECT f.id, f.nombre_familia, c.nombre AS comunidad
                FROM familias f INNER JOIN comunidades c ON f.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sqlFamilias .= " WHERE f.comunidad_id = " . (int)$comFiltro;
}
$sqlFamilias .= " ORDER BY f.nombre_familia ASC";
$listaFamilias = $conexion->query($sqlFamilias);

$familia = null;
$integrantes = [];
$jefe = null;

if ($familiaId > 0) {
    $sqlF = "SELECT f.*, c.nombre AS comunidad, c.rif_o_codigo, c.parroquia, c.municipio, c.estado AS estado_geo,
                    v.numero_casa, v.tipo_vivienda, v.tenencia, ca.nombre_calle
             FROM familias f
             INNER JOIN comunidades c ON f.comunidad_id = c.id
             INNER JOIN viviendas v ON f.vivienda_id = v.id
             LEFT JOIN calles ca ON v.calle_id = ca.id
             WHERE f.id = ?";
    if ($comFiltro !== null) {
        $sqlF .= " AND f.comunidad_id = " . (int)$comFiltro;
    }
    $stmtF = $conexion->prepare($sqlF);
    $stmtF->bind_param('i', $familiaId);
    $stmtF->execute();
    $familia = $stmtF->get_result()->fetch_assoc();
    $stmtF->close();

    if ($familia) {
        $stmtH = $conexion->prepare("SELECT *, TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) AS edad FROM habitantes WHERE familia_id = ? AND activo = 1 ORDER BY (parentesco = 'Jefe de Familia') DESC, edad DESC");
        $stmtH->bind_param('i', $familiaId);
        $stmtH->execute();
        $resH = $stmtH->get_result();
        while ($row = $resH->fetch_assoc()) {
            if ($jefe === null) {
                $jefe = $row;
            }
            $integrantes[] = $row;
        }
        $stmtH->close();
    }
}

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaTexto = date('d') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');

$tituloPagina = 'Constancias y Planillas Censales';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header no-print">
    <div>
        <h2>Constancia de Residencia y Planilla Censal</h2>
        <p class="text-muted">Documentos oficiales listos para imprimir o guardar como PDF desde el navegador</p>
    </div>
    <?php if ($familia): ?>
        <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Imprimir / Guardar PDF</button>
    <?php endif; ?>
</div>

<div class="card no-print">
    <form method="GET" class="inline-form">
        <label for="familia_id">Familia:</label>
        <select name="familia_id" id="familia_id" required>
            <option value="">-- Selecciona núcleo familiar --</option>
            <?php if ($listaFamilias): ?>
                <?php while ($fm = $listaFamilias->fetch_assoc()): ?>
                    <option value="<?= (int)$fm['id'] ?>" <?= $familiaId === (int)$fm['id'] ? 'selected' : '' ?>>
                        <?= e($fm['nombre_familia']) ?> (<?= e($fm['comunidad']) ?>)
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <label for="tipo">Documento:</label>
        <select name="tipo" id="tipo">
            <option value="planilla" <?= $tipoDoc === 'planilla' ? 'selected' : '' ?>>Planilla Censal del Núcleo</option>
            <option value="residencia" <?= $tipoDoc === 'residencia' ? 'selected' : '' ?>>Constancia de Residencia</option>
        </select>

        <button type="submit" class="btn btn-secondary">Generar Documento</button>
    </form>
</div>

<?php if ($familia): ?>
    <div class="sheet-print">
        <div class="sheet-header">
            <img src="<?= e(base_url($configSistema['logo_sistema'])) ?>" alt="Logo" class="sheet-logo" onerror="this.style.display='none';">
            <div>
                <h3><?= e(mb_strtoupper($familia['comunidad'])) ?></h3>
                <p><?= e($configSistema['nombre_sistema']) ?> &mdash; RIF/Código: <?= e($familia['rif_o_codigo'] ?: 'N/A') ?></p>
                <p><small><?= e(implode(', ', array_filter([$familia['parroquia'], $familia['municipio'], $familia['estado_geo']]))) ?></small></p>
            </div>
        </div>

        <hr>

        <?php if ($tipoDoc === 'residencia'): ?>
            <h2 class="sheet-title">CONSTANCIA DE RESIDENCIA</h2>
            <p class="sheet-paragraph">
                Quienes suscriben, voceros y representantes del Consejo Comunal <strong><?= e($familia['comunidad']) ?></strong>,
                hacen constar por medio de la presente que el(la) ciudadano(a)
                <strong><?= e($jefe ? ($jefe['nombres'] . ' ' . $jefe['apellidos']) : $familia['nombre_familia']) ?></strong>,
                titular de la cédula de identidad N°
                <strong><?= e($jefe && $jefe['cedula'] ? ($jefe['nacionalidad'] . '-' . $jefe['cedula']) : ($familia['jefe_familia_cedula'] ?: 'No registrada')) ?></strong>,
                reside de manera permanente en esta comunidad, específicamente en
                <strong><?= e(($familia['nombre_calle'] ? $familia['nombre_calle'] . ', ' : '') . $familia['tipo_vivienda'] . ' N° ' . $familia['numero_casa']) ?></strong>,
                Parroquia <?= e($familia['parroquia'] ?: '___') ?>, Municipio <?= e($familia['municipio'] ?: '___') ?>, Estado <?= e($familia['estado_geo'] ?: '___') ?>.
            </p>
            <p class="sheet-paragraph">
                Constancia que se expide a petición de la parte interesada en fecha <strong><?= e($fechaTexto) ?></strong>.
            </p>
        <?php else: ?>
            <h2 class="sheet-title">PLANILLA CENSAL DEL NÚCLEO FAMILIAR</h2>
            <div class="grid-2" style="margin-bottom: 1rem;">
                <div>
                    <p><strong>Familia:</strong> <?= e($familia['nombre_familia']) ?></p>
                    <p><strong>Calle / Vereda:</strong> <?= e($familia['nombre_calle'] ?: 'Sin calle') ?></p>
                    <p><strong>Vivienda:</strong> <?= e($familia['tipo_vivienda'] . ' #' . $familia['numero_casa'] . ' (' . $familia['tenencia'] . ')') ?></p>
                </div>
                <div>
                    <p><strong>Cilindros de gas:</strong> <?= (int)$familia['cantidad_cilindros_gas'] ?></p>
                    <p><strong>Fecha de censo:</strong> <?= e($familia['fecha_censo'] ?: date('Y-m-d')) ?></p>
                    <p><strong>Total integrantes activos:</strong> <?= count($integrantes) ?></p>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cédula</th>
                        <th>Nombres y Apellidos</th>
                        <th>Parentesco</th>
                        <th>Edad</th>
                        <th>Sexo</th>
                        <th>Ocupación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($integrantes)): ?>
                        <?php foreach ($integrantes as $idx => $in): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><?= e($in['cedula'] ? ($in['nacionalidad'] . '-' . $in['cedula']) : 'S/C') ?></td>
                                <td><?= e($in['nombres'] . ' ' . $in['apellidos']) ?></td>
                                <td><?= e($in['parentesco']) ?></td>
                                <td><?= (int)$in['edad'] ?></td>
                                <td><?= e($in['sexo']) ?></td>
                                <td><?= e($in['ocupacion'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Sin integrantes activos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="sheet-signatures">
            <div>
                <div class="sig-line"></div>
                <p>Vocero(a) de Unidad Ejecutiva</p>
            </div>
            <div>
                <div class="sig-line"></div>
                <p>Contraloría Social / Sello</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>