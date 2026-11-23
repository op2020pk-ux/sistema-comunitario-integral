<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

$usuarioId = isset($_REQUEST['usuario_id']) ? (int)$_REQUEST['usuario_id'] : (int)$_SESSION['usuario_id'];

if ($usuarioId !== (int)$_SESSION['usuario_id'] && !es_admin_comunidad()) {
    header('Location: ' . base_url('dashboard.php?error=permisos'));
    exit;
}

$stmtSel = $conexion->prepare("SELECT foto FROM usuarios WHERE id = ? LIMIT 1");
$stmtSel->bind_param('i', $usuarioId);
$stmtSel->execute();
$rowUser = $stmtSel->get_result()->fetch_assoc();
$stmtSel->close();

if ($rowUser) {
    $fotoActual = $rowUser['foto'] ?? '';
    if (
        $fotoActual !== '' &&
        $fotoActual !== 'uploads/usuarios/default_avatar.png' &&
        strpos($fotoActual, 'uploads/usuarios/') === 0
    ) {
        $fisicaActual = BASE_PATH . '/' . $fotoActual;
        if (file_exists($fisicaActual)) {
            @unlink($fisicaActual);
        }
    }

    $avatarDefault = 'uploads/usuarios/default_avatar.png';
    $stmtUpd = $conexion->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
    $stmtUpd->bind_param('si', $avatarDefault, $usuarioId);
    $stmtUpd->execute();
    $stmtUpd->close();

    if ($usuarioId === (int)$_SESSION['usuario_id']) {
        $_SESSION['foto_usuario'] = $avatarDefault;
    }
}

header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=foto_eliminada'));
exit;