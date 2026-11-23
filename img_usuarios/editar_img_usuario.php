<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php'));
    exit;
}

$usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : (int)$_SESSION['usuario_id'];

if ($usuarioId !== (int)$_SESSION['usuario_id'] && !es_admin_comunidad()) {
    header('Location: ' . base_url('dashboard.php?error=permisos'));
    exit;
}

if (empty($_FILES['foto_usuario']['name']) || $_FILES['foto_usuario']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('No se recibió la nueva imagen.')));
    exit;
}

// Obtiene foto anterior para eliminarla si no es la de por defecto
$stmtSel = $conexion->prepare("SELECT foto FROM usuarios WHERE id = ? LIMIT 1");
$stmtSel->bind_param('i', $usuarioId);
$stmtSel->execute();
$rowUser = $stmtSel->get_result()->fetch_assoc();
$stmtSel->close();

if (!$rowUser) {
    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('Usuario no encontrado.')));
    exit;
}

$archivo = $_FILES['foto_usuario'];
if ($archivo['size'] > 2 * 1024 * 1024) {
    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('La imagen supera los 2 MB.')));
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($archivo['tmp_name']);
$permitidos = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

if (!isset($permitidos[$mime])) {
    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('Formato no permitido.')));
    exit;
}

$dirDestino = BASE_PATH . '/uploads/usuarios';
if (!is_dir($dirDestino)) {
    mkdir($dirDestino, 0775, true);
}

$nombreArchivo = 'usr_' . $usuarioId . '_' . time() . '.' . $permitidos[$mime];
$rutaFisica = $dirDestino . '/' . $nombreArchivo;
$rutaRelativa = 'uploads/usuarios/' . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('No se pudo reemplazar la imagen.')));
    exit;
}

$fotoAnterior = $rowUser['foto'] ?? '';
if (
    $fotoAnterior !== '' &&
    $fotoAnterior !== 'uploads/usuarios/default_avatar.png' &&
    strpos($fotoAnterior, 'uploads/usuarios/') === 0
) {
    $fisicaAnterior = BASE_PATH . '/' . $fotoAnterior;
    if (file_exists($fisicaAnterior)) {
        @unlink($fisicaAnterior);
    }
}

$stmtUpd = $conexion->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
$stmtUpd->bind_param('si', $rutaRelativa, $usuarioId);
$stmtUpd->execute();
$stmtUpd->close();

if ($usuarioId === (int)$_SESSION['usuario_id']) {
    $_SESSION['foto_usuario'] = $rutaRelativa;
}

header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=foto_ok'));
exit;