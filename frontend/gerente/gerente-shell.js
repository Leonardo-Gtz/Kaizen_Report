const HERO_META = {
    inicio: {
        eyebrow: 'Inicio',
        title: 'Panel del gerente',
        sub: 'Resumen del área, reportes pendientes de autorización y tendencia mensual.',
        meta: '',
        icon: 'inicio'
    },
    revisar: {
        eyebrow: 'Revisión',
        title: 'Reportes por autorizar',
        sub: 'Reportes aprobados por supervisión que esperan tu autorización.',
        meta: 'Filtra por año y mes para acotar la bandeja.',
        icon: 'revisar'
    },
    autorizados: {
        eyebrow: 'Historial',
        title: 'Reportes autorizados',
        sub: 'Reportes que ya autorizaste y avanzaron hacia RH.',
        meta: 'Filtra por período para consultar autorizaciones anteriores.',
        icon: 'autorizados'
    },
    rechazados: {
        eyebrow: 'Historial',
        title: 'Reportes rechazados',
        sub: 'Reportes devueltos con motivo para corrección.',
        meta: '',
        icon: 'rechazados'
    }
};

const HERO_ICONS = {
    inicio: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    revisar: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    autorizados: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    rechazados: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
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
    const sup = parseInt(document.getElementById('supervisores')?.textContent || '0', 10);
    const auth = parseInt(document.getElementById('autorizados')?.textContent || '0', 10);

    let texto = '';
    if (pend > 0) {
        texto = `${pend} pendiente${pend !== 1 ? 's' : ''} de autorización`;
    } else {
        texto = 'Bandeja al día';
    }
    if (sup > 0) texto += ` · ${sup} supervisor${sup !== 1 ? 'es' : ''} en el área`;
    else if (auth > 0) texto += ` · ${auth} autorizado${auth !== 1 ? 's' : ''} por ti`;

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
