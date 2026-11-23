<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Caracas');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sistema_comunitario_integral';

$conexion = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conexion->connect_errno) {
    die('Error de conexión a MySQL (' . $conexion->connect_errno . '): ' . htmlspecialchars($conexion->connect_error));
}

$conexion->set_charset('utf8mb4');

// Ruta física base del proyecto
define('BASE_PATH', realpath(__DIR__ . '/..'));

// Detección dinámica de BASE_URL (compatible con /sistema_comunitario o subcarpetas en XAMPP)
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
$baseUrl = '/sistema_comunitario';

if ($docRoot && strpos(BASE_PATH, $docRoot) === 0) {
    $relPath = str_replace('\\', '/', substr(BASE_PATH, strlen($docRoot)));
    $baseUrl = '/' . trim($relPath, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
}

define('BASE_URL', $baseUrl);

// Carga configuración general del sistema
$configSistema = [
    'nombre_sistema' => 'Sistema Comunitario Integral',
    'subtitulo'      => 'Gestión Integral de Consejos Comunales y Censos',
    'logo_sistema'   => 'uploads/sistema/default_logo.png'
];

$resConfig = $conexion->query("SELECT nombre_sistema, subtitulo, logo_sistema FROM configuracion ORDER BY id ASC LIMIT 1");
if ($resConfig && $filaConfig = $resConfig->fetch_assoc()) {
    $configSistema['nombre_sistema'] = $filaConfig['nombre_sistema'] ?: $configSistema['nombre_sistema'];
    $configSistema['subtitulo']      = $filaConfig['subtitulo'] ?: $configSistema['subtitulo'];
    $configSistema['logo_sistema']   = $filaConfig['logo_sistema'] ?: $configSistema['logo_sistema'];
}

function e(?string $valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function base_url(string $ruta = ''): string {
    return BASE_URL . '/' . ltrim($ruta, '/');
}