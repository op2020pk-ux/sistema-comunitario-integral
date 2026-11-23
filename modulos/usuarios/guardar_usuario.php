<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';
exigir_rol(['SuperAdmin', 'Administrador Comunidad']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esEdicion = ($id > 0);
$error = '';

$u = [
    'comunidad_id'    => es_superadmin() ? null : ($_SESSION['comunidad_id'] ?? null),
    'cedula'          => '',
    'nombre_usuario'  => '',
    'nombre_completo' => '',
    'telefono'        => '',
    'foto'            => 'uploads/usuarios/default_avatar.png',
    'rol'             => es_superadmin() ? 'Administrador Comunidad' : 'Vocero/Censador',
    'estado'          => 1
];

if ($esEdicion) {
    $stmt = $conexion->prepare("SELECT id, comunidad_id, cedula, nombre_usuario, nombre_completo, telefono, foto, rol, estado FROM usuarios WHERE id = ? LIMIT 1");
    if (!$stmt) {
        die('Error al consultar usuario: ' . e($conexion->error));
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existente) {
        header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?error=no_encontrado'));
        exit;
    }

    if (!es_superadmin() && (int)$existente['comunidad_id'] !== (int)($_SESSION['comunidad_id'] ?? 0)) {
        header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?error=permisos'));
        exit;
    }

    $u = $existente;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u['nombre_usuario']  = trim($_POST['nombre_usuario'] ?? '');
    $u['nombre_completo'] = trim($_POST['nombre_completo'] ?? '');
    $u['cedula']          = trim($_POST['cedula'] ?? '');
    $u['telefono']        = trim($_POST['telefono'] ?? '');
    $u['estado']          = isset($_POST['estado']) ? 1 : 0;
    $passwordPlano        = (string)($_POST['password'] ?? '');

    if (es_superadmin()) {
        $comInput = $_POST['comunidad_id'] ?? '';
        $u['comunidad_id'] = ($comInput !== '' && (int)$comInput > 0) ? (int)$comInput : null;
        $rolInput = $_POST['rol'] ?? 'Vocero/Censador';
        $u['rol'] = in_array($rolInput, ['SuperAdmin', 'Administrador Comunidad', 'Vocero/Censador'], true) ? $rolInput : 'Vocero/Censador';
    } else {
        $u['comunidad_id'] = $_SESSION['comunidad_id'] !== null ? (int)$_SESSION['comunidad_id'] : null;
        $rolInput = $_POST['rol'] ?? 'Vocero/Censador';
        $u['rol'] = ($rolInput === 'SuperAdmin') ? 'Vocero/Censador' : (in_array($rolInput, ['Administrador Comunidad', 'Vocero/Censador'], true) ? $rolInput : 'Vocero/Censador');
    }

    if ($u['nombre_usuario'] === '' || $u['nombre_completo'] === '') {
        $error = 'El nombre de usuario y el nombre completo son obligatorios.';
    } elseif (!$esEdicion && $passwordPlano === '') {
        $error = 'Debes asignar una contraseña para el nuevo usuario.';
    }

    $fotoAnterior = $u['foto'] ?: 'uploads/usuarios/default_avatar.png';
    $rutaFotoNueva = null;

    if ($error === '' && !empty($_FILES['foto_usuario']['name'])) {
        $archivo = $_FILES['foto_usuario'];
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error al subir la fotografía del usuario.';
        } elseif ($archivo['size'] > 3 * 1024 * 1024) {
            $error = 'La fotografía no debe superar los 3 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($archivo['tmp_name']);
            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($permitidos[$mime])) {
                $error = 'Formato de imagen no permitido. Usa JPG, PNG o WEBP.';
            } else {
                $dirUploads = BASE_PATH . '/uploads/usuarios';
                if (!is_dir($dirUploads) && !mkdir($dirUploads, 0777, true)) {
                    $error = 'No se pudo crear el directorio uploads/usuarios.';
                } else {
                    $baseNombre = preg_replace('/[^0-9A-Za-z_-]/', '', $u['nombre_usuario'] ?: ('usr_' . ($id ?: time())));
                    $nombreArchivo = 'usr_' . $baseNombre . '_' . time() . '_' . rand(100, 999) . '.' . $permitidos[$mime];
                    $rutaFisica = $dirUploads . '/' . $nombreArchivo;

                    if (move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
                        @chmod($rutaFisica, 0666);
                        $rutaFotoNueva = 'uploads/usuarios/' . $nombreArchivo;
                    } else {
                        $error = 'No se pudo guardar la imagen en uploads/usuarios/. Verifica los permisos.';
                    }
                }
            }
        }
    }

    if ($error === '') {
        $cedVal = $u['cedula'] !== '' ? $u['cedula'] : null;
        $telVal = $u['telefono'] !== '' ? $u['telefono'] : null;
        $fotoFinal = $rutaFotoNueva !== null ? $rutaFotoNueva : $fotoAnterior;

        if ($esEdicion) {
            if ($passwordPlano !== '') {
                $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("UPDATE usuarios SET comunidad_id = ?, cedula = ?, nombre_usuario = ?, password_hash = ?, nombre_completo = ?, telefono = ?, foto = ?, rol = ?, estado = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('isssssssii', $u['comunidad_id'], $cedVal, $u['nombre_usuario'], $hash, $u['nombre_completo'], $telVal, $fotoFinal, $u['rol'], $u['estado'], $id);
                }
            } else {
                $stmt = $conexion->prepare("UPDATE usuarios SET comunidad_id = ?, cedula = ?, nombre_usuario = ?, nombre_completo = ?, telefono = ?, foto = ?, rol = ?, estado = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('issssssii', $u['comunidad_id'], $cedVal, $u['nombre_usuario'], $u['nombre_completo'], $telVal, $fotoFinal, $u['rol'], $u['estado'], $id);
                }
            }

            if (!$stmt) {
                $error = 'Error al preparar actualización: ' . $conexion->error;
            } elseif ($stmt->execute()) {
                $stmt->close();

                if (
                    $rutaFotoNueva !== null &&
                    !empty($fotoAnterior) &&
                    $fotoAnterior !== 'uploads/usuarios/default_avatar.png' &&
                    strpos($fotoAnterior, 'uploads/usuarios/') === 0 &&
                    $fotoAnterior !== $rutaFotoNueva &&
                    file_exists(BASE_PATH . '/' . $fotoAnterior)
                ) {
                    @unlink(BASE_PATH . '/' . $fotoAnterior);
                }

                if ($id === (int)$_SESSION['usuario_id']) {
                    $_SESSION['foto_usuario'] = $fotoFinal;
                    $_SESSION['nombre_completo'] = $u['nombre_completo'];
                }

                header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=guardado_ok'));
                exit;
            } else {
                if ($rutaFotoNueva !== null && file_exists(BASE_PATH . '/' . $rutaFotoNueva)) {
                    @unlink(BASE_PATH . '/' . $rutaFotoNueva);
                }
                $error = 'Error al actualizar usuario (verifica que usuario o cédula no estén duplicados): ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO usuarios (comunidad_id, cedula, nombre_usuario, password_hash, nombre_completo, telefono, foto, rol, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $error = 'Error al preparar inserción: ' . $conexion->error;
            } else {
                $stmt->bind_param('isssssssi', $u['comunidad_id'], $cedVal, $u['nombre_usuario'], $hash, $u['nombre_completo'], $telVal, $fotoFinal, $u['rol'], $u['estado']);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=guardado_ok'));
                    exit;
                } else {
                    if ($rutaFotoNueva !== null && file_exists(BASE_PATH . '/' . $rutaFotoNueva)) {
                        @unlink(BASE_PATH . '/' . $rutaFotoNueva);
                    }
                    $error = 'Error al crear usuario (verifica que usuario o cédula no estén duplicados): ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

$comunidadesRes = $conexion->query("SELECT id, nombre FROM comunidades ORDER BY nombre ASC");

$fotoActualUrl = '';
if (!empty($u['foto']) && file_exists(BASE_PATH . '/' . $u['foto'])) {
    $fotoActualUrl = base_url($u['foto']);
} elseif (file_exists(BASE_PATH . '/uploads/usuarios/default_avatar.png')) {
    $fotoActualUrl = base_url('uploads/usuarios/default_avatar.png');
}

$tituloPagina = $esEdicion ? 'Editar Usuario' : 'Nuevo Usuario';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2><?= $esEdicion ? 'Editar Usuario #' . (int)$id : 'Registrar Nuevo Usuario' ?></h2>
        <p class="text-muted">Configuración de acceso, rol, comunidad y fotografía del usuario</p>
    </div>
    <a href="<?= e(base_url('modulos/usuarios/listar_usuarios.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <div class="form-group">
            <label for="nombre_usuario">Nombre de usuario *</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario" required value="<?= e($u['nombre_usuario']) ?>">
        </div>

        <div class="form-group">
            <label for="nombre_completo">Nombre completo *</label>
            <input type="text" name="nombre_completo" id="nombre_completo" required value="<?= e($u['nombre_completo']) ?>">
        </div>

        <div class="form-group">
            <label for="cedula">Cédula</label>
            <input type="text" name="cedula" id="cedula" value="<?= e($u['cedula'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono" value="<?= e($u['telefono'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="password"><?= $esEdicion ? 'Nueva contraseña (dejar vacío para mantener la actual)' : 'Contraseña *' ?></label>
            <input type="password" name="password" id="password" <?= $esEdicion ? '' : 'required' ?> placeholder="••••••••">
        </div>

        <div class="form-group">
            <label for="rol">Rol en el sistema</label>
            <select name="rol" id="rol">
                <?php if (es_superadmin()): ?>
                    <option value="SuperAdmin" <?= ($u['rol'] ?? '') === 'SuperAdmin' ? 'selected' : '' ?>>SuperAdmin</option>
                <?php endif; ?>
                <option value="Administrador Comunidad" <?= ($u['rol'] ?? '') === 'Administrador Comunidad' ? 'selected' : '' ?>>Administrador Comunidad</option>
                <option value="Vocero/Censador" <?= ($u['rol'] ?? '') === 'Vocero/Censador' ? 'selected' : '' ?>>Vocero/Censador</option>
            </select>
        </div>

        <?php if (es_superadmin()): ?>
            <div class="form-group">
                <label for="comunidad_id">Comunidad asignada (vacío = todas)</label>
                <select name="comunidad_id" id="comunidad_id">
                    <option value="">-- Todas las comunidades (SuperAdmin) --</option>
                    <?php if ($comunidadesRes): ?>
                        <?php while ($cm = $comunidadesRes->fetch_assoc()): ?>
                            <option value="<?= (int)$cm['id'] ?>" <?= ((int)($u['comunidad_id'] ?? 0) === (int)$cm['id']) ? 'selected' : '' ?>>
                                <?= e($cm['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="foto_usuario">Fotografía de usuario (JPG, PNG, WEBP - máx. 3 MB)</label>
            <input type="file" name="foto_usuario" id="foto_usuario" accept=".jpg,.jpeg,.png,.webp" data-preview="previewGuardarUsuario">
        </div>

        <div class="form-group">
            <label>Vista previa / Foto actual</label>
            <div>
                <img id="previewGuardarUsuario"
                     src="<?= e($fotoActualUrl) ?>"
                     alt="Foto de usuario"
                     class="preview-img"
                     style="<?= $fotoActualUrl !== '' ? 'display: inline-block;' : 'display: none;' ?>">
            </div>
        </div>

        <div class="form-group full-width">
            <label>
                <input type="checkbox" name="estado" value="1" <?= !empty($u['estado']) ? 'checked' : '' ?>>
                Usuario activo en el sistema
            </label>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar Cambios' : 'Crear Usuario' ?></button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>