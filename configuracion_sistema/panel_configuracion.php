<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
exigir_rol(['SuperAdmin']);

$mensaje = '';
$tipoMensaje = 'success';

if (!empty($_GET['msg'])) {
    $mapaMsg = [
        'nombre_ok' => 'Nombre y subtítulo del sistema actualizados correctamente.',
        'logo_ok'   => 'Logotipo del sistema actualizado correctamente.',
        'error'     => $_GET['detalle'] ?? 'Ocurrió un error al procesar la solicitud.'
    ];
    if (isset($mapaMsg[$_GET['msg']])) {
        $mensaje = $mapaMsg[$_GET['msg']];
        $tipoMensaje = $_GET['msg'] === 'error' ? 'error' : 'success';
    }
}

$tituloPagina = 'Configuración del Sistema';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Configuración General del Sistema</h2>
        <p class="text-muted">Personaliza identidad institucional y respalda la base de datos</p>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= e($tipoMensaje) ?>"><?= e($mensaje) ?></div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <h3>Identidad Institucional</h3>
        <form method="POST" action="<?= e(base_url('configuracion_sistema/cambiar_nombre_sistema.php')) ?>" class="form-vertical">
            <div class="form-group">
                <label for="nombre_sistema">Nombre del Sistema</label>
                <input type="text" id="nombre_sistema" name="nombre_sistema" required value="<?= e($configSistema['nombre_sistema']) ?>">
            </div>
            <div class="form-group">
                <label for="subtitulo">Subtítulo / Descripción</label>
                <input type="text" id="subtitulo" name="subtitulo" value="<?= e($configSistema['subtitulo']) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Nombre</button>
        </form>
    </div>

    <div class="card">
        <h3>Logotipo del Sistema</h3>
        <form method="POST" action="<?= e(base_url('configuracion_sistema/cambiar_img_sistema.php')) ?>" enctype="multipart/form-data" class="form-vertical">
            <div class="form-group">
                <label>Logo actual</label>
                <div>
                    <img id="previewLogo" src="<?= e(base_url($configSistema['logo_sistema'])) ?>" alt="Logo actual" class="preview-img" onerror="this.style.display='none';">
                </div>
            </div>
            <div class="form-group">
                <label for="logo_archivo">Seleccionar nueva imagen (JPG, PNG, WEBP, máx. 2 MB)</label>
                <input type="file" id="logo_archivo" name="logo_archivo" accept=".jpg,.jpeg,.png,.webp" data-preview="previewLogo" required>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Logo</button>
        </form>
    </div>
</div>

<div class="card">
    <h3>Respaldo de Base de Datos</h3>
    <p class="text-muted">Genera y descarga un archivo <code>.sql</code> con la estructura y datos de <code>sistema_comunitario_integral</code>.</p>
    <a href="<?= e(base_url('configuracion_sistema/respaldar_base_datos.php')) ?>" class="btn btn-secondary">Descargar Respaldo (.SQL)</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>