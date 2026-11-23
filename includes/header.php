<?php
if (!isset($tituloPagina)) {
    $tituloPagina = $configSistema['nombre_sistema'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> - <?= e($configSistema['nombre_sistema']) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/estilos.css')) ?>">
</head>
<body class="app-layout">
    <header class="top-navbar">
        <div class="brand-box">
            <button type="button" id="btnToggleSidebar" class="btn-icon" aria-label="Menú">☰</button>
            <img src="<?= e(base_url($configSistema['logo_sistema'])) ?>" alt="Logo" class="brand-logo" onerror="this.style.display='none';">
            <span class="brand-title"><?= e($configSistema['nombre_sistema']) ?></span>
        </div>

        <div class="user-box">
            <img src="<?= e(base_url($_SESSION['foto_usuario'] ?? 'uploads/usuarios/default_avatar.png')) ?>" alt="Avatar" class="user-avatar" onerror="this.style.display='none';">
            <div class="user-meta">
                <strong><?= e($_SESSION['nombre_completo'] ?? 'Usuario') ?></strong>
                <small><?= e($_SESSION['rol'] ?? '') ?></small>
            </div>
            <a href="<?= e(base_url('carnet/carnet_usuario.php')) ?>" class="btn btn-sm btn-outline">Mi Carnet</a>
            <a href="<?= e(base_url('cerrar_sesion.php')) ?>" class="btn btn-sm btn-danger">Salir</a>
        </div>
    </header>
    <div class="main-wrapper">