# Resumen de Implementación - Sistema de Roles y Permisos

## Fecha: 2024-12-23

## Cambios Realizados

### 1. APIs con Verificación de Permisos

Se actualizaron las siguientes APIs para incluir verificación de permisos en todas las operaciones CRUD:

#### api_estudiantes.php
- `list`: Requiere permiso `estudiantes.ver` + filtrado por categorías del instructor
- `buscar`: Requiere permiso `estudiantes.ver`
- `list_with_details`: Requiere permiso `estudiantes.ver`
- `get_details`: Requiere permiso `estudiantes.ver`
- `get`: Requiere permiso `estudiantes.ver`
- `create`: Requiere permiso `estudiantes.crear`
- `update`: Requiere permiso `estudiantes.editar`
- `delete`: Requiere permiso `estudiantes.eliminar`
- `listar_grupo`: Requiere permiso `estudiantes.ver` + validación de acceso al grupo

#### api_grupos.php
- `list`: Requiere permiso `grupos.ver` + filtrado por grupos del instructor
- `create`: Requiere permiso `grupos.crear`
- `assign_periodos`: Requiere permiso `grupos.editar`
- `update`: Requiere permiso `grupos.editar`
- `delete`: Requiere permiso `grupos.eliminar`
- `get`: Requiere permiso `grupos.ver` + validación de acceso

#### api_categorias.php
- `create`: Requiere permiso `categorias.crear`
- `update`: Requiere permiso `categorias.editar`
- `delete`: Requiere permiso `categorias.eliminar`
- `get`: Requiere permiso `categorias.ver` + validación de acceso
- `listar`: Requiere permiso `categorias.ver` + validación de acceso al grupo

#### api_periodos.php
- `obtener`: Requiere permiso `periodos.ver`
- `list`: Requiere permiso `periodos.ver`
- `crear/create`: Requiere permiso `periodos.crear`
- `actualizar_nombre`: Requiere permiso `periodos.editar`
- `actualizar/update`: Requiere permiso `periodos.editar`
- `eliminar/delete`: Requiere permiso `periodos.eliminar`
- `get_categorias`: Requiere permiso `periodos.ver`
- `asignar_categoria`: Requiere permiso `periodos.editar`
- `copiar_categorias`: Requiere permiso `periodos.editar`

### 2. Auth.php - Nuevas Funciones

Se agregaron las siguientes funciones a la clase Auth:

```php
// Obtener grupos asignados al instructor actual
Auth::getGruposAsignados(): array

// Obtener categorías asignadas al instructor actual
Auth::getCategoriasAsignadas(): array

// Verificar acceso a un grupo específico
Auth::tieneAccesoGrupo(int $grupo_id): bool

// Verificar acceso a una categoría específica
Auth::tieneAccesoCategoria(int $categoria_id): bool
```

Y las funciones helper correspondientes:
- `tieneAccesoGrupo($grupo_id)`
- `tieneAccesoCategoria($categoria_id)`

### 3. Filtrado de Datos por Rol

#### Instructores
- Solo ven grupos asignados en `instructor_grupos`
- Solo ven categorías asignadas en `instructor_categorias`
- Solo ven estudiantes de sus categorías asignadas
- No pueden crear, editar o eliminar (solo ver, según permisos de su rol)

#### Oficinistas
- Acceso según permisos personalizados en `permisos_usuario`
- Pueden tener permisos de crear/editar según configuración del admin

### 4. Menús de Navegación con Permisos

Se actualizaron los menús en las siguientes páginas:
- `index.php` - Menú filtra opciones según permisos
- `mi_perfil.php` - Menú filtra opciones según permisos
- `estudiantes_grupo.php` - Menú filtra opciones según permisos

Ejemplo de uso:
```php
<?php if (puede('estudiantes', 'ver')): ?>
<li><a href="estudiantes.php">Estudiantes</a></li>
<?php endif; ?>
```

### 5. Sistema de Aprobación de Certificados

Se creó la migración `migration_aprobacion_certificados.sql` y el instalador `install_aprobacion.php`.

#### Nuevos campos en `certificados`:
- `aprobado` (TINYINT) - Si el certificado ha sido aprobado
- `aprobado_por` (INT) - Usuario admin que aprobó
- `fecha_aprobacion` (TIMESTAMP) - Cuándo se aprobó
- `requiere_aprobacion` (TINYINT) - Si requiere aprobación antes de generarse

#### Nueva tabla `certificados_aprobaciones`:
Historial de acciones de aprobación con:
- `certificado_id` - Certificado relacionado
- `usuario_id` - Quien realizó la acción
- `accion` - 'aprobar', 'rechazar', 'revocar'
- `comentario` - Motivo/comentario
- `fecha_accion` - Timestamp

## Archivos Modificados

1. `includes/Auth.php` - Nuevas funciones de acceso
2. `public/api_estudiantes.php` - Verificaciones de permisos
3. `public/api_grupos.php` - Verificaciones de permisos
4. `public/api_categorias.php` - Verificaciones de permisos
5. `public/api_periodos.php` - Verificaciones de permisos
6. `public/index.php` - Menú con permisos + filtrado de grupos
7. `public/mi_perfil.php` - Menú con permisos
8. `public/estudiantes_grupo.php` - Menú con permisos

## Archivos Creados

1. `database/migration_aprobacion_certificados.sql` - SQL de migración
2. `database/install_aprobacion.php` - Script PHP de instalación

## Cómo Usar

### Ejecutar Migración
Si la migración no se ha ejecutado:
```bash
php database/install_aprobacion.php
```

### Asignar Instructor a Grupos/Categorías
```sql
-- Asignar grupo a instructor
INSERT INTO instructor_grupos (usuario_id, grupo_id) VALUES (?, ?);

-- Asignar categoría a instructor
INSERT INTO instructor_categorias (usuario_id, categoria_id) VALUES (?, ?);
```

### Personalizar Permisos de Oficinista
```sql
INSERT INTO permisos_usuario (usuario_id, permisos_custom, asignado_por)
VALUES (?, '{"estudiantes":["ver","crear"],"grupos":["ver"]}', ?);
```

## Notas Importantes

1. Los certificados existentes fueron marcados como `aprobado=1` y `requiere_aprobacion=0`
2. Los nuevos certificados requerirán aprobación por defecto
3. El filtrado de instructores usa las tablas `instructor_grupos` e `instructor_categorias`
4. Los oficinistas pueden tener permisos personalizados via `permisos_usuario`
