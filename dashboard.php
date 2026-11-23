<?php
require_once __DIR__ . '/config/verificar_sesion.php';

$comFiltro = comunidad_filtro(isset($_GET['comunidad_id']) ? (int)$_GET['comunidad_id'] : null);

$whereCom = '';
$params = [];
$types = '';
if ($comFiltro !== null) {
    $whereCom = ' WHERE comunidad_id = ? ';
    $params[] = $comFiltro;
    $types .= 'i';
}

function contar_tabla(mysqli $db, string $tabla, string $extraWhere = '', ?int $comId = null): int {
    $sql = "SELECT COUNT(*) AS total FROM {$tabla}";
    $conds = [];
    $params = [];
    $types = '';

    if ($comId !== null) {
        $conds[] = "comunidad_id = ?";
        $params[] = $comId;
        $types .= 'i';
    }
    if ($extraWhere !== '') {
        $conds[] = $extraWhere;
    }
    if (!empty($conds)) {
        $sql .= " WHERE " . implode(' AND ', $conds);
    }

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['total'] ?? 0);
}

$totalComunidades = es_superadmin() ? contar_tabla($conexion, 'comunidades') : contar_tabla($conexion, 'comunidades', $comFiltro ? "id = {$comFiltro}" : '');
$totalViviendas   = contar_tabla($conexion, 'viviendas', '', $comFiltro);
$totalFamilias    = contar_tabla($conexion, 'familias', '', $comFiltro);
$totalHabitantes  = contar_tabla($conexion, 'habitantes', 'activo = 1', $comFiltro);
$totalFallecidos  = contar_tabla($conexion, 'fallecidos', '', $comFiltro);
$totalBeneficios  = contar_tabla($conexion, 'beneficios_entregados', '', $comFiltro);

// Conteo de estudiantes activos
$sqlEst = "SELECT COUNT(*) AS total FROM estudiantes e INNER JOIN habitantes h ON e.habitante_id = h.id WHERE h.activo = 1";
if ($comFiltro !== null) {
    $sqlEst .= " AND h.comunidad_id = " . (int)$comFiltro;
}
$totalEstudiantes = (int)($conexion->query($sqlEst)->fetch_assoc()['total'] ?? 0);

// Lista de comunidades para filtro si es SuperAdmin
$comunidadesList = [];
if (es_superadmin()) {
    $rCom = $conexion->query("SELECT id, nombre FROM comunidades ORDER BY nombre ASC");
    while ($row = $rCom->fetch_assoc()) {
        $comunidadesList[] = $row;
    }
}

// Últimas entregas registradas
$sqlEntregas = "SELECT be.id, be.tipo_beneficio, be.cantidad, be.fecha_entrega, f.nombre_familia, c.nombre AS comunidad
                FROM beneficios_entregados be
                INNER JOIN familias f ON be.familia_id = f.id
                INNER JOIN comunidades c ON be.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sqlEntregas .= " WHERE be.comunidad_id = " . (int)$comFiltro;
}
$sqlEntregas .= " ORDER BY be.fecha_entrega DESC, be.id DESC LIMIT 6";
$ultimasEntregas = $conexion->query($sqlEntregas);

$tituloPagina = 'Panel Principal';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Panel Principal</h2>
        <p class="text-muted">Resumen estadístico del censo y gestión comunitaria</p>
    </div>

    <?php if (es_superadmin() && !empty($comunidadesList)): ?>
        <form method="GET" class="inline-form">
            <label for="comunidad_id">Comunidad:</label>
            <select name="comunidad_id" id="comunidad_id" onchange="this.form.submit()">
                <option value="">Todas las comunidades</option>
                <?php foreach ($comunidadesList as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($comFiltro === (int)$c['id']) ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if (!empty($_GET['error']) && $_GET['error'] === 'permisos'): ?>
    <div class="alert alert-error">No tienes permisos suficientes para acceder a esa sección.</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Comunidades</span>
        <strong class="stat-value"><?= $totalComunidades ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Viviendas</span>
        <strong class="stat-value"><?= $totalViviendas ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Núcleos Familiares</span>
        <strong class="stat-value"><?= $totalFamilias ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Habitantes Activos</span>
        <strong class="stat-value"><?= $totalHabitantes ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Estudiantes</span>
        <strong class="stat-value"><?= $totalEstudiantes ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Beneficios Entregados</span>
        <strong class="stat-value"><?= $totalBeneficios ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Fallecidos Registrados</span>
        <strong class="stat-value"><?= $totalFallecidos ?></strong>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Últimas Entregas de Beneficios</h3>
        <a href="<?= e(base_url('modulos/beneficios/listar_beneficios.php')) ?>" class="btn btn-sm">Ver todas</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comunidad</th>
                    <th>Familia</th>
                    <th>Beneficio</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ultimasEntregas && $ultimasEntregas->num_rows > 0): ?>
                    <?php while ($ent = $ultimasEntregas->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($ent['fecha_entrega']) ?></td>
                            <td><?= e($ent['comunidad']) ?></td>
                            <td><?= e($ent['nombre_familia']) ?></td>
                            <td><?= e($ent['tipo_beneficio']) ?></td>
                            <td><?= (int)$ent['cantidad'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay entregas registradas todavía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>