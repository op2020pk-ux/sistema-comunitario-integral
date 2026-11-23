<aside id="sidebar" class="sidebar">
    <nav class="sidebar-nav">
        <a href="<?= e(base_url('dashboard.php')) ?>" class="nav-link">📊 Panel Principal</a>

        <div class="nav-section">Censo y Territorio</div>
        <a href="<?= e(base_url('modulos/comunidades/listar_comunidades.php')) ?>" class="nav-link">🏘️ Comunidades</a>
        <a href="<?= e(base_url('modulos/comunidades/calles.php')) ?>" class="nav-link">🛣️ Calles / Veredas</a>
        <a href="<?= e(base_url('modulos/viviendas/listar_viviendas.php')) ?>" class="nav-link">🏠 Viviendas</a>
        <a href="<?= e(base_url('modulos/familias/listar_familias.php')) ?>" class="nav-link">👨‍👩‍👧‍👦 Familias</a>
        <a href="<?= e(base_url('modulos/habitantes/listar_habitantes.php')) ?>" class="nav-link">🧑 Habitantes</a>
        <a href="<?= e(base_url('modulos/estudiantes/listar_estudiantes.php')) ?>" class="nav-link">🎓 Estudiantes</a>
        <a href="<?= e(base_url('modulos/consulta_cedula/buscar_cedula.php')) ?>" class="nav-link">🔍 Consulta por Cédula</a>

        <div class="nav-section">Gestión Social y Registro</div>
        <a href="<?= e(base_url('modulos/beneficios/listar_beneficios.php')) ?>" class="nav-link">📦 Beneficios</a>
        <a href="<?= e(base_url('modulos/fallecidos/listar_fallecidos.php')) ?>" class="nav-link">🕊️ Fallecidos</a>
        <a href="<?= e(base_url('carnet/carnet_jefe_familia.php')) ?>" class="nav-link">🪪 Carnet Jefe de Familia</a>
        <a href="<?= e(base_url('reportes/reporte_censo_pdf.php')) ?>" class="nav-link">📄 Constancias / Planillas</a>

        <?php if (es_admin_comunidad()): ?>
            <div class="nav-section">Administración</div>
            <a href="<?= e(base_url('modulos/usuarios/listar_usuarios.php')) ?>" class="nav-link">🔐 Usuarios</a>
        <?php endif; ?>

        <?php if (es_superadmin()): ?>
            <a href="<?= e(base_url('configuracion_sistema/panel_configuracion.php')) ?>" class="nav-link">⚙️ Configuración</a>
        <?php endif; ?>
    </nav>
</aside>
<main class="content-area">