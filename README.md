# Sistema de Certificados CCE

Sistema web para generar certificados digitales con códigos QR de verificación.

## ✨ Características

- ✅ Generación automática de certificados en PDF y PNG
- ✅ Códigos QR de verificación únicos
- ✅ **Configuración visual de posición de nombre y QR** (¡Nuevo!)
- ✅ Vista previa en tiempo real
- ✅ Soporte para fuentes personalizadas
- ✅ Interfaz intuitiva de arrastrar y soltar
- ✅ Verificación pública de certificados
- ✅ Registro de verificaciones

## 📋 Requisitos

### Para desarrollo local:
- **PHP 7.4 o superior** con extensiones:
  - GD (para manipulación de imágenes)
  - PDO MySQL
  - mbstring
- **MySQL/MariaDB 5.7+**
- **Composer** (gestor de dependencias PHP)
- **XAMPP/WAMP/LARAGON** (recomendado para Windows)

### Para producción (cPanel):
- Hosting con cPanel
- PHP 7.4+
- MySQL
- Acceso a cron jobs (opcional)

## 🚀 Instalación Local

### 1. Instalar XAMPP (si no lo tienes)

Descarga e instala XAMPP desde: https://www.apachefriends.org/

### 2. Clonar/Copiar el proyecto

```bash
# Copiar el proyecto a la carpeta htdocs de XAMPP
# Normalmente en: C:\xampp\htdocs\cce-certificados
```

### 3. Instalar dependencias PHP

Abre PowerShell/CMD en la carpeta del proyecto:

```powershell
# Navegar a la carpeta del proyecto
cd "C:\Users\alexi\Desktop\Casa de la Cultura CCE\cce-certificados"

# Instalar Composer si no lo tienes
# Descarga desde: https://getcomposer.org/download/

# Instalar dependencias
composer install
```

### 4. Configurar base de datos

1. Abre XAMPP Control Panel
2. Inicia Apache y MySQL
3. Abre phpMyAdmin: http://localhost/phpmyadmin
4. Crea una nueva base de datos llamada `cce_certificados`
5. Importa el archivo `database/schema.sql`

O ejecuta desde línea de comandos:

```powershell
# Desde la carpeta de XAMPP
cd C:\xampp\mysql\bin

# Crear base de datos e importar schema
.\mysql -u root -p -e "CREATE DATABASE cce_certificados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
.\mysql -u root -p cce_certificados < "C:\Users\alexi\Desktop\Casa de la Cultura CCE\cce-certificados\database\schema.sql"
```

### 5. Configurar conexión a base de datos

```powershell
# Copiar el archivo de ejemplo
cd "C:\Users\alexi\Desktop\Casa de la Cultura CCE\cce-certificados\config"
Copy-Item database.example.php database.php

# Editar database.php con tus credenciales (usualmente root sin password en local)
```

### 6. Preparar plantilla de certificado

1. Crea una imagen PNG de tu certificado en tamaño A4 landscape (297x210mm o ~3508x2480 px)
2. Guárdala en: `assets/templates/default_template.png`
3. Opcionalmente, agrega fuentes personalizadas en: `assets/fonts/`
4. Agrega el logo de tu institución en: `assets/logos/logo-cce.png`

### 7. Configurar permisos de escritura

En Windows con XAMPP, asegúrate que la carpeta `uploads/` tenga permisos de escritura:

```powershell
# Dar permisos completos a la carpeta uploads
icacls "C:\Users\alexi\Desktop\Casa de la Cultura CCE\cce-certificados\uploads" /grant Everyone:F
```

### 8. Probar la aplicación

1. Abre tu navegador
2. Ve a: http://localhost/cce-certificados/public/
3. Genera un certificado de prueba
4. Verifica que se generen los archivos en `uploads/`

### 9. Configurar posición del nombre y QR

1. En la página principal, haz clic en **"⚙️ Configurar Plantilla"**
2. Arrastra los marcadores (azul para NOMBRE, verde para QR) sobre la imagen
3. Ajusta el tamaño de fuente, color y otras opciones
4. Haz clic en **"Ver Vista Previa"** para probar la configuración
5. Guarda los cambios

Ver guía completa: [CONFIGURACION-PLANTILLA.md](CONFIGURACION-PLANTILLA.md)

## 📦 Subir a cPanel

### 1. Preparar archivos para producción

```powershell
# Crear archivo ZIP excluyendo archivos innecesarios
Compress-Archive -Path * -DestinationPath cce-certificados-produccion.zip -Force
```

O manualmente, excluye:
- Carpeta `.git/`
- Carpeta `vendor/` (se reinstalará en el servidor)
- `composer.lock`
- `config/database.php`

### 2. Subir archivos a cPanel

1. Accede a tu cPanel
2. Ve a **File Manager**
3. Navega a `public_html/` (o la carpeta que quieras usar)
4. Sube el archivo ZIP
5. Extrae el archivo ZIP

### 3. Instalar dependencias en el servidor

**Opción A: Desde terminal SSH (si tienes acceso)**

```bash
cd public_html/cce-certificados
composer install --no-dev --optimize-autoloader
```

**Opción B: Si no tienes SSH**

1. Instala las dependencias localmente
2. Sube la carpeta `vendor/` completa via FTP/File Manager

### 4. Crear base de datos en cPanel

1. En cPanel, ve a **MySQL Databases**
2. Crea una nueva base de datos: `tunombre_cce`
3. Crea un usuario MySQL y asigna todos los privilegios
4. Anota: nombre de BD, usuario y contraseña

### 5. Importar schema SQL

1. En cPanel, ve a **phpMyAdmin**
2. Selecciona tu base de datos
3. Ve a la pestaña **Import**
4. Sube el archivo `database/schema.sql`

### 6. Configurar database.php

1. Copia `config/database.example.php` a `config/database.php`
2. Edita con tus credenciales de producción:

```php
$config = [
    'host' => 'localhost',
    'database' => 'tunombre_cce',
    'username' => 'tunombre_usuario',
    'password' => 'tu_password_seguro',
    'charset' => 'utf8mb4'
];

define('BASE_URL', 'https://tudominio.com');
```

### 7. Configurar permisos

En File Manager, haz clic derecho en la carpeta `uploads/` y establece permisos **755** o **777**

### 8. Verificar instalación

1. Ve a: https://tudominio.com/public/
2. Genera un certificado de prueba
3. Escanea el código QR generado

## 🎨 Personalización

### Cambiar plantilla de certificado

1. Sube tu imagen PNG/JPG a `assets/templates/`
2. Actualiza en la base de datos:

```sql
UPDATE configuracion_plantillas 
SET archivo_plantilla = 'tu_plantilla.png',
    posicion_nombre_x = 400,
    posicion_nombre_y = 300,
    tamanio_fuente = 48
WHERE id = 1;
```

### Agregar fuentes personalizadas

1. Sube archivos `.ttf` o `.otf` a `assets/fonts/`
2. Actualiza en la base de datos:

```sql
UPDATE configuracion_plantillas 
SET fuente_nombre = 'MiFuente'
WHERE id = 1;
```

### Cambiar posición del QR

```sql
UPDATE configuracion_plantillas 
SET posicion_qr = 'bottom-right',  -- opciones: top-left, top-right, bottom-left, bottom-right
    posicion_qr_x = 50,
    posicion_qr_y = 50
WHERE id = 1;
```

## 🔧 Troubleshooting

### Error: "Class not found"
```powershell
composer dump-autoload
```

### Error: "Cannot write to uploads/"
```powershell
icacls uploads /grant Everyone:F
```

### Error de conexión a base de datos
- Verifica credenciales en `config/database.php`
- Asegúrate que MySQL esté corriendo

### Certificados no se generan
- Verifica que existe `assets/templates/default_template.png`
- Verifica permisos de escritura en `uploads/`
- Revisa logs de PHP (en XAMPP: `xampp/apache/logs/error.log`)

### QR no redirige correctamente
- Verifica que `BASE_URL` esté correctamente configurado en `config/database.php`

## 📚 Estructura del Proyecto

```
cce-certificados/
├── assets/
│   ├── fonts/          # Fuentes personalizadas (.ttf, .otf)
│   ├── templates/      # Plantillas de certificados (PNG/JPG)
│   └── logos/          # Logo institucional
├── config/
│   └── database.php    # Configuración de BD (crear desde .example)
├── database/
│   └── schema.sql      # Estructura de base de datos
├── includes/
│   └── Certificate.php # Clase principal de certificados
├── public/             # Carpeta pública (punto de entrada)
│   ├── css/
│   ├── js/
│   ├── index.php       # Página principal
│   ├── generate.php    # API para generar certificados
│   ├── verify.php      # Verificación de certificados
│   └── list.php        # API para listar certificados
├── uploads/            # Certificados generados (PNG + PDF)
├── vendor/             # Dependencias de Composer
├── .htaccess          # Redireccionamiento a /public
└── composer.json       # Dependencias PHP
```

## 🔒 Seguridad

- Todos los archivos sensibles están protegidos por `.htaccess`
- Las consultas SQL usan prepared statements (PDO)
- La carpeta `uploads/` solo permite acceso a imágenes y PDFs
- Los datos se validan y sanitizan antes de procesarse

## 📝 Licencia

Proyecto desarrollado para Casa de la Cultura CCE.

---

**¿Necesitas ayuda?** Contacta al administrador del sistema.
