# Configuración de Plantillas por Categoría

## Descripción

El sistema ahora permite que cada categoría pueda tener su propia configuración de plantilla independiente de la configuración del grupo al que pertenece.

## Jerarquía de Configuración

El sistema carga la configuración en el siguiente orden (cada nivel sobrescribe al anterior):

1. **Configuración Global** (tabla `configuracion_plantillas`)
2. **Configuración del Grupo** (tabla `grupos`)
3. **Configuración de la Categoría** (tabla `categorias`) - Solo si `usar_plantilla_propia = 1`

## Características

### Por Categoría se puede configurar:

- ✅ **Plantilla personalizada**: Imagen de fondo diferente para la categoría
- ✅ **Fuente y tamaño**: Tipo de fuente y tamaño del texto
- ✅ **Color de texto**: Color personalizado para el texto
- ✅ **Posiciones**: Ubicación de nombre, razón, QR, firma y fecha
- ✅ **Variables habilitadas**: Seleccionar qué elementos mostrar (nombre, QR, firma)
- ✅ **Firma personalizada**: Nombre, cargo e imagen de firma específica
- ✅ **Tamaños**: Tamaño del QR y de la firma

### Modos de Operación

#### Modo Herencia (usar_plantilla_propia = 0)
- La categoría hereda toda la configuración del grupo
- No requiere configuración adicional
- Simplifica la gestión cuando múltiples categorías comparten el mismo diseño

#### Modo Personalizado (usar_plantilla_propia = 1)
- La categoría tiene su propia configuración completa
- Permite diseños únicos por categoría
- Sobrescribe completamente la configuración del grupo

## Base de Datos

### Campos agregados a la tabla `categorias`:

```sql
- plantilla_archivo VARCHAR(255)           -- Archivo de plantilla
- plantilla_fuente VARCHAR(100)            -- Fuente del texto
- plantilla_tamanio_fuente INT             -- Tamaño de fuente
- plantilla_color_texto VARCHAR(7)         -- Color en formato hex
- plantilla_variables_habilitadas TEXT     -- JSON con variables activas
- plantilla_pos_nombre_x INT               -- Posición X del nombre
- plantilla_pos_nombre_y INT               -- Posición Y del nombre
- plantilla_pos_razon_x INT                -- Posición X de la razón
- plantilla_pos_razon_y INT                -- Posición Y de la razón
- plantilla_pos_qr_x INT                   -- Posición X del QR
- plantilla_pos_qr_y INT                   -- Posición Y del QR
- plantilla_pos_firma_x INT                -- Posición X de la firma
- plantilla_pos_firma_y INT                -- Posición Y de la firma
- plantilla_pos_fecha_x INT                -- Posición X de la fecha
- plantilla_pos_fecha_y INT                -- Posición Y de la fecha
- plantilla_tamanio_qr INT                 -- Tamaño del código QR
- plantilla_archivo_firma VARCHAR(255)     -- Archivo de imagen de firma
- usar_plantilla_propia TINYINT(1)         -- Flag para activar configuración propia
```

## Archivos del Sistema

### Archivos principales:

1. **`config_categoria.php`** - Interfaz para configurar plantilla de categoría
2. **`config_categoria.js`** - Lógica del frontend (drag & drop, validación)
3. **`api_categoria_config.php`** - API para guardar/cargar configuración
4. **`Certificate.php`** - Clase actualizada con soporte para plantillas por categoría
5. **`migration_categoria_plantillas.sql`** - Script de migración de base de datos

### Flujo de funcionamiento:

1. Usuario accede a "Configurar Plantilla" desde una categoría
2. Activa el switch "Usar configuración personalizada"
3. Configura todos los parámetros de la plantilla
4. Guarda la configuración
5. Al generar certificados, el sistema usa la configuración de la categoría

## Uso

### Acceder a la configuración:

1. Navegar a un grupo
2. Entrar a una categoría
3. Hacer clic en "⚙️ Configurar Plantilla"
4. Activar el toggle "Usar configuración personalizada"
5. Configurar la plantilla según necesidades
6. Guardar

### Volver a heredar del grupo:

1. Acceder a "Configurar Plantilla" de la categoría
2. Desactivar el toggle "Usar configuración personalizada"
3. Guardar
4. La categoría volverá a usar la configuración del grupo

## Casos de Uso

### Ejemplo 1: Talleres con diferentes estilos
- Grupo: "Talleres 2025"
- Categoría "Taller de Pintura": Plantilla con fondo artístico
- Categoría "Taller de Música": Plantilla con notas musicales
- Ambas categorías con configuraciones independientes

### Ejemplo 2: Eventos corporativos
- Grupo: "Eventos Corporativos"
- Categoría "Conferencias": Plantilla formal
- Categoría "Workshops": Plantilla dinámica
- Categoría "Networking": Hereda configuración del grupo

## Migración

Para aplicar la migración en una base de datos existente:

```bash
mysql -u root -p < database/migration_categoria_plantillas.sql
```

O desde PowerShell (Windows con XAMPP):

```powershell
Get-Content "database/migration_categoria_plantillas.sql" | & "c:\xampp\mysql\bin\mysql.exe" -u root -p
```

## Notas Técnicas

- Los archivos de plantilla se guardan en `assets/templates/`
- Los archivos de firma se guardan en `assets/templates/`
- La nomenclatura de archivos incluye el ID de categoría y timestamp
- El sistema valida que la categoría exista y esté activa
- Los cambios se reflejan inmediatamente en certificados nuevos
- Los certificados ya generados mantienen su diseño original

## Solución de Problemas

### La categoría no muestra su configuración personalizada
- Verificar que `usar_plantilla_propia = 1` en la base de datos
- Limpiar caché del navegador
- Verificar permisos en el directorio `assets/templates/`

### Los certificados no usan la plantilla de la categoría
- Verificar que al generar el certificado se pase el `categoria_id`
- Revisar los logs del servidor para errores
- Verificar que el archivo de plantilla existe físicamente

### Archivos no se suben
- Verificar permisos de escritura en `assets/templates/` (755 o 777)
- Verificar límite de tamaño de archivo en `php.ini`
- Revisar el tamaño de imagen (recomendado: 1600x1120px)

## Mantenimiento

### Limpieza de archivos huérfanos:

Es recomendable periódicamente limpiar archivos de plantillas que ya no se usan:

```sql
-- Ver plantillas que no están siendo usadas
SELECT plantilla_archivo FROM categorias 
WHERE plantilla_archivo IS NOT NULL 
AND usar_plantilla_propia = 0;
```

### Backup de configuraciones:

Antes de cambios importantes, respaldar las configuraciones:

```sql
-- Backup de configuraciones de categorías
SELECT id, nombre, usar_plantilla_propia, plantilla_archivo, plantilla_fuente
FROM categorias 
WHERE usar_plantilla_propia = 1;
```

## Changelog

### v1.0.0 - 2025-11-24
- ✅ Implementación inicial de plantillas por categoría
- ✅ Interfaz de configuración visual con drag & drop
- ✅ API REST para gestionar configuraciones
- ✅ Integración con clase Certificate.php
- ✅ Migración de base de datos
- ✅ Toggle para activar/desactivar plantilla personalizada
