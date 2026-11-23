<?php
require_once __DIR__ . '/../../config/verificar_sesion.php';

$comFiltro = comunidad_filtro(null);
$habitanteId = isset($_GET['habitante_id']) ? (int)$_GET['habitante_id'] : (int)($_POST['habitante_id'] ?? 0);
$error = '';

$est = [
    'habitante_id'           => $habitanteId,
    'nivel_educativo'        => 'Primaria',
    'grado_o_semestre'       => '',
    'nombre_institucion'     => '',
    'tipo_institucion'       => 'Pública',
    'requiere_beca_o_utiles' => 0
];

if ($habitanteId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexion->prepare("SELECT * FROM estudiantes WHERE habitante_id = ? LIMIT 1");
    $stmt->bind_param('i', $habitanteId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found) {
        $est = $found;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $est['habitante_id']           = (int)($_POST['habitante_id'] ?? 0);
    $est['nivel_educativo']        = $_POST['nivel_educativo'] ?? 'Primaria';
    $est['grado_o_semestre']       = trim($_POST['grado_o_semestre'] ?? '');
    $est['nombre_institucion']     = trim($_POST['nombre_institucion'] ?? '');
    $est['tipo_institucion']       = in_array($_POST['tipo_institucion'] ?? 'Pública', ['Pública', 'Privada'], true) ? $_POST['tipo_institucion'] : 'Pública';
    $est['requiere_beca_o_utiles'] = isset($_POST['requiere_beca_o_utiles']) ? 1 : 0;

    if ($est['habitante_id'] <= 0) {
        $error = 'Debes seleccionar un habitante.';
    } else {
        $sqlUpsert = "INSERT INTO estudiantes (habitante_id, nivel_educativo, grado_o_semestre, nombre_institucion, tipo_institucion, requiere_beca_o_utiles)
                      VALUES (?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                        nivel_educativo = VALUES(nivel_educativo),
                        grado_o_semestre = VALUES(grado_o_semestre),
                        nombre_institucion = VALUES(nombre_institucion),
                        tipo_institucion = VALUES(tipo_institucion),
                        requiere_beca_o_utiles = VALUES(requiere_beca_o_utiles)";
        $stmt = $conexion->prepare($sqlUpsert);
        $stmt->bind_param(
            'issssi',
            $est['habitante_id'],
            $est['nivel_educativo'],
            $est['grado_o_semestre'],
            $est['nombre_institucion'],
            $est['tipo_institucion'],
            $est['requiere_beca_o_utiles']
        );

        if ($stmt->execute()) {
            $stmt->close();
            // Asegura bandera es_estudiante = 1 en habitantes
            $stmtFlag = $conexion->prepare("UPDATE habitantes SET es_estudiante = 1 WHERE id = ?");
            $stmtFlag->bind_param('i', $est['habitante_id']);
            $stmtFlag->execute();
            $stmtFlag->close();

            header('Location: ' . base_url('modulos/estudiantes/listar_estudiantes.php?msg=guardado_ok'));
            exit;
        } else {
            $error = 'Error al guardar ficha estudiantil: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$sqlHab = "SELECT h.id, h.nacionalidad, h.cedula, h.nombres, h.apellidos, c.nombre AS comunidad
           FROM habitantes h INNER JOIN comunidades c ON h.comunidad_id = c.id
           WHERE h.activo = 1";
if ($comFiltro !== null) {
    $sqlHab .= " AND h.comunidad_id = " . (int)$comFiltro;
}
$sqlHab .= " ORDER BY h.apellidos ASC, h.nombres ASC";
$listaHabitantes = $conexion->query($sqlHab);

$tituloPagina = 'Ficha Académica de Estudiante';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2>Vincular / Editar Datos Académicos</h2>
        <p class="text-muted">Información educativa del habitante censado</p>
    </div>
    <a href="<?= e(base_url('modulos/estudiantes/listar_estudiantes.php')) ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" class="form-grid">
        <div class="form-group full-width">
            <label for="habitante_id">Habitante *</label>
            <select name="habitante_id" id="habitante_id" required>
                <option value="">-- Selecciona habitante --</option>
                <?php if ($listaHabitantes): ?>
                    <?php while ($hb = $listaHabitantes->fetch_assoc()): ?>
                        <option value="<?= (int)$hb['id'] ?>" <?= ((int)$est['habitante_id'] === (int)$hb['id']) ? 'selected' : '' ?>>
                            <?= e($hb['apellidos'] . ', ' . $hb['nombres'] . ' (' . ($hb['cedula'] ? $hb['nacionalidad'] . '-' . $hb['cedula'] : 'S/C') . ') - ' . $hb['comunidad']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="nivel_educativo">Nivel educativo *</label>
            <select name="nivel_educativo" id="nivel_educativo">
                <?php foreach (['Maternal', 'Preescolar', 'Primaria', 'Secundaria', 'Técnico', 'Universitario', 'Misión Educativa'] as $nv): ?>
                    <option value="<?= e($nv) ?>" <?= $est['nivel_educativo'] === $nv ? 'selected' : '' ?>><?= e($nv) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="grado_o_semestre">Grado, año o semestre</label>
            <input type="text" name="grado_o_semestre" id="grado_o_semestre" value="<?= e($est['grado_o_semestre']) ?>" placeholder="Ej. 4to Grado / 2do Semestre">
        </div>

        <div class="form-group">
            <label for="nombre_institucion">Nombre de la institución</label>
            <input type="text" name="nombre_institucion" id="nombre_institucion" value="<?= e($est['nombre_institucion']) ?>">
        </div>

        <div class="form-group">
            <label for="tipo_institucion">Tipo de institución</label>
            <select name="tipo_institucion" id="tipo_institucion">
                <option value="Pública" <?= $est['tipo_institucion'] === 'Pública' ? 'selected' : '' ?>>Pública</option>
                <option value="Privada" <?= $est['tipo_institucion'] === 'Privada' ? 'selected' : '' ?>>Privada</option>
            </select>
        </div>

        <div class="form-group full-width">
            <div class="checks-row">
                <label>
                    <input type="checkbox" name="requiere_beca_o_utiles" value="1" <?= !empty($est['requiere_beca_o_utiles']) ? 'checked' : '' ?>>
                    Requiere beca estudiantil, uniformes o útiles escolares
                </label>
            </div>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn btn-primary">Guardar Ficha Estudiantil</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>