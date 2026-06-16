const HERO_META = {
    inicio: {
        eyebrow: 'Inicio',
        title: 'Panel del supervisor',
        sub: 'Resumen de tu equipo, reportes pendientes y tendencia de aprobaciones.',
        meta: '',
        icon: 'inicio'
    },
    revisar: {
        eyebrow: 'Revisión',
        title: 'Reportes por aprobar',
        sub: 'Aprueba o rechaza reportes de tu equipo antes de que pasen a gerencia.',
        meta: 'Filtra por año y mes para acotar la bandeja.',
        icon: 'revisar'
    },
    aprobados: {
        eyebrow: 'Historial',
        title: 'Reportes aprobados',
        sub: 'Reportes que ya autorizaste y avanzaron en el flujo.',
        meta: 'Filtra por período para consultar aprobaciones anteriores.',
        icon: 'aprobados'
    },
    rechazados: {
        eyebrow: 'Historial',
        title: 'Reportes rechazados',
        sub: 'Reportes devueltos con motivo para corrección del trabajador.',
        meta: '',
        icon: 'rechazados'
    },
    misreportes: {
        eyebrow: 'Personal',
        title: 'Mis reportes',
        sub: 'Reportes Kaizen en los que participas como trabajador.',
        meta: '',
        icon: 'misreportes'
    },
    trabajadores: {
        eyebrow: 'Equipo',
        title: 'Mi equipo',
        sub: 'Directorio de integrantes bajo tu supervisión, sincronizado con RH.',
        meta: 'Busca por nombre o ID y ordena por actividad de reportes.',
        icon: 'trabajadores'
    }
};

const HERO_ICONS = {
    inicio: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    revisar: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    aprobados: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    rechazados: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    misreportes: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
    trabajadores: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>'
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

    if (eyebrowEl) eyebrowEl.textContent = meta.eyebrow;
    if (titleEl) titleEl.textContent = meta.title;
    if (subEl) {
        subEl.textContent = meta.sub || '';
        subEl.classList.toggle('hidden', !meta.sub);
    }
    if (iconEl && HERO_ICONS[meta.icon]) iconEl.innerHTML = HERO_ICONS[meta.icon];

    const headerEl = document.getElementById('pageHeader');
    if (headerEl) headerEl.classList.toggle('section-hero--inicio', seccion === 'inicio');

    if (metaEl) {
        if (seccion === 'inicio') {
            actualizarHeroMetaInicio();
        } else {
            metaEl.textContent = meta.meta || '';
            metaEl.classList.toggle('hidden', !meta.meta);
        }
    }
}

function actualizarHeroMetaInicio() {
    const metaEl = document.getElementById('pageHeaderMeta');
    if (!metaEl) return;

    const pend = parseInt(document.getElementById('porRevisar')?.textContent || '0', 10);
    const team = parseInt(document.getElementById('trabajadores')?.textContent || '0', 10);
    const ok = parseInt(document.getElementById('aprobados')?.textContent || '0', 10);

    let texto = '';
    if (pend > 0) {
        texto = `${pend} pendiente${pend !== 1 ? 's' : ''} de revisión`;
        if (team > 0) texto += ` · ${team} integrante${team !== 1 ? 's' : ''} en tu equipo`;
    } else if (team > 0) {
        texto = `${team} integrante${team !== 1 ? 's' : ''} en tu equipo · bandeja al día`;
        if (ok > 0) texto += ` · ${ok} aprobado${ok !== 1 ? 's' : ''} por ti`;
    } else {
        texto = 'Sin integrantes asignados aún en el organigrama';
    }

    metaEl.textContent = texto;
    metaEl.classList.remove('hidden');
}

function setNavDisabled(navId, disabled, title) {
    const ids = [navId, 'nav-mobile-' + navId.replace('nav-', '')];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('nav-disabled', disabled);
        if (disabled && title) el.title = title;
        else el.removeAttribute('title');
    });
}
