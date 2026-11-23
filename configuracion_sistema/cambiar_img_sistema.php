<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
exigir_rol(['SuperAdmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['logo_archivo']['name'])) {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('No se recibió ningún archivo.')));
    exit;
}

$archivo = $_FILES['logo_archivo'];
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('Error al subir el archivo.')));
    exit;
}

$maxBytes = 2 * 1024 * 1024;
if ($archivo['size'] > $maxBytes) {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('La imagen supera el límite de 2 MB.')));
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
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('Formato no permitido. Usa JPG, PNG o WEBP.')));
    exit;
}

$dirDestino = BASE_PATH . '/uploads/sistema';
if (!is_dir($dirDestino) && !mkdir($dirDestino, 0775, true)) {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('No se pudo crear la carpeta uploads/sistema.')));
    exit;
}

$nombreArchivo = 'logo_sistema_' . time() . '.' . $permitidos[$mime];
$rutaFisica = $dirDestino . '/' . $nombreArchivo;
$rutaRelativa = 'uploads/sistema/' . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('No se pudo mover la imagen a uploads/sistema. Revisa permisos en Linux.')));
    exit;
}

$stmt = $conexion->prepare("UPDATE configuracion SET logo_sistema = ? ORDER BY id ASC LIMIT 1");
$stmt->bind_param('s', $rutaRelativa);
$stmt->execute();
$stmt->close();

header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=logo_ok'));
exit;