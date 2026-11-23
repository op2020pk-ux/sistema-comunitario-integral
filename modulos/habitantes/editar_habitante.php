<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

// Asegura que la columna `foto` exista en `habitantes` sin romper nada
$resColFoto = $conexion->query("SHOW COLUMNS FROM habitantes LIKE 'foto'");
if ($resColFoto && $resColFoto->num_rows === 0) {
    $conexion->query("ALTER TABLE habitantes ADD COLUMN foto VARCHAR(255) NULL AFTER activo");
}

$comFiltro = comunidad_filtro(null);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($id <= 0) {
    header('Location: ' . base_url('modulos/habitantes/listar_habitantes.php'));
    exit;
}

$sqlHab = "SELECT * FROM habitantes WHERE id = ?";
if ($comFiltro !== null) {
    $sqlHab .= " AND comunidad_id = " . (int)$comFiltro;
}
$sqlHab .= " LIMIT 1";

$stmt = $conexion->prepare($sqlHab);
if (!$stmt) {
    die('Error al consultar habitante: ' . e($conexion->error));
}
$stmt->bind_param('i', $id);
$stmt->execute();
$h = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$h) {
    header('Location: ' . base_url('modulos/habitantes/listar_habitantes.php?error=no_encontrado'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $h['familia_id']          = (int)($_POST['familia_id'] ?? $h['familia_id']);
    $h['nacionalidad']        = in_array($_POST['nacionalidad'] ?? 'V', ['V', 'E'], true) ? $_POST['nacionalidad'] : 'V';
    $h['cedula']              = trim($_POST['cedula'] ?? '');
    $h['nombres']             = trim($_POST['nombres'] ?? '');
    $h['apellidos']           = trim($_POST['apellidos'] ?? '');
    $h['fecha_nacimiento']    = $_POST['fecha_nacimiento'] ?? '';
    $h['sexo']                = in_array($_POST['sexo'] ?? 'F', ['M', 'F'], true) ? $_POST['sexo'] : 'F';
    $h['parentesco']          = $_POST['parentesco'] ?? 'Otro';
    $h['estado_civil']        = $_POST['estado_civil'] ?? 'Soltero/a';
    $h['telefono']            = trim($_POST['telefono'] ?? '');
    $h['correo']              = trim($_POST['correo'] ?? '');
    $h['ocupacion']           = trim($_POST['ocupacion'] ?? '');
    $h['trabaja_actualmente'] = isset($_POST['trabaja_actualmente']) ? 1 : 0;
    $h['es_estudiante']       = isset($_POST['es_estudiante']) ? 1 : 0;
    $h['posee_discapacidad']  = isset($_POST['posee_discapacidad']) ? 1 : 0;
    $h['tipo_discapacidad']   = trim($_POST['tipo_discapacidad'] ?? '');
    $h['enfermedad_cronica']  = trim($_POST['enfermedad_cronica'] ?? '');
    $h['es_embarazada']       = ($h['sexo'] === 'F' && isset($_POST['es_embarazada'])) ? 1 : 0;
    $h['es_lactante']         = ($h['sexo'] === 'F' && isset($_POST['es_lactante'])) ? 1 : 0;

    $stmtFam = $conexion->prepare("SELECT comunidad_id FROM familias WHERE id = ? LIMIT 1");
    $stmtFam->bind_param('i', $h['familia_id']);
    $stmtFam->execute();
    $famRow = $stmtFam->get_result()->fetch_assoc();
    $stmtFam->close();

    if (!$famRow || ($comFiltro !== null && (int)$famRow['comunidad_id'] !== $comFiltro)) {
        $error = 'Familia no válida para esta comunidad.';
    } elseif ($h['nombres'] === '' || $h['apellidos'] === '' || $h['fecha_nacimiento'] === '') {
        $error = 'Nombres, apellidos y fecha de nacimiento son obligatorios.';
    }

    $fotoAnterior = $h['foto'] ?? null;
    $rutaFotoNueva = null;

    if ($error === '' && !empty($_FILES['foto_habitante']['name'])) {
        $archivo = $_FILES['foto_habitante'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error al subir la nueva fotografía.';
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
                $dirFotos = BASE_PATH . '/fotos';
                if (!is_dir($dirFotos) && !mkdir($dirFotos, 0777, true)) {
                    $error = 'No se pudo crear la carpeta fotos en el servidor.';
                } else {
                    $baseCedula = preg_replace('/[^0-9A-Za-z_-]/', '', $h['cedula'] ?: ('id_' . $id));
                    $nombreFoto = 'hab_' . $baseCedula . '_' . time() . '_' . rand(100, 999) . '.' . $permitidos[$mime];
                    $rutaFisica = $dirFotos . '/' . $nombreFoto;

                    if (move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
                        @chmod($rutaFisica, 0666);
                        $rutaFotoNueva = 'fotos/' . $nombreFoto;
                    } else {
                        $error = 'No se pudo guardar la nueva imagen en la carpeta fotos/. Revisa los permisos.';
                    }
                }
            }
        }
    }

    if ($error === '') {
        $comunidadId = (int)$famRow['comunidad_id'];
        $cedVal = $h['cedula'] !== '' ? $h['cedula'] : null;
        $fotoFinal = $rutaFotoNueva !== null ? $rutaFotoNueva : $fotoAnterior;

        $sqlUpd = "UPDATE habitantes SET
            comunidad_id = ?, familia_id = ?, nacionalidad = ?, cedula = ?,
            nombres = ?, apellidos = ?, fecha_nacimiento = ?, sexo = ?,
            parentesco = ?, estado_civil = ?, telefono = ?, correo = ?,
            ocupacion = ?, trabaja_actualmente = ?, es_estudiante = ?,
            posee_discapacidad = ?, tipo_discapacidad = ?, enfermedad_cronica = ?,
            es_embarazada = ?, es_lactante = ?, foto = ?
            WHERE id = ?";

        $stmtUpd = $conexion->prepare($sqlUpd);
        if (!$stmtUpd) {
            $error = 'Error al preparar actualización: ' . $conexion->error;
        } else {
            $stmtUpd->bind_param(
                'iisssssssssssiiissiisi',
                $comunidadId,
                $h['familia_id'],
                $h['nacionalidad'],
                $cedVal,
                $h['nombres'],
                $h['apellidos'],
                $h['fecha_nacimiento'],
                $h['sexo'],
                $h['parentesco'],
                $h['estado_civil'],
                $h['telefono'],
                $h['correo'],
                $h['ocupacion'],
                $h['trabaja_actualmente'],
                $h['es_estudiante'],
                $h['posee_discapacidad'],
                $h['tipo_discapacidad'],
                $h['enfermedad_cronica'],
                $h['es_embarazada'],
                $h['es_lactante'],
                $fotoFinal,
                $id
            );

            if ($stmtUpd->execute()) {
                $stmtUpd->close();

                // Si se subió una foto nueva y había una anterior dentro de fotos/, la eliminamos para no acumular basura
                if (
                    $rutaFotoNueva !== null &&
                    !empty($fotoAnterior) &&
                    strpos($fotoAnterior, 'fotos/') === 0 &&
                    $fotoAnterior !== $rutaFotoNueva &&
                    file_exists(BASE_PATH . '/' . $fotoAnterior)
                ) {
                    @unlink(BASE_PATH . '/' . $fotoAnterior);
                }

                if ($h['es_estudiante'] === 1) {
                    header('Location: ' . base_url('modulos/estudiantes/guardar_estudiante.php?habitante_id=' . $id));
                } else {
                    header('Location: ' . base_url('modulos/habitantes/listar_habitantes.php?msg=guardado_ok'));
                }
                exit;
            } else {
                if ($rutaFotoNueva !== null && file_exists(BASE_PATH . '/' . $rutaFotoNueva)) {
                    @unlink(BASE_PATH . '/' . $rutaFotoNueva);
                }
                $error = 'Error al actualizar habitante: ' . $stmtUpd->error;
                $stmtUpd->close();
            }
        }
    }
}

$sqlFamList = "SELECT f.id, f.nombre_familia, c.nombre AS comunidad
               FROM familias f INNER JOIN comunidades c ON f.comunidad_id = c.id";
if ($comFiltro !== null) {
    $sqlFamList .= " WHERE f.comunidad_id = " . (int)$comFiltro;
}
$sqlFamList .= " ORDER BY f.nombre_familia ASC";
$familias = $conexion->query($sqlFamList);

$fotoActualUrl = '';
if (!empty($h['foto']) && file_exists(BASE_PATH . '/' . $h['foto'])) {
    $fotoActualUrl = base_url($h['foto']);
}

$tituloPagina = 'Editar Habitante';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Editar Habitante</h2>
        <p class="text-muted">Actualiza datos personales, fotografía para carnet y caracterización</p>
    </div>
    <a href="<?= e(base_url('modulos/habitantes/listar_habitantes.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <div class="form-group full-width">
            <label for="familia_id">Núcleo Familiar *</label>
            <select name="familia_id" id="familia_id" required>
                <?php if ($familias): ?>
                    <?php while ($fm = $familias->fetch_assoc()): ?>
                        <option value="<?= (int)$fm['id'] ?>" <?= ((int)$h['familia_id'] === (int)$fm['id']) ? 'selected' : '' ?>>
                            <?= e($fm['nombre_familia']) ?> (<?= e($fm['comunidad']) ?>)
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="foto_habitante">Cambiar fotografía (JPG, PNG, WEBP - máx. 3 MB)</label>
            <input type="file" name="foto_habitante" id="foto_habitante" accept=".jpg,.jpeg,.png,.webp" data-preview="previewHabitanteEdit">
            <small class="text-muted">Si no seleccionas ningún archivo, se conservará la foto actual.</small>
        </div>

        <div class="form-group">
            <label>Fotografía actual / vista previa</label>
            <div>
                <img id="previewHabitanteEdit"
                     src="<?= e($fotoActualUrl) ?>"
                     alt="Fotografía del habitante"
                     class="preview-img"
                     style="<?= $fotoActualUrl !== '' ? 'display: inline-block;' : 'display: none;' ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="nacionalidad">Nacionalidad</label>
            <select name="nacionalidad" id="nacionalidad">
                <option value="V" <?= ($h['nacionalidad'] ?? 'V') === 'V' ? 'selected' : '' ?>>Venezolano/a (V)</option>
                <option value="E" <?= ($h['nacionalidad'] ?? 'V') === 'E' ? 'selected' : '' ?>>Extranjero/a (E)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="cedula">Cédula</label>
            <input type="text" name="cedula" id="cedula" value="<?= e($h['cedula'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="nombres">Nombres *</label>
            <input type="text" name="nombres" id="nombres" required value="<?= e($h['nombres']) ?>">
        </div>

        <div class="form-group">
            <label for="apellidos">Apellidos *</label>
            <input type="text" name="apellidos" id="apellidos" required value="<?= e($h['apellidos']) ?>">
        </div>

        <div class="form-group">
            <label for="fecha_nacimiento">Fecha de nacimiento *</label>
            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required value="<?= e($h['fecha_nacimiento']) ?>">
        </div>

        <div class="form-group">
            <label for="sexo">Sexo *</label>
            <select name="sexo" id="sexo">
                <option value="F" <?= ($h['sexo'] ?? 'F') === 'F' ? 'selected' : '' ?>>Femenino (F)</option>
                <option value="M" <?= ($h['sexo'] ?? 'F') === 'M' ? 'selected' : '' ?>>Masculino (M)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="parentesco">Parentesco *</label>
            <select name="parentesco" id="parentesco">
                <?php foreach (['Jefe de Familia', 'Cónyuge', 'Hijo/a', 'Padre/Madre', 'Nieto/a', 'Abuelo/a', 'Hermano/a', 'Otro'] as $par): ?>
                    <option value="<?= e($par) ?>" <?= ($h['parentesco'] ?? '') === $par ? 'selected' : '' ?>><?= e($par) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="estado_civil">Estado civil</label>
            <select name="estado_civil" id="estado_civil">
                <?php foreach (['Soltero/a', 'Casado/a', 'Concubinato', 'Divorciado/a', 'Viudo/a'] as $ec): ?>
                    <option value="<?= e($ec) ?>" <?= ($h['estado_civil'] ?? '') === $ec ? 'selected' : '' ?>><?= e($ec) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono" value="<?= e($h['telefono'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" name="correo" id="correo" value="<?= e($h['correo'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="ocupacion">Ocupación / Oficio</label>
            <input type="text" name="ocupacion" id="ocupacion" value="<?= e($h['ocupacion'] ?? '') ?>">
        </div>

        <div class="form-group full-width">
            <label>Caracterización Social y de Salud</label>
            <div class="checks-row">
                <label><input type="checkbox" name="trabaja_actualmente" value="1" <?= !empty($h['trabaja_actualmente']) ? 'checked' : '' ?>> Trabaja actualmente</label>
                <label><input type="checkbox" name="es_estudiante" value="1" <?= !empty($h['es_estudiante']) ? 'checked' : '' ?>> Es estudiante</label>
                <label><input type="checkbox" name="posee_discapacidad" value="1" <?= !empty($h['posee_discapacidad']) ? 'checked' : '' ?>> Posee discapacidad</label>
                <label><input type="checkbox" name="es_embarazada" value="1" <?= !empty($h['es_embarazada']) ? 'checked' : '' ?>> Embarazada</label>
                <label><input type="checkbox" name="es_lactante" value="1" <?= !empty($h['es_lactante']) ? 'checked' : '' ?>> Lactante</label>
            </div>
        </div>

        <div class="form-group">
            <label for="tipo_discapacidad">Tipo de discapacidad (si aplica)</label>
            <input type="text" name="tipo_discapacidad" id="tipo_discapacidad" value="<?= e($h['tipo_discapacidad'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="enfermedad_cronica">Enfermedad crónica / tratamiento</label>
            <input type="text" name="enfermedad_cronica" id="enfermedad_cronica" value="<?= e($h['enfermedad_cronica'] ?? '') ?>">
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>