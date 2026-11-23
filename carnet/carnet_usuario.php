<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

$usuarioId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['usuario_id'] ?? 0);

// Solo SuperAdmin puede consultar el carnet de otro usuario distinto al propio
if (!es_superadmin() && $usuarioId !== (int)$_SESSION['usuario_id']) {
    $usuarioId = (int)$_SESSION['usuario_id'];
}

$stmtU = $conexion->prepare("SELECT u.id, u.comunidad_id, u.cedula, u.nombre_usuario, u.nombre_completo, u.telefono, u.foto, u.rol, u.estado, c.nombre AS comunidad
                              FROM usuarios u
                              LEFT JOIN comunidades c ON u.comunidad_id = c.id
                              WHERE u.id = ? LIMIT 1");
if (!$stmtU) {
    die('Error al consultar usuario: ' . e($conexion->error));
}
$stmtU->bind_param('i', $usuarioId);
$stmtU->execute();
$usuarioCarnet = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

if (!$usuarioCarnet) {
    header('Location: ' . base_url('dashboard.php?error=usuario_no_encontrado'));
    exit;
}

$fotoUsuarioUrl = '';
if (!empty($usuarioCarnet['foto']) && file_exists(BASE_PATH . '/' . $usuarioCarnet['foto'])) {
    $fotoUsuarioUrl = base_url($usuarioCarnet['foto']);
} elseif (file_exists(BASE_PATH . '/uploads/usuarios/default_avatar.png')) {
    $fotoUsuarioUrl = base_url('uploads/usuarios/default_avatar.png');
}

$nombreComunidad = !empty($usuarioCarnet['comunidad'])
    ? $usuarioCarnet['comunidad']
    : (es_superadmin() ? 'Todas / Nivel Central' : 'Sin comunidad asignada');

$estadoTexto = ((int)$usuarioCarnet['estado'] === 1) ? 'ACTIVO' : 'INACTIVO';

$qrUsuarioPayload = json_encode([
    'tipo'       => 'usuario_institucional',
    'usuario_id' => (int)$usuarioCarnet['id'],
    'usuario'    => $usuarioCarnet['nombre_usuario'],
    'nombre'     => $usuarioCarnet['nombre_completo'],
    'cedula'     => $usuarioCarnet['cedula'] ?: 'N/A',
    'rol'        => $usuarioCarnet['rol'],
    'comunidad'  => $nombreComunidad,
    'estado'     => $estadoTexto
], JSON_UNESCAPED_UNICODE);

$tituloPagina = 'Carnet Institucional de Usuario';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header no-print">
    <div>
        <h2>Carnet Institucional de Usuario</h2>
        <p class="text-muted">Credencial de identificación para voceros y administradores</p>
    </div>
    <div class="actions-cell">
        <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Imprimir Carnet</button>
        <a href="<?= e(base_url('dashboard.php')) ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="carnet-wrapper">
    <div class="carnet-card carnet-usuario carnet-ampliado">
        <div class="carnet-top">
            <img src="<?= e(base_url($configSistema['logo_sistema'])) ?>" alt="Logo" class="carnet-logo" onerror="this.style.display='none';">
            <div>
                <div class="carnet-org"><?= e($configSistema['nombre_sistema']) ?></div>
                <div class="carnet-sub">Credencial de Operador / Vocero</div>
            </div>
        </div>

        <div class="carnet-body carnet-body-ampliado">
            <div class="carnet-photo-box">
                <?php if ($fotoUsuarioUrl !== ''): ?>
                    <img src="<?= e($fotoUsuarioUrl) ?>" alt="Fotografía del usuario" class="carnet-photo">
                <?php else: ?>
                    <div class="carnet-photo-placeholder">Sin Foto</div>
                <?php endif; ?>
            </div>

            <div class="carnet-info">
                <h3 class="carnet-name"><?= e($usuarioCarnet['nombre_completo']) ?></h3>
                <p><strong>Rol:</strong> <?= e($usuarioCarnet['rol']) ?></p>
                <p><strong>Cédula:</strong> <?= e($usuarioCarnet['cedula'] ?: 'No registrada') ?></p>
                <p><strong>Usuario:</strong> @<?= e($usuarioCarnet['nombre_usuario']) ?></p>
                <p><strong>Teléfono:</strong> <?= e($usuarioCarnet['telefono'] ?: 'No registrado') ?></p>
                <p><strong>Comunidad:</strong> <?= e($nombreComunidad) ?></p>
            </div>

            <div class="carnet-qr-box">
                <div id="qrCanvasContainer" data-qr="<?= e($qrUsuarioPayload) ?>"></div>
                <small>ID #<?= str_pad((string)(int)$usuarioCarnet['id'], 5, '0', STR_PAD_LEFT) ?></small>
            </div>
        </div>

        <div class="carnet-footer">
            <span>ID #<?= str_pad((string)(int)$usuarioCarnet['id'], 5, '0', STR_PAD_LEFT) ?></span>
            <span><?= e($estadoTexto) ?></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>