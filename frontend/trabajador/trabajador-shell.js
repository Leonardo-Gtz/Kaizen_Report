const HERO_META = {
    nuevo: {
        eyebrow: 'Nuevo',
        title: 'Crear reporte Kaizen',
        sub: 'Registra una mejora con evidencia, participantes y análisis de riesgo.',
        meta: 'Guarda borrador o envía cuando esté listo para el flujo de aprobación.',
        icon: 'nuevo'
    },
    reportes: {
        eyebrow: 'Historial',
        title: 'Mis reportes',
        sub: 'Consulta el estado de tus reportes en el flujo Sup → Ger → RH.',
        meta: 'Filtra por año, mes o estado para encontrar un reporte.',
        icon: 'reportes'
    },
    borradores: {
        eyebrow: 'Borradores',
        title: 'Reportes en borrador',
        sub: 'Continúa editando reportes que aún no has enviado.',
        meta: '',
        icon: 'borradores'
    }
};

const HERO_ICONS = {
    nuevo: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>',
    reportes: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    borradores: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>'
};

function toggleHeaderMenu() {
    const nav = document.getElementById('headerNavMobile');
    const btn = document.getElementById('headerMenuToggle');
    const iconOpen = document.getElementById('headerMenuIconOpen');
    const iconClose = document.getElementById('headerMenuIconClose');
    if (!nav || !btn) return;
    const abrir = !nav.classList.contains('open');
    nav.classList.toggle('open', abrir);
    btn.classList.toggle('open', abrir);
    btn.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    if (iconOpen) iconOpen.classList.toggle('hidden', abrir);
    if (iconClose) iconClose.classList.toggle('hidden', !abrir);
}

function cerrarHeaderMenu() {
    const nav = document.getElementById('headerNavMobile');
    const btn = document.getElementById('headerMenuToggle');
    const iconOpen = document.getElementById('headerMenuIconOpen');
    const iconClose = document.getElementById('headerMenuIconClose');
    if (nav) nav.classList.remove('open');
    if (btn) {
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    }
    if (iconOpen) iconOpen.classList.remove('hidden');
    if (iconClose) iconClose.classList.add('hidden');
}

function actualizarTituloSeccion(seccion) {
    const meta = HERO_META[seccion];
    if (!meta) return;

    const eyebrowEl = document.getElementById('pageHeaderEyebrow');
    const titleEl = document.getElementById('pageHeaderTitle');
    const subEl = document.getElementById('pageHeaderSub');
    const metaEl = document.getElementById('pageHeaderMeta');
    const iconEl = document.getElementById('pageHeaderIcon');
    const headerEl = document.getElementById('pageHeader');

    if (eyebrowEl) eyebrowEl.textContent = meta.eyebrow;
    if (titleEl) titleEl.textContent = meta.title;
    if (subEl) {
        subEl.textContent = meta.sub || '';
        subEl.classList.toggle('hidden', !meta.sub);
    }
    if (iconEl && HERO_ICONS[meta.icon]) iconEl.innerHTML = HERO_ICONS[meta.icon];
    if (headerEl) {
        headerEl.classList.toggle('section-hero--nuevo', seccion === 'nuevo');
        headerEl.classList.toggle('section-hero--reportes', seccion === 'reportes');
        headerEl.classList.toggle('section-hero--borradores', seccion === 'borradores');
    }
    if (metaEl) {
        if (seccion === 'borradores') {
            actualizarHeroMetaBorradores();
        } else {
            metaEl.textContent = meta.meta || '';
            metaEl.classList.toggle('hidden', !meta.meta);
        }
    }
}

function actualizarHeroMetaBorradores() {
    const metaEl = document.getElementById('pageHeaderMeta');
    const info = document.getElementById('infoBorradores');
    if (!metaEl) return;
    const txt = info?.textContent?.trim() || '';
    if (txt && txt !== 'Cargando...' && !txt.startsWith('Error')) {
        metaEl.textContent = txt;
        metaEl.classList.remove('hidden');
    } else {
        metaEl.classList.add('hidden');
    }
}

function actualizarBadgeBorradores(count) {
    const badge = document.getElementById('badge-borradores');
    if (!badge) return;
    const n = parseInt(count, 10) || 0;
    if (n > 0) {
        badge.textContent = String(n);
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

window.actualizarBadgeBorradores = actualizarBadgeBorradores;
window.actualizarHeroMetaBorradores = actualizarHeroMetaBorradores;

document.addEventListener('DOMContentLoaded', () => {
    const fechaEl = document.getElementById('fecha');
    if (!fechaEl) return;
    const hoy = new Date();
    const y = hoy.getFullYear();
    const m = String(hoy.getMonth() + 1).padStart(2, '0');
    const d = String(hoy.getDate()).padStart(2, '0');
    fechaEl.min = `${y}-${m}-${d}`;
    fechaEl.removeAttribute('max');
    if (!fechaEl.value) {
        fechaEl.value = `${y}-${m}-${d}`;
    }
});
