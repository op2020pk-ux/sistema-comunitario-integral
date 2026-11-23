<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
exigir_rol(['SuperAdmin']);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'sistema_comunitario_integral';

// Busca mysqldump en XAMPP para Linux o en PATH del sistema
$mysqldumpBin = '/opt/lampp/bin/mysqldump';
if (!file_exists($mysqldumpBin) || !is_executable($mysqldumpBin)) {
    $mysqldumpBin = 'mysqldump';
}

$fechaArchivo = date('Y-m-d_H-i-s');
$nombreSql = $dbName . '_' . $fechaArchivo . '.sql';

$cmd = escapeshellcmd($mysqldumpBin)
     . ' --host=' . escapeshellarg($dbHost)
     . ' --user=' . escapeshellarg($dbUser);

if ($dbPass !== '') {
    $cmd .= ' --password=' . escapeshellarg($dbPass);
}

$cmd .= ' --routines --triggers ' . escapeshellarg($dbName) . ' 2>&1';

$salida = [];
$codigoRetorno = 0;
exec($cmd, $salida, $codigoRetorno);

if ($codigoRetorno !== 0 || empty($salida)) {
    $detalle = 'No se pudo generar el respaldo SQL. Verifica mysqldump en /opt/lampp/bin/mysqldump.';
    header('Location: ' . base_url('configuracion_sistema/panel_configuracion.php?msg=error&detalle=' . urlencode($detalle)));
    exit;
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreSql . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo implode("\n", $salida) . "\n";
exit;