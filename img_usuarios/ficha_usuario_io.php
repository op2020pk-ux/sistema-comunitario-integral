<?php
require_once __DIR__ . '/../config/verificar_sesion.php';

// Exportar ficha de usuario en formato JSON (incluye foto en base64 si existe)
if (isset($_GET['exportar_id'])) {
    $exportId = (int)$_GET['exportar_id'];
    if ($exportId !== (int)$_SESSION['usuario_id'] && !es_admin_comunidad()) {
        header('Location: ' . base_url('dashboard.php?error=permisos'));
        exit;
    }

    $stmt = $conexion->prepare("SELECT id, comunidad_id, cedula, nombre_usuario, nombre_completo, telefono, foto, rol, estado FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $exportId);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u) {
        header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('Usuario no encontrado para exportar.')));
        exit;
    }

    $fotoBase64 = null;
    $fotoExt = null;
    if (!empty($u['foto']) && $u['foto'] !== 'uploads/usuarios/default_avatar.png') {
        $rutaFisica = BASE_PATH . '/' . $u['foto'];
        if (file_exists($rutaFisica) && is_file($rutaFisica)) {
            $fotoBase64 = base64_encode(file_get_contents($rutaFisica));
            $fotoExt = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
        }
    }

    $payload = [
        'sistema'         => 'sistema_comunitario_integral',
        'exportado_en'    => date('c'),
        'datos_usuario'   => [
            'cedula'          => $u['cedula'],
            'nombre_usuario'  => $u['nombre_usuario'],
            'nombre_completo' => $u['nombre_completo'],
            'telefono'        => $u['telefono'],
            'rol'             => $u['rol'],
            'estado'          => (int)$u['estado']
        ],
        'foto_extension'  => $fotoExt,
        'foto_base64'     => $fotoBase64
    ];

    $filename = 'ficha_usuario_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $u['nombre_usuario']) . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Importar ficha JSON para actualizar o crear usuario (solo Admin Comunidad / SuperAdmin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_ficha'])) {
    exigir_rol(['SuperAdmin', 'Administrador Comunidad']);

    if ($_FILES['archivo_ficha']['error'] !== UPLOAD_ERR_OK) {
        header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('Error al cargar el archivo JSON.')));
        exit;
    }

    $contenido = file_get_contents($_FILES['archivo_ficha']['tmp_name']);
    $data = json_decode($contenido, true);

    if (!is_array($data) || empty($data['datos_usuario']['nombre_usuario']) || empty($data['datos_usuario']['nombre_completo'])) {
        header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=error&detalle=' . urlencode('Archivo de ficha inválido o incompleto.')));
        exit;
    }

    $du = $data['datos_usuario'];
    $nombreUsuario  = trim((string)$du['nombre_usuario']);
    $nombreCompleto = trim((string)$du['nombre_completo']);
    $cedula         = !empty($du['cedula']) ? trim((string)$du['cedula']) : null;
    $telefono       = !empty($du['telefono']) ? trim((string)$du['telefono']) : null;
    $rol            = in_array($du['rol'] ?? '', ['SuperAdmin', 'Administrador Comunidad', 'Vocero/Censador'], true) ? $du['rol'] : 'Vocero/Censador';
    if (!es_superadmin() && $rol === 'SuperAdmin') {
        $rol = 'Vocero/Censador';
    }
    $comunidadId    = es_superadmin() ? null : ($_SESSION['comunidad_id'] ?? null);

    // Busca si ya existe por nombre_usuario
    $stmtBus = $conexion->prepare("SELECT id, foto FROM usuarios WHERE nombre_usuario = ? LIMIT 1");
    $stmtBus->bind_param('s', $nombreUsuario);
    $stmtBus->execute();
    $existente = $stmtBus->get_result()->fetch_assoc();
    $stmtBus->close();

    $usuarioDestinoId = $existente ? (int)$existente['id'] : 0;
    $rutaFotoFinal = $existente['foto'] ?? 'uploads/usuarios/default_avatar.png';

    // Restaura foto si viene en base64 y tiene extensión permitida
    $extPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower((string)($data['foto_extension'] ?? ''));
    if (!empty($data['foto_base64']) && in_array($ext, $extPermitidas, true)) {
        $binFoto = base64_decode((string)$data['foto_base64'], true);
        if ($binFoto !== false && strlen($binFoto) <= 2 * 1024 * 1024) {
            $dirUsr = BASE_PATH . '/uploads/usuarios';
            if (!is_dir($dirUsr)) {
                mkdir($dirUsr, 0775, true);
            }
            $nomFoto = 'usr_imp_' . time() . '_' . rand(100, 999) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
            if (file_put_contents($dirUsr . '/' . $nomFoto, $binFoto) !== false) {
                $rutaFotoFinal = 'uploads/usuarios/' . $nomFoto;
            }
        }
    }

    if ($usuarioDestinoId > 0) {
        $stmtUpd = $conexion->prepare("UPDATE usuarios SET cedula = ?, nombre_completo = ?, telefono = ?, foto = ?, rol = ? WHERE id = ?");
        $stmtUpd->bind_param('sssssi', $cedula, $nombreCompleto, $telefono, $rutaFotoFinal, $rol, $usuarioDestinoId);
        $stmtUpd->execute();
        $stmtUpd->close();
    } else {
        $claveTemporal = password_hash('Censo2026*', PASSWORD_DEFAULT);
        $stmtIns = $conexion->prepare("INSERT INTO usuarios (comunidad_id, cedula, nombre_usuario, password_hash, nombre_completo, telefono, foto, rol, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmtIns->bind_param('isssssss', $comunidadId, $cedula, $nombreUsuario, $claveTemporal, $nombreCompleto, $telefono, $rutaFotoFinal, $rol);
        $stmtIns->execute();
        $stmtIns->close();
    }

    header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php?msg=ficha_importada'));
    exit;
}

header('Location: ' . base_url('modulos/usuarios/listar_usuarios.php'));
exit;