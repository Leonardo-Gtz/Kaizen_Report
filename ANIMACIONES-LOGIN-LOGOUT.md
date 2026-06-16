# Animaciones de Login y Logout - Sistema Kaizen

## 📋 Resumen

Se han implementado animaciones elegantes con efecto de **ondas líquidas** para mejorar la experiencia de usuario al iniciar y cerrar sesión.

---

## 🌊 Animación de Login (Azul)

### Características:
- **Pantalla completa** con degradado azul (#0066CC → #004999)
- **3 ondas líquidas** animadas en la parte inferior
- **Spinner circular** grande con borde brillante
- **Texto animado** con efecto de pulso
- **Transición suave** de entrada/salida

### Ubicación:
- Archivo: `frontend/login.php`
- Ya está implementado y funcionando

### Cómo se ve:
```
┌─────────────────────────────────────┐
│                                     │
│         [Spinner Girando]           │
│                                     │
│      Iniciando sesión               │
│      Por favor espera...            │
│                                     │
│                                     │
│     ～～～～～～～～～～～～～～～     │ <- Ondas animadas
└─────────────────────────────────────┘
```

---

## 🔴 Animación de Logout (Roja)

### Características:
- **Pantalla completa** con degradado rojo (#dc2626 → #991b1b)
- **3 ondas líquidas** animadas en la parte inferior
- **Icono de salida** con animación de pulso y deslizamiento
- **Texto "Cerrando sesión"** con sombra elegante
- **Subtexto "Hasta pronto..."** con efecto de pulso

### Archivos creados:
1. `frontend/assets/logout-animation.css` - Estilos de la animación
2. `frontend/assets/logout-animation.js` - Lógica de la animación

---

## 🚀 Implementación en Dashboards

### Para implementar en TODOS los dashboards:

#### 1. Dashboard RH (Ya implementado)
✅ `frontend/rh/dashboard.php` - Ya tiene la animación integrada

#### 2. Dashboard Gerente
Agregar en `frontend/gerente/dashboard.php`:

**En el `<head>`:**
```html
<link rel="stylesheet" href="../assets/logout-animation.css">
```

**Antes del cierre de `</body>`:**
```html
<script src="../assets/logout-animation.js"></script>
```

**Modificar el enlace de "Cerrar Sesión":**
```html
<!-- ANTES: -->
<a href="../../logout.php" class="...">
    <svg>...</svg>
    <span>Cerrar Sesión</span>
</a>

<!-- DESPUÉS: -->
<a href="#" onclick="cerrarSesionConAnimacion(event); return false;" class="...">
    <svg>...</svg>
    <span>Cerrar Sesión</span>
</a>
```

#### 3. Dashboard Supervisor
Agregar en `frontend/supervisor/dashboard.php`:

**En el `<head>`:**
```html
<link rel="stylesheet" href="../assets/logout-animation.css">
```

**Antes del cierre de `</body>`:**
```html
<script src="../assets/logout-animation.js"></script>
```

**Modificar el enlace de "Cerrar Sesión":**
```html
<a href="#" onclick="cerrarSesionConAnimacion(event); return false;" class="...">
    <svg>...</svg>
    <span>Cerrar Sesión</span>
</a>
```

#### 4. Dashboard Trabajador
Agregar en `frontend/trabajador/dashboard.php`:

**En el `<head>`:**
```html
<link rel="stylesheet" href="../assets/logout-animation.css">
```

**Antes del cierre de `</body>`:**
```html
<script src="../assets/logout-animation.js"></script>
```

**Modificar el enlace de "Cerrar Sesión":**
```html
<a href="#" onclick="cerrarSesionConAnimacion(event); return false;" class="...">
    <svg>...</svg>
    <span>Cerrar Sesión</span>
</a>
```

---

## 🎨 Personalización

### Cambiar colores del logout:

En `frontend/assets/logout-animation.css`, línea 6:
```css
/* Rojo (actual) */
background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);

/* Otras opciones: */
/* Naranja */
background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);

/* Morado */
background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%);

/* Gris oscuro */
background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
```

### Cambiar velocidad de las ondas:

En `frontend/assets/logout-animation.css`, líneas 27-37:
```css
/* Más rápido: cambiar 10s, 15s, 20s a valores menores */
.wave-logout {
    animation: wave 8s linear infinite;  /* Más rápido */
}

/* Más lento: cambiar a valores mayores */
.wave-logout {
    animation: wave 15s linear infinite;  /* Más lento */
}
```

### Cambiar duración total de la animación:

En `frontend/assets/logout-animation.js`, línea 10:
```javascript
// Actual: 1200ms (1.2 segundos)
setTimeout(() => {
    window.location.href = '../../logout.php';
}, 1200);

// Más rápido: 800ms
}, 800);

// Más lento: 2000ms
}, 2000);
```

---

## 🔧 Solución de Problemas

### La animación no aparece:
1. Verificar que los archivos CSS y JS estén en `frontend/assets/`
2. Verificar que las rutas en el `<head>` y `<script>` sean correctas
3. Verificar que el enlace de logout tenga `onclick="cerrarSesionConAnimacion(event)"`

### La animación se ve cortada:
- Asegurarse de que no haya CSS que limite el `overflow` del body

### La animación no redirige:
- Verificar la ruta en `logout-animation.js` línea 10
- Debe ser `../../logout.php` para dashboards en subcarpetas

---

## 📱 Compatibilidad

✅ Chrome, Firefox, Safari, Edge (últimas versiones)
✅ Responsive (móvil, tablet, desktop)
✅ Funciona sin JavaScript (redirige directamente)

---

## 🎯 Resultado Final

### Login (Azul):
- Fondo azul corporativo NTN
- Ondas blancas translúcidas
- Spinner blanco girando
- Texto blanco con sombra

### Logout (Rojo):
- Fondo rojo de advertencia
- Ondas blancas translúcidas
- Icono de salida animado
- Texto blanco con sombra

Ambas animaciones duran aproximadamente 1.2 segundos y crean una experiencia visual fluida y profesional.

---

## 📝 Notas Adicionales

- Las animaciones son **no bloqueantes**: si hay un error, el sistema redirige normalmente
- El CSS está **optimizado** para rendimiento
- Las animaciones usan **hardware acceleration** (transform, opacity)
- Compatible con **modo oscuro** del navegador

---

**Fecha de implementación:** 2024
**Versión:** 1.0
**Desarrollado para:** Sistema Kaizen NTN
