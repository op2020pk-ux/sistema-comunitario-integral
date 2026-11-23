<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(null);
$usuarioId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['usuario_id'] ?? 0);

// Solo SuperAdmin puede cambiar la foto de otro usuario distinto a sí mismo
if (!es_superadmin() && $usuarioId !== (int)$_SESSION['usuario_id']) {
    $usuarioId = (int)$_SESSION['usuario_id'];
}

$error = '';
$exito = '';

// Lista de usuarios disponibles para seleccionar
$sqlUsuarios = "SELECT u.id, u.nombre_usuario, u.nombre_completo, u.cedula, u.rol, u.foto, u.comunidad_id, c.nombre AS comunidad
                FROM usuarios u
                LEFT JOIN comunidades c ON u.comunidad_id = c.id";
if (!es_superadmin()) {
    $sqlUsuarios .= " WHERE u.id = " . (int)$_SESSION['usuario_id'];
}
$sqlUsuarios .= " ORDER BY u.nombre_completo ASC";
$listaUsuarios = $conexion->query($sqlUsuarios);

if (!$listaUsuarios) {
    die('Error al consultar usuarios: ' . e($conexion->error));
}

// Cargar datos del usuario seleccionado
$usuarioTarget = null;
if ($usuarioId > 0) {
    $stmtU = $conexion->prepare("SELECT id, comunidad_id, cedula, nombre_usuario, nombre_completo, rol, foto, estado FROM usuarios WHERE id = ? LIMIT 1");
    if ($stmtU) {
        $stmtU->bind_param('i', $usuarioId);
        $stmtU->execute();
        $usuarioTarget = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();
    }
}

if (!$usuarioTarget) {
    $usuarioId = (int)$_SESSION['usuario_id'];
    $stmtU = $conexion->prepare("SELECT id, comunidad_id, cedula, nombre_usuario, nombre_completo, rol, foto, estado FROM usuarios WHERE id = ? LIMIT 1");
    if ($stmtU) {
        $stmtU->bind_param('i', $usuarioId);
        $stmtU->execute();
        $usuarioTarget = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuarioTarget) {
    $postUserId = (int)($_POST['usuario_id'] ?? $usuarioTarget['id']);
    if (!es_superadmin() && $postUserId !== (int)$_SESSION['usuario_id']) {
        $postUserId = (int)$_SESSION['usuario_id'];
    }

    if (empty($_FILES['foto_usuario']['name'])) {
        $error = 'Por favor selecciona una imagen (JPG, PNG o WEBP) antes de guardar.';
    } else {
        $archivo = $_FILES['foto_usuario'];
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error al recibir la imagen en el servidor.';
        } elseif ($archivo['size'] > 3 * 1024 * 1024) {
            $error = 'La imagen no debe superar los 3 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($archivo['tmp_name']);
            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($permitidos[$mime])) {
                $error = 'Formato no permitido. Solo se aceptan JPG, PNG o WEBP.';
            } else {
                $dirUploads = BASE_PATH . '/uploads/usuarios';
                if (!is_dir($dirUploads) && !mkdir($dirUploads, 0777, true)) {
                    $error = 'No se pudo crear el directorio uploads/usuarios.';
                } else {
                    $baseNombre = preg_replace('/[^0-9A-Za-z_-]/', '', $usuarioTarget['nombre_usuario'] ?: ('user_' . $postUserId));
                    $nombreArchivo = 'usr_' . $baseNombre . '_' . time() . '_' . rand(100, 999) . '.' . $permitidos[$mime];
                    $rutaFisica = $dirUploads . '/' . $nombreArchivo;

                    if (move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
                        @chmod($rutaFisica, 0666);
                        $rutaRelativa = 'uploads/usuarios/' . $nombreArchivo;
                        $fotoAnterior = $usuarioTarget['foto'] ?? '';

                        $stmtUpd = $conexion->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                        if ($stmtUpd) {
                            $stmtUpd->bind_param('si', $rutaRelativa, $postUserId);
                            if ($stmtUpd->execute()) {
                                $stmtUpd->close();

                                if (
                                    !empty($fotoAnterior) &&
                                    $fotoAnterior !== 'uploads/usuarios/default_avatar.png' &&
                                    strpos($fotoAnterior, 'uploads/usuarios/') === 0 &&
                                    $fotoAnterior !== $rutaRelativa &&
                                    file_exists(BASE_PATH . '/' . $fotoAnterior)
                                ) {
                                    @unlink(BASE_PATH . '/' . $fotoAnterior);
                                }

                                $usuarioTarget['foto'] = $rutaRelativa;
                                if ($postUserId === (int)$_SESSION['usuario_id']) {
                                    $_SESSION['foto_usuario'] = $rutaRelativa;
                                }
                                $exito = 'Fotografía del usuario actualizada correctamente.';
                            } else {
                                @unlink($rutaFisica);
                                $error = 'Error al guardar la ruta en la base de datos: ' . $stmtUpd->error;
                                $stmtUpd->close();
                            }
                        } else {
                            @unlink($rutaFisica);
                            $error = 'Error al preparar actualización: ' . $conexion->error;
                        }
                    } else {
                        $error = 'No se pudo mover la imagen a uploads/usuarios/. Verifica los permisos de la carpeta.';
                    }
                }
            }
        }
    }
}

$fotoActualUrl = '';
if (!empty($usuarioTarget['foto']) && file_exists(BASE_PATH . '/' . $usuarioTarget['foto'])) {
    $fotoActualUrl = base_url($usuarioTarget['foto']);
} elseif (file_exists(BASE_PATH . '/uploads/usuarios/default_avatar.png')) {
    $fotoActualUrl = base_url('uploads/usuarios/default_avatar.png');
}

$tituloPagina = 'Subir Imagen de Usuario';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Fotografía de Usuario</h2>
        <p class="text-muted">Sube o cambia la imagen del usuario en el sistema</p>
    </div>
    <a href="<?= e(base_url('modulos/usuarios/listar_usuarios.php')) ?>" class="btn btn-secondary">Volver a Usuarios</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($exito !== ''): ?>
    <div class="alert alert-success"><?= e($exito) ?></div>
<?php endif; ?>

<?php if (es_superadmin() && $listaUsuarios->num_rows > 0): ?>
    <div class="card" style="margin-bottom: 1rem;">
        <form method="GET" class="inline-form">
            <label for="id">Seleccionar usuario:</label>
            <select name="id" id="id" onchange="this.form.submit()">
                <?php while ($uRow = $listaUsuarios->fetch_assoc()): ?>
                    <option value="<?= (int)$uRow['id'] ?>" <?= ((int)$usuarioTarget['id'] === (int)$uRow['id']) ? 'selected' : '' ?>>
                        <?= e($uRow['nombre_completo']) ?> (@<?= e($uRow['nombre_usuario']) ?> - <?= e($uRow['rol']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <noscript><button type="submit" class="btn btn-secondary">Cargar</button></noscript>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="usuario_id" value="<?= (int)$usuarioTarget['id'] ?>">

        <div class="form-group full-width">
            <strong>Usuario seleccionado:</strong>
            <?= e($usuarioTarget['nombre_completo']) ?> (@<?= e($usuarioTarget['nombre_usuario']) ?>) —
            <span class="text-muted"><?= e($usuarioTarget['rol']) ?><?= !empty($usuarioTarget['cedula']) ? ' | C.I.: ' . e($usuarioTarget['cedula']) : '' ?></span>
        </div>

        <div class="form-group">
            <label for="foto_usuario">Seleccionar nueva fotografía (JPG, PNG, WEBP - máx. 3 MB)</label>
            <input type="file" name="foto_usuario" id="foto_usuario" accept=".jpg,.jpeg,.png,.webp" data-preview="previewSubirUsuario" required>
        </div>

        <div class="form-group">
            <label>Vista previa / Foto actual</label>
            <div>
                <img id="previewSubirUsuario"
                     src="<?= e($fotoActualUrl) ?>"
                     alt="Foto de usuario"
                     class="preview-img"
                     style="<?= $fotoActualUrl !== '' ? 'display: inline-block;' : 'display: none;' ?>">
            </div>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Imagen de Usuario</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>