# Sistema de Autenticación - CCE Certificados

## Descripción

Sistema de autenticación con gestión de usuarios y permisos basados en roles.

## Instalación

### 1. Ejecutar la migración de base de datos

Ejecuta el archivo SQL para crear las tablas necesarias:

```sql
mysql -u tu_usuario -p cce_certificados < database/schema_usuarios.sql
```

O importa desde phpMyAdmin:
1. Abre phpMyAdmin
2. Selecciona la base de datos `cce_certificados`
3. Ve a "Importar"
4. Selecciona el archivo `database/schema_usuarios.sql`
5. Ejecutar

### 2. Usuario administrador por defecto

El script crea un usuario administrador:
- **Usuario:** admin
- **Email:** admin@cce.com
- **Contraseña:** admin123

⚠️ **IMPORTANTE:** Cambia la contraseña después del primer inicio de sesión.

## Roles y Permisos

### Roles predefinidos

1. **Administrador** - Acceso completo a todo el sistema
2. **Editor** - Puede ver, crear y editar (no eliminar usuarios)
3. **Operador** - Solo puede ver y crear contenido
4. **Visualizador** - Solo lectura

### Permisos por módulo

| Módulo | Ver | Crear | Editar | Eliminar |
|--------|-----|-------|--------|----------|
| grupos | ✓ | ✓ | ✓ | ✓ |
| categorias | ✓ | ✓ | ✓ | ✓ |
| estudiantes | ✓ | ✓ | ✓ | ✓ |
| certificados | ✓ | ✓ | ✓ | ✓ |
| usuarios | ✓ | ✓ | ✓ | ✓ |

## Uso

### Verificar autenticación en páginas PHP

```php
<?php
require_once '../includes/Auth.php';

// Requerir autenticación (redirige a login si no está autenticado)
Auth::requireAuth();

// Obtener usuario actual
$usuario = Auth::user();
echo "Bienvenido, " . $usuario['nombre_completo'];
```

### Verificar permisos

```php
// Verificar si puede realizar una acción
if (Auth::can('usuarios', 'editar')) {
    // Mostrar botón de editar
}

// Requerir permiso (termina con error 403 si no tiene permiso)
Auth::requirePermission('usuarios', 'eliminar');

// Funciones helper (definidas en Auth.php)
if (puede('usuarios', 'ver')) {
    // ...
}

if (esAdmin()) {
    // ...
}
```

### Cambiar contraseña

```php
$resultado = Auth::changePassword($usuario_id, 'password_actual', 'password_nueva');
if ($resultado['success']) {
    echo "Contraseña actualizada";
}
```

## API de Usuarios

### Endpoints disponibles

| Acción | Descripción |
|--------|-------------|
| `list` | Listar todos los usuarios |
| `get` | Obtener un usuario por ID |
| `create` | Crear nuevo usuario |
| `update` | Actualizar usuario existente |
| `delete` | Eliminar usuario |
| `roles` | Listar roles disponibles |
| `cambiar_password` | Cambiar contraseña propia |
| `mi_perfil` | Obtener perfil del usuario actual |
| `actualizar_perfil` | Actualizar perfil propio |

### Ejemplo de uso con JavaScript

```javascript
// Crear usuario
fetch('api_usuarios.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'create',
        username: 'nuevo_usuario',
        email: 'email@ejemplo.com',
        nombre_completo: 'Nombre Completo',
        password: 'contraseña123',
        rol_id: 2 // Editor
    })
});
```

## Páginas del sistema

- `/public/login.php` - Inicio de sesión
- `/public/logout.php` - Cerrar sesión
- `/public/usuarios.php` - Gestión de usuarios (requiere permisos)

## Seguridad

- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Protección contra fuerza bruta (bloqueo después de 5 intentos)
- Sesiones seguras con regeneración de ID
- Registro de actividad (login, logout, cambios importantes)

## Tablas de la base de datos

### `roles`
- id, nombre, descripcion, permisos (JSON)

### `usuarios`
- id, username, email, password_hash, nombre_completo, rol_id, activo, etc.

### `sesiones_usuario`
- Seguimiento de sesiones activas

### `log_actividad`
- Registro de acciones importantes

## Solución de problemas

### Error "No autenticado"
- Verifica que las cookies de sesión estén habilitadas
- Limpia la caché del navegador

### Error 403 "Sin permiso"
- Verifica que el usuario tenga el rol correcto
- Revisa los permisos del rol en la tabla `roles`

### No puedo iniciar sesión
- Verifica que el usuario esté activo (`activo = 1`)
- Revisa si está bloqueado (`bloqueado_hasta`)
- Verifica la contraseña
