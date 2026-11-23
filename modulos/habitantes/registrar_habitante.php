<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

// Asegura que la columna `foto` exista en `habitantes` sin romper nada
$resColFoto = $conexion->query("SHOW COLUMNS FROM habitantes LIKE 'foto'");
if ($resColFoto && $resColFoto->num_rows === 0) {
    $conexion->query("ALTER TABLE habitantes ADD COLUMN foto VARCHAR(255) NULL AFTER activo");
}

$comFiltro  = comunidad_filtro(null);
$familiaPre = isset($_GET['familia_id']) ? (int)$_GET['familia_id'] : 0;
$error      = '';

$h = [
    'familia_id'          => $familiaPre,
    'nacionalidad'        => 'V',
    'cedula'              => '',
    'nombres'             => '',
    'apellidos'           => '',
    'fecha_nacimiento'    => '',
    'sexo'                => 'F',
    'parentesco'          => 'Jefe de Familia',
    'estado_civil'        => 'Soltero/a',
    'telefono'            => '',
    'correo'              => '',
    'ocupacion'           => '',
    'trabaja_actualmente' => 0,
    'es_estudiante'       => 0,
    'posee_discapacidad'  => 0,
    'tipo_discapacidad'   => '',
    'enfermedad_cronica'  => '',
    'es_embarazada'       => 0,
    'es_lactante'         => 0,
    'foto'                => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $h['familia_id']          = (int)($_POST['familia_id'] ?? 0);
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
        $error = 'Debes seleccionar una familia válida dentro de tu comunidad.';
    } elseif ($h['nombres'] === '' || $h['apellidos'] === '' || $h['fecha_nacimiento'] === '') {
        $error = 'Nombres, apellidos y fecha de nacimiento son obligatorios.';
    }

    // Procesa la imagen si se seleccionó una en el formulario
    $rutaFotoGuardada = null;
    if ($error === '' && !empty($_FILES['foto_habitante']['name'])) {
        $archivo = $_FILES['foto_habitante'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error al subir la fotografía del habitante.';
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
                    $baseCedula = preg_replace('/[^0-9A-Za-z_-]/', '', $h['cedula'] ?: 'sin_ci');
                    $nombreFoto = 'hab_' . $baseCedula . '_' . time() . '_' . rand(100, 999) . '.' . $permitidos[$mime];
                    $rutaFisica = $dirFotos . '/' . $nombreFoto;

                    if (move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
                        @chmod($rutaFisica, 0666);
                        $rutaFotoGuardada = 'fotos/' . $nombreFoto;
                    } else {
                        $error = 'No se pudo guardar la imagen en la carpeta fotos/. Revisa los permisos.';
                    }
                }
            }
        }
    }

    if ($error === '') {
        $comunidadId = (int)$famRow['comunidad_id'];
        $cedVal = $h['cedula'] !== '' ? $h['cedula'] : null;

        $sqlIns = "INSERT INTO habitantes (
            comunidad_id, familia_id, nacionalidad, cedula, nombres, apellidos,
            fecha_nacimiento, sexo, parentesco, estado_civil, telefono, correo,
            ocupacion, trabaja_actualmente, es_estudiante, posee_discapacidad,
            tipo_discapacidad, enfermedad_cronica, es_embarazada, es_lactante, activo, foto
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

        $stmt = $conexion->prepare($sqlIns);
        if (!$stmt) {
            $error = 'Error al preparar inserción: ' . $conexion->error;
        } else {
            $stmt->bind_param(
                'iisssssssssssiiissiis',
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
                $rutaFotoGuardada
            );

            if ($stmt->execute()) {
                $nuevoId = $stmt->insert_id;
                $stmt->close();

                if ($h['parentesco'] === 'Jefe de Familia' && $cedVal !== null) {
                    $stmtJ = $conexion->prepare("UPDATE familias SET jefe_familia_cedula = ? WHERE id = ? AND (jefe_familia_cedula IS NULL OR jefe_familia_cedula = '')");
                    $stmtJ->bind_param('si', $cedVal, $h['familia_id']);
                    $stmtJ->execute();
                    $stmtJ->close();
                }

                if ($h['es_estudiante'] === 1) {
                    header('Location: ' . base_url('modulos/estudiantes/guardar_estudiante.php?habitante_id=' . $nuevoId));
                } else {
                    header('Location: ' . base_url('modulos/habitantes/listar_habitantes.php?msg=guardado_ok'));
                }
                exit;
            } else {
                if ($rutaFotoGuardada && file_exists(BASE_PATH . '/' . $rutaFotoGuardada)) {
                    @unlink(BASE_PATH . '/' . $rutaFotoGuardada);
                }
                $error = 'Error al registrar habitante (verifica que la cédula no esté duplicada): ' . $stmt->error;
                $stmt->close();
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

$tituloPagina = 'Registrar Habitante';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Registrar Habitante en el Censo</h2>
        <p class="text-muted">Datos personales, fotografía para carnet y caracterización social/salud</p>
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
                <option value="">-- Selecciona la familia --</option>
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
            <label for="foto_habitante">Fotografía para el Carnet (JPG, PNG, WEBP - máx. 3 MB)</label>
            <input type="file" name="foto_habitante" id="foto_habitante" accept=".jpg,.jpeg,.png,.webp" data-preview="previewHabitante">
        </div>

        <div class="form-group">
            <label>Vista previa de la foto</label>
            <div>
                <img id="previewHabitante" src="" alt="Vista previa del habitante" class="preview-img" style="display: none;">
            </div>
        </div>

        <div class="form-group">
            <label for="nacionalidad">Nacionalidad</label>
            <select name="nacionalidad" id="nacionalidad">
                <option value="V" <?= $h['nacionalidad'] === 'V' ? 'selected' : '' ?>>Venezolano/a (V)</option>
                <option value="E" <?= $h['nacionalidad'] === 'E' ? 'selected' : '' ?>>Extranjero/a (E)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="cedula">Cédula (dejar vacío si es niño sin cédula)</label>
            <input type="text" name="cedula" id="cedula" value="<?= e($h['cedula']) ?>" placeholder="Solo números">
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
                <option value="F" <?= $h['sexo'] === 'F' ? 'selected' : '' ?>>Femenino (F)</option>
                <option value="M" <?= $h['sexo'] === 'M' ? 'selected' : '' ?>>Masculino (M)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="parentesco">Parentesco *</label>
            <select name="parentesco" id="parentesco">
                <?php foreach (['Jefe de Familia', 'Cónyuge', 'Hijo/a', 'Padre/Madre', 'Nieto/a', 'Abuelo/a', 'Hermano/a', 'Otro'] as $par): ?>
                    <option value="<?= e($par) ?>" <?= $h['parentesco'] === $par ? 'selected' : '' ?>><?= e($par) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="estado_civil">Estado civil</label>
            <select name="estado_civil" id="estado_civil">
                <?php foreach (['Soltero/a', 'Casado/a', 'Concubinato', 'Divorciado/a', 'Viudo/a'] as $ec): ?>
                    <option value="<?= e($ec) ?>" <?= $h['estado_civil'] === $ec ? 'selected' : '' ?>><?= e($ec) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono" value="<?= e($h['telefono']) ?>">
        </div>

        <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" name="correo" id="correo" value="<?= e($h['correo']) ?>">
        </div>

        <div class="form-group">
            <label for="ocupacion">Ocupación / Oficio</label>
            <input type="text" name="ocupacion" id="ocupacion" value="<?= e($h['ocupacion']) ?>">
        </div>

        <div class="form-group full-width">
            <label>Caracterización Social y de Salud</label>
            <div class="checks-row">
                <label><input type="checkbox" name="trabaja_actualmente" value="1" <?= $h['trabaja_actualmente'] ? 'checked' : '' ?>> Trabaja actualmente</label>
                <label><input type="checkbox" name="es_estudiante" value="1" <?= $h['es_estudiante'] ? 'checked' : '' ?>> Es estudiante</label>
                <label><input type="checkbox" name="posee_discapacidad" value="1" <?= $h['posee_discapacidad'] ? 'checked' : '' ?>> Posee discapacidad</label>
                <label><input type="checkbox" name="es_embarazada" value="1" <?= $h['es_embarazada'] ? 'checked' : '' ?>> Embarazada</label>
                <label><input type="checkbox" name="es_lactante" value="1" <?= $h['es_lactante'] ? 'checked' : '' ?>> Lactante</label>
            </div>
        </div>

        <div class="form-group">
            <label for="tipo_discapacidad">Tipo de discapacidad (si aplica)</label>
            <input type="text" name="tipo_discapacidad" id="tipo_discapacidad" value="<?= e($h['tipo_discapacidad']) ?>">
        </div>

        <div class="form-group">
            <label for="enfermedad_cronica">Enfermedad crónica / tratamiento</label>
            <input type="text" name="enfermedad_cronica" id="enfermedad_cronica" value="<?= e($h['enfermedad_cronica']) ?>">
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Habitante</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>