document.addEventListener('DOMContentLoaded', () => {
    // 1. Alternar menú lateral
    const btnToggle = document.getElementById('btnToggleSidebar');
    const sidebar = document.getElementById('sidebar');
    if (btnToggle && sidebar) {
        btnToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // 2. Vista previa instantánea al seleccionar imagen
    document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
        input.addEventListener('change', (e) => {
            const targetId = input.getAttribute('data-preview');
            const imgEl = document.getElementById(targetId);
            const file = e.target.files && e.target.files[0];
            if (imgEl && file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    imgEl.src = ev.target.result;
                    imgEl.style.display = 'inline-block';
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // 3. Renderizado de matriz identificadora local para Carnet de Jefe de Familia (sin internet)
    const qrContainer = document.getElementById('qrCanvasContainer');
    if (qrContainer) {
        const payload = qrContainer.getAttribute('data-qr') || '';
        if (payload !== '') {
            renderMatrizIdentificadora(qrContainer, payload, 118);
        }
    }
});

/**
 * Genera un código matricial determinístico en Canvas (funciona 100% offline en XAMPP)
 */
function renderMatrizIdentificadora(container, text, sizePx) {
    const grid = 21;
    const canvas = document.createElement('canvas');
    canvas.width = sizePx;
    canvas.height = sizePx;
    const ctx = canvas.getContext('2d');
    const cell = sizePx / grid;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, sizePx, sizePx);

    // Hash determinístico del contenido JSON de la familia
    let hash = 2166136261;
    for (let i = 0; i < text.length; i++) {
        hash ^= text.charCodeAt(i);
        hash = Math.imul(hash, 16777619);
    }

    const drawFinder = (ox, oy) => {
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(ox * cell, oy * cell, 7 * cell, 7 * cell);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect((ox + 1) * cell, (oy + 1) * cell, 5 * cell, 5 * cell);
        ctx.fillStyle = '#0f172a';
        ctx.fillRect((ox + 2) * cell, (oy + 2) * cell, 3 * cell, 3 * cell);
    };

    drawFinder(0, 0);
    drawFinder(grid - 7, 0);
    drawFinder(0, grid - 7);

    ctx.fillStyle = '#0f172a';
    let seed = hash >>> 0;
    for (let y = 0; y < grid; y++) {
        for (let x = 0; x < grid; x++) {
            const inTopLeft = x < 8 && y < 8;
            const inTopRight = x >= grid - 8 && y < 8;
            const inBottomLeft = x < 8 && y >= grid - 8;
            if (inTopLeft || inTopRight || inBottomLeft) continue;

            const charCode = text.charCodeAt((y * grid + x) % text.length) || 1;
            seed = (Math.imul(seed ^ charCode, 1664525) + 1013904223) >>> 0;
            if ((seed & 1) === 1) {
                ctx.fillRect(Math.floor(x * cell), Math.floor(y * cell), Math.ceil(cell), Math.ceil(cell));
            }
        }
    }

    canvas.style.border = '1px solid #cbd5e1';
    canvas.style.borderRadius = '4px';
    container.innerHTML = '';
    container.appendChild(canvas);
}