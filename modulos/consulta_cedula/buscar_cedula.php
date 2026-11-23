<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(null);
$cedulaBuscada = trim($_GET['cedula'] ?? '');
$ciudadano = null;
$nucleo = [];

if ($cedulaBuscada !== '') {
    $cedulaLimpia = preg_replace('/[^0-9]/', '', $cedulaBuscada);

    $sql = "SELECT * FROM vista_consulta_ciudadano WHERE cedula = ?";
    if ($comFiltro !== null) {
        $sql .= " AND comunidad_id = " . (int)$comFiltro;
    }
    $sql .= " LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $cedulaLimpia);
    $stmt->execute();
    $ciudadano = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($ciudadano) {
        $famId = (int)$ciudadano['familia_id'];
        $stmtN = $conexion->prepare("SELECT * FROM vista_consulta_ciudadano WHERE familia_id = ? ORDER BY (parentesco = 'Jefe de Familia') DESC, edad_actual DESC");
        $stmtN->bind_param('i', $famId);
        $stmtN->execute();
        $resN = $stmtN->get_result();
        while ($rowN = $resN->fetch_assoc()) {
            $nucleo[] = $rowN;
        }
        $stmtN->close();
    }
}

$tituloPagina = 'Consulta por Cédula';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Consulta Rápida por Cédula</h2>
        <p class="text-muted">Verifica ficha personal, vivienda y núcleo familiar completo</p>
    </div>
</div>

<div class="card">
    <form method="GET" class="inline-form">
        <label for="cedula">Número de Cédula:</label>
        <input type="text" id="cedula" name="cedula" required value="<?= e($cedulaBuscada) ?>" placeholder="Ej. 18456789" autofocus>
        <button type="submit" class="btn btn-primary">Consultar</button>
    </form>
</div>

<?php if ($cedulaBuscada !== '' && !$ciudadano): ?>
    <div class="alert alert-error">No se encontró ningún ciudadano con la cédula <strong><?= e($cedulaBuscada) ?></strong> en tu ámbito de consulta.</div>
<?php endif; ?>

<?php if ($ciudadano): ?>
    <div class="card">
        <div class="card-header">
            <h3>Ficha del Ciudadano</h3>
            <span class="badge <?= (int)$ciudadano['esta_vivo_o_activo'] === 1 ? 'badge-success' : 'badge-danger' ?>">
                <?= (int)$ciudadano['esta_vivo_o_activo'] === 1 ? 'ACTIVO EN CENSO' : 'INACTIVO / FALLECIDO' ?>
            </span>
        </div>

        <div class="grid-2">
            <div>
                <p><strong>Nombre completo:</strong> <?= e($ciudadano['nombre_completo']) ?></p>
                <p><strong>Cédula:</strong> <?= e($ciudadano['nacionalidad'] . '-' . $ciudadano['cedula']) ?></p>
                <p><strong>Edad actual:</strong> <?= (int)$ciudadano['edad_actual'] ?> años (Nac. <?= e($ciudadano['fecha_nacimiento']) ?>)</p>
                <p><strong>Sexo / Estado civil:</strong> <?= e($ciudadano['sexo']) ?> / <?= e($ciudadano['estado_civil']) ?></p>
                <p><strong>Teléfono:</strong> <?= e($ciudadano['telefono'] ?: 'No registrado') ?></p>
            </div>
            <div>
                <p><strong>Comunidad:</strong> <?= e($ciudadano['comunidad']) ?></p>
                <p><strong>Calle / Vivienda:</strong> <?= e(($ciudadano['nombre_calle'] ?: 'Sin calle') . ' - ' . $ciudadano['tipo_vivienda'] . ' #' . $ciudadano['numero_casa']) ?></p>
                <p><strong>Núcleo Familiar:</strong> <?= e($ciudadano['nombre_familia']) ?> (<?= e($ciudadano['parentesco']) ?>)</p>
                <p><strong>Ocupación:</strong> <?= e($ciudadano['ocupacion'] ?: 'No especificada') ?></p>
                <p><strong>Salud:</strong> <?= e($ciudadano['enfermedad_cronica'] ?: 'Sin enfermedad crónica reportada') ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Integrantes del Núcleo Familiar (<?= count($nucleo) ?>)</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre Completo</th>
                        <th>Parentesco</th>
                        <th>Edad</th>
                        <th>Sexo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nucleo as $m): ?>
                        <tr>
                            <td><?= e($m['cedula'] ? ($m['nacionalidad'] . '-' . $m['cedula']) : 'S/C') ?></td>
                            <td><strong><?= e($m['nombre_completo']) ?></strong></td>
                            <td><?= e($m['parentesco']) ?></td>
                            <td><?= (int)$m['edad_actual'] ?> años</td>
                            <td><?= e($m['sexo']) ?></td>
                            <td><?= (int)$m['esta_vivo_o_activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>