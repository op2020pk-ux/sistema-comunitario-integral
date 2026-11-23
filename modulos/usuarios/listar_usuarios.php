<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';
exigir_rol(['SuperAdmin', 'Administrador Comunidad']);

$comFiltro = comunidad_filtro(null);

$sql = "SELECT u.*, c.nombre AS comunidad
        FROM usuarios u
        LEFT JOIN comunidades c ON u.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sql .= " WHERE u.comunidad_id = " . (int)$comFiltro;
}
$sql .= " ORDER BY u.nombre_completo ASC";
$resUsuarios = $conexion->query($sql);

$mensaje = '';
$tipoMsg = 'success';
if (!empty($_GET['msg'])) {
    $map = [
        'guardado_ok'     => 'Usuario guardado correctamente.',
        'foto_ok'         => 'Fotografía de perfil actualizada.',
        'foto_eliminada'  => 'Fotografía eliminada y restaurada al avatar por defecto.',
        'ficha_importada' => 'Ficha de usuario importada correctamente.',
        'error'           => $_GET['detalle'] ?? 'Ocurrió un error.'
    ];
    if (isset($map[$_GET['msg']])) {
        $mensaje = $map[$_GET['msg']];
        $tipoMsg = $_GET['msg'] === 'error' ? 'error' : 'success';
    }
}

$tituloPagina = 'Usuarios del Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Usuarios, Voceros y Administradores</h2>
        <p class="text-muted">Control de acceso, fotografía de perfil, carnets e intercambio de fichas</p>
    </div>
    <a href="<?= e(base_url('modulos/usuarios/guardar_usuario.php')) ?>" class="btn btn-primary">+ Nuevo Usuario</a>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= e($tipoMsg) ?>"><?= e($mensaje) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Cédula</th>
                    <th>Rol</th>
                    <th>Comunidad</th>
                    <th>Estado</th>
                    <th>Gestión de Foto y Ficha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resUsuarios && $resUsuarios->num_rows > 0): ?>
                    <?php while ($u = $resUsuarios->fetch_assoc()): ?>
                        <?php
                        $foto = !empty($u['foto']) ? $u['foto'] : 'uploads/usuarios/default_avatar.png';
                        $tieneFotoPersonal = ($foto !== 'uploads/usuarios/default_avatar.png');
                        $accionFoto = $tieneFotoPersonal ? 'img_usuarios/editar_img_usuario.php' : 'img_usuarios/subir_img_usuario.php';
                        ?>
                        <tr>
                            <td>
                                <img src="<?= e(base_url($foto)) ?>" alt="Foto" class="user-avatar" onerror="this.style.display='none';">
                            </td>
                            <td><strong><?= e($u['nombre_completo']) ?></strong></td>
                            <td><?= e($u['nombre_usuario']) ?></td>
                            <td><?= e($u['cedula'] ?: 'N/A') ?></td>
                            <td><?= e($u['rol']) ?></td>
                            <td><?= e($u['comunidad'] ?: 'Nivel Central') ?></td>
                            <td><?= (int)$u['estado'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <form method="POST" action="<?= e(base_url($accionFoto)) ?>" enctype="multipart/form-data" class="inline-form">
                                    <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
                                    <input type="file" name="foto_usuario" accept=".jpg,.jpeg,.png,.webp" required style="max-width: 170px;">
                                    <button type="submit" class="btn btn-sm"><?= $tieneFotoPersonal ? 'Cambiar' : 'Subir' ?></button>
                                </form>
                                <?php if ($tieneFotoPersonal): ?>
                                    <a href="<?= e(base_url('img_usuarios/eliminar_img_usuario.php?usuario_id=' . (int)$u['id'])) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar fotografía de este usuario?');">Quitar Foto</a>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a href="<?= e(base_url('modulos/usuarios/guardar_usuario.php?id=' . (int)$u['id'])) ?>" class="btn btn-sm">Editar</a>
                                <a href="<?= e(base_url('carnet/carnet_usuario.php?id=' . (int)$u['id'])) ?>" class="btn btn-sm btn-secondary">Carnet</a>
                                <a href="<?= e(base_url('img_usuarios/ficha_usuario_io.php?exportar_id=' . (int)$u['id'])) ?>" class="btn btn-sm btn-outline">Exportar Ficha</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No hay usuarios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>Importar Ficha de Usuario (Foto + Datos Personales)</h3>
    <p class="text-muted">Carga un archivo <code>.json</code> exportado previamente para crear o actualizar la ficha de un usuario.</p>
    <form method="POST" action="<?= e(base_url('img_usuarios/ficha_usuario_io.php')) ?>" enctype="multipart/form-data" class="inline-form">
        <input type="file" name="archivo_ficha" accept=".json" required>
        <button type="submit" class="btn btn-secondary">Importar Ficha JSON</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>