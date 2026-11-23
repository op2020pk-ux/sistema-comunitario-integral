<?php
require_once __DIR__ . '/config/conexion.php';

// Si ya inició sesión, entra directo a dashboard.php
if (!empty($_SESSION['usuario_id']) && !empty($_SESSION['nombre_usuario']) && empty($_GET['forzar_login'])) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$mensajeError = '';
$codError = $_GET['error'] ?? '';
if ($codError === 'sesion') {
    $mensajeError = 'Debes iniciar sesión para acceder al sistema.';
} elseif ($codError === 'inactivo') {
    $mensajeError = 'Tu cuenta de usuario está desactivada.';
} elseif ($codError === 'credenciales') {
    $mensajeError = 'Usuario o contraseña incorrectos.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = trim($_POST['nombre_usuario'] ?? '');
    $claveInput   = (string)($_POST['password'] ?? '');

    if ($usuarioInput === '' || $claveInput === '') {
        $mensajeError = 'Por favor completa el usuario y la contraseña.';
    } else {
        $stmt = $conexion->prepare("SELECT id, comunidad_id, nombre_usuario, password_hash, nombre_completo, rol, estado, foto FROM usuarios WHERE nombre_usuario = ? LIMIT 1");
        if (!$stmt) {
            $mensajeError = 'Error interno al consultar usuario.';
        } else {
            $stmt->bind_param('s', $usuarioInput);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user && (int)$user['estado'] === 1 && !empty($user['password_hash']) && password_verify($claveInput, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id']      = (int)$user['id'];
                $_SESSION['comunidad_id']    = $user['comunidad_id'] !== null ? (int)$user['comunidad_id'] : null;
                $_SESSION['nombre_usuario']  = $user['nombre_usuario'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rol']             = $user['rol'];
                $_SESSION['foto_usuario']    = $user['foto'] ?: 'uploads/usuarios/default_avatar.png';

                header('Location: ' . base_url('dashboard.php'));
                exit;
            } else {
                $mensajeError = 'Usuario o contraseña incorrectos, o cuenta inactiva.';
            }
        }
    }
}

// Imagen principal de fondo: usa uploads/sistema/portada_comunal.jpg si existe, o el logo del sistema como respaldo
$imagenFondoRelativa = 'uploads/sistema/portada_comunal.jpg';
if (!file_exists(BASE_PATH . '/' . $imagenFondoRelativa)) {
    $imagenFondoRelativa = $configSistema['logo_sistema'] ?: 'uploads/sistema/default_logo.png';
}
$urlImagenFondo = base_url($imagenFondoRelativa);

// Si hubo error de login o envío de formulario, abrimos directo el panel de acceso
$mostrarAccesoInicial = ($mensajeError !== '' || $_SERVER['REQUEST_METHOD'] === 'POST');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Comunal</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/estilos.css')) ?>">
</head>
<body class="portal-index-body">
    <section class="portal-index-hero" style="background-image: url('<?= e($urlImagenFondo) ?>');">
        <div class="portal-index-overlay">
            <div class="portal-index-contenedor <?= $mostrarAccesoInicial ? 'con-login' : '' ?>" id="portalContenedor">
                <div class="portal-portada" id="portalPortada">
                    <h1>Sistema Comunal</h1>
                    <p><?= e($configSistema['subtitulo']) ?></p>
                    <button type="button" id="btnMostrarAcceso" class="btn-entrar-sistema">Entrar al Sistema</button>
                </div>

                <div class="login-card portal-login-box" id="portalLoginBox">
                    <div class="login-header">
                        <img src="<?= e(base_url($configSistema['logo_sistema'])) ?>" alt="Logo del sistema" class="login-logo" onerror="this.style.display='none';">
                        <h2>Sistema Comunal</h2>
                        <p>Acceso al Sistema</p>
                    </div>

                    <?php if ($mensajeError !== ''): ?>
                        <div class="alert alert-error"><?= e($mensajeError) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" class="form-vertical">
                        <div class="form-group">
                            <label for="nombre_usuario">Nombre de usuario</label>
                            <input type="text" id="nombre_usuario" name="nombre_usuario" required autofocus placeholder="Ej. admin">
                        </div>

                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" required placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Ingresar al Sistema</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnMostrar = document.getElementById('btnMostrarAcceso');
            const contenedor = document.getElementById('portalContenedor');
            const inputUsuario = document.getElementById('nombre_usuario');

            if (btnMostrar && contenedor) {
                btnMostrar.addEventListener('click', () => {
                    contenedor.classList.add('con-login');
                    if (inputUsuario) {
                        inputUsuario.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>