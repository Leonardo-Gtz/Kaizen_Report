# Gestión de Empleados - Sistema Kaizen

## Nuevas Funcionalidades Implementadas

### 1. Agregar Nuevos Empleados
- Botón "Nuevo Empleado" en la sección de Empleados del dashboard RH
- Modal con formulario para capturar:
  - ID de empleado (único)
  - Nombre
  - Apellido Paterno
  - Apellido Materno (opcional)
  - Departamento
  - Contraseña
- Validaciones:
  - ID único (no duplicado)
  - Nombre y apellido mínimo 2 caracteres
  - Contraseña mínimo 4 caracteres

### 2. Dar de Baja Empleados
- Botón "Dar de Baja" en cada empleado activo
- Modal de confirmación con:
  - Nombre del empleado
  - Campo opcional para motivo de baja
  - Confirmación de acción
- Al dar de baja:
  - Marca al empleado como inactivo (activo = 0)
  - Registra fecha de baja
  - Guarda motivo de baja (opcional)
  - Desactiva jerarquías asociadas automáticamente

### 3. Filtros Mejorados
- Filtro por estado: Activos / Inactivos / Todos
- Filtro por departamento
- Búsqueda por nombre o ID
- Por defecto muestra solo empleados activos

## Archivos Modificados

### Frontend
1. **dashboard.php**
   - Agregado botón "Nuevo Empleado"
   - Modificada tabla de empleados (columnas Estado y Acciones)
   - Agregado filtro de estado (Activo/Inactivo)
   - Modal para nuevo empleado
   - Modal de confirmación de baja

2. **dashboard-fixed.js**
   - Función `abrirModalNuevoEmpleado()`
   - Función `abrirModalBaja(empleadoId, empleadoNombre)`
   - Función `confirmarBajaEmpleado()`
   - Actualizada función `filtrarEmpleados()` para incluir estado
   - Actualizada función `renderizarEmpleados()` con botón de baja
   - Event listener para formulario de nuevo empleado

### Backend (APIs)
1. **api-crear-empleado.php** (NUEVO)
   - Endpoint: POST
   - Crea nuevos empleados en bd_ntn
   - Validaciones de datos
   - Verifica ID único

2. **api-baja-empleado.php** (NUEVO)
   - Endpoint: POST
   - Marca empleado como inactivo
   - Registra fecha y motivo de baja
   - Desactiva jerarquías asociadas

3. **api-empleados.php** (MODIFICADO)
   - Agregado campo `activo` en la respuesta
   - Usa COALESCE para compatibilidad

## Base de Datos

### Cambios Requeridos en la Tabla `bd_ntn`

Ejecutar el script: `sql-agregar-columnas-empleados.sql`

```sql
-- Columnas agregadas:
ALTER TABLE bd_ntn ADD COLUMN activo TINYINT(1) DEFAULT 1;
ALTER TABLE bd_ntn ADD COLUMN fecha_baja DATETIME NULL;
ALTER TABLE bd_ntn ADD COLUMN motivo_baja TEXT NULL;
```

## Instrucciones de Instalación

### Paso 1: Actualizar Base de Datos
```bash
# Conectar a MySQL
mysql -u root -p kaizen_db

# Ejecutar script
source /xampp/htdocs/Kaizen-Final-Back/sql-agregar-columnas-empleados.sql
```

### Paso 2: Verificar Archivos
Asegurarse de que existen:
- ✅ api-crear-empleado.php
- ✅ api-baja-empleado.php
- ✅ dashboard.php (actualizado)
- ✅ dashboard-fixed.js (actualizado)
- ✅ api-empleados.php (actualizado)

### Paso 3: Probar Funcionalidad
1. Iniciar sesión como RH
2. Ir a sección "Empleados"
3. Probar:
   - Crear nuevo empleado
   - Filtrar por estado (Activos/Inactivos)
   - Dar de baja a un empleado
   - Verificar que aparece como inactivo

## Flujo de Trabajo

### Agregar Empleado
1. Usuario RH hace clic en "Nuevo Empleado"
2. Llena formulario con datos requeridos
3. Sistema valida:
   - ID único
   - Campos requeridos
   - Longitud mínima
4. Se crea empleado con estado activo = 1
5. Se recarga lista de empleados

### Dar de Baja
1. Usuario RH hace clic en "Dar de Baja" en empleado activo
2. Aparece modal de confirmación
3. Opcionalmente ingresa motivo
4. Confirma acción
5. Sistema:
   - Marca activo = 0
   - Registra fecha_baja = NOW()
   - Guarda motivo_baja
   - Desactiva jerarquías (tabla jerarquia)
6. Empleado ya no aparece en filtro "Activos"

## Notas Importantes

- Los empleados inactivos NO se eliminan de la base de datos
- Se mantiene historial completo (fecha y motivo de baja)
- Las jerarquías se desactivan automáticamente al dar de baja
- Por defecto, la vista muestra solo empleados activos
- Los empleados inactivos pueden verse seleccionando "Inactivos" o "Todos"

## Seguridad

- Solo usuarios con rol 'rh' pueden:
  - Crear empleados
  - Dar de baja empleados
- Validaciones en frontend y backend
- Transacciones para mantener integridad de datos
- No se permite dar de baja a empleados ya inactivos

## Mejoras Futuras Sugeridas

1. Reactivar empleados inactivos
2. Historial de cambios de estado
3. Exportar lista de empleados (activos/inactivos)
4. Notificaciones al dar de baja
5. Validación de permisos antes de dar de baja (ej: si tiene reportes pendientes)
