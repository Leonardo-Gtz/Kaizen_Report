/**
 * Etiqueta de puesto visible en UI (rol funcional del sistema sin cambios).
 */
(function (global) {
    'use strict';

    const PUESTO_POR_EMP = {
        6: { gerente: 'Advisor' }
    };

    const PUESTO_POR_ROL = {
        rh: 'RH',
        gerente: 'Gerente',
        supervisor: 'Supervisor',
        trabajador: 'Trabajador'
    };

    function puestoEmpleado(empId, rol) {
        const id = Number(empId);
        const r = String(rol || '').toLowerCase();
        if (PUESTO_POR_EMP[id] && PUESTO_POR_EMP[id][r]) {
            return PUESTO_POR_EMP[id][r];
        }
        return PUESTO_POR_ROL[r] || (r ? r.charAt(0).toUpperCase() + r.slice(1) : '');
    }

    function etiquetaRevisor(nombre, empId, rol, departamento) {
        const puesto = puestoEmpleado(empId, rol);
        const dept = String(departamento || '').trim();
        const nom = String(nombre || '').trim() || 'Usuario';

        if (puesto && dept) {
            return nom + ' (' + puesto + ' · ' + dept + ')';
        }
        if (puesto) {
            return nom + ' (' + puesto + ')';
        }
        if (dept) {
            return nom + ' (' + dept + ')';
        }
        return nom;
    }

    global.KaizenPuesto = {
        puestoEmpleado,
        etiquetaRevisor
    };
})(window);
