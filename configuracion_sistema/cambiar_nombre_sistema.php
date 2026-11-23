<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
exigir_rol(['SuperAdmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php'));
    exit;
}

$nombreSistema = trim($_POST['nombre_sistema'] ?? '');
$subtitulo     = trim($_POST['subtitulo'] ?? '');

if ($nombreSistema === '') {
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode('El nombre del sistema no puede estar vacío.')));
    exit;
}

$stmt = $conexion->prepare("UPDATE configuracion SET nombre_sistema = ?, subtitulo = ? ORDER BY id ASC LIMIT 1");
$stmt->bind_param('ss', $nombreSistema, $subtitulo);
$stmt->execute();
$stmt->close();

header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=nombre_ok'));
exit;