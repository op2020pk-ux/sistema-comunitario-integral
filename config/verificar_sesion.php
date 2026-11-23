<?php
require_once __DIR__ . '/conexion.php';

if (empty($_SESSION['usuario_id']) || empty($_SESSION['nombre_usuario'])) {
    header('Location: ' . base_url('index.php?error=sesion'));
    exit;
}

$uid = (int)$_SESSION['usuario_id'];
$stmt = $conexion->prepare("SELECT id, comunidad_id, nombre_usuario, nombre_completo, rol, estado, foto FROM usuarios WHERE id = ? LIMIT 1");
if (!$stmt) {
    die('Error en consulta de sesión: ' . e($conexion->error));
}
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$usuarioActual = $res->fetch_assoc();
$stmt->close();

if (!$usuarioActual || (int)$usuarioActual['estado'] !== 1) {
    session_unset();
    session_destroy();
    header('Location: ' . base_url('index.php?error=inactivo'));
    exit;
}

$_SESSION['comunidad_id']    = $usuarioActual['comunidad_id'] !== null ? (int)$usuarioActual['comunidad_id'] : null;
$_SESSION['nombre_completo'] = $usuarioActual['nombre_completo'];
$_SESSION['rol']             = $usuarioActual['rol'];
$_SESSION['foto_usuario']    = $usuarioActual['foto'] ?: 'uploads/usuarios/default_avatar.png';

function es_superadmin(): bool {
    return ($_SESSION['rol'] ?? '') === 'SuperAdmin';
}

function es_admin_comunidad(): bool {
    return in_array($_SESSION['rol'] ?? '', ['SuperAdmin', 'Administrador Comunidad'], true);
}

function exigir_rol(array $rolesPermitidos): void {
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, $rolesPermitidos, true)) {
        header('Location: ' . base_url('dashboard.php?error=permisos'));
        exit;
    }
}

function comunidad_filtro(?int $comunidadSolicitada = null): ?int {
    if (es_superadmin()) {
        return $comunidadSolicitada ?: null;
    }
    return $_SESSION['comunidad_id'] ?? 0;
}