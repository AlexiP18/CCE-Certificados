# GUÍA DE DESPLIEGUE EN CPANEL

## 📋 Pre-requisitos

Antes de comenzar, asegúrate de tener:

✅ Acceso a tu cPanel  
✅ Credenciales FTP (opcional, pero recomendado)  
✅ El proyecto funcionando localmente  
✅ Plantilla de certificado preparada  

## 🎯 Paso a Paso

### PASO 1: Preparar Archivos para Producción

#### Opción A: Crear ZIP desde el proyecto local

```powershell
# Navegar a tu proyecto
cd "C:\Users\alexi\Desktop\Casa de la Cultura CCE\cce-certificados"

# Incluir vendor en el ZIP (más fácil para cPanel sin SSH)
Compress-Archive -Path assets, config, database, includes, public, uploads, vendor, .htaccess, composer.json, README.md -DestinationPath cce-certificados.zip -Force
```

**Nota:** Asegúrate de incluir la carpeta `vendor/` con todas las dependencias ya instaladas.

#### Opción B: Usar FileZilla (FTP)

Si prefieres FTP:
1. Descarga FileZilla: https://filezilla-project.org/
2. Conecta usando credenciales de tu hosting
3. Sube todos los archivos EXCEPTO:
   - `.git/`
   - `config/database.php` (lo crearás en el servidor)

---

### PASO 2: Crear Base de Datos MySQL en cPanel

1. **Accede a tu cPanel**
   - URL: `https://tudominio.com:2083` o `https://tudominio.com/cpanel`

2. **Busca "MySQL Databases"**
   - En la sección "Databases"

3. **Crear Nueva Base de Datos**
   - Nombre: `cce_certificados` (cPanel agregará prefijo automático)
   - Nombre real será algo como: `tunombre_cce_certificados`
   - Click en "Create Database"

4. **Crear Usuario MySQL**
   - Usuario: `cce_admin`
   - Password: *genera uno seguro* (guárdalo)
   - Click en "Create User"

5. **Agregar Usuario a la Base de Datos**
   - Selecciona la BD creada
   - Selecciona el usuario creado
   - Click en "Add"
   - Marca "ALL PRIVILEGES"
   - Click en "Make Changes"

📝 **Anota estos datos:**
```
Base de datos: tunombre_cce_certificados
Usuario: tunombre_cce_admin
Password: [tu_password_seguro]
Host: localhost
```

---

### PASO 3: Importar Schema SQL

1. **Ir a phpMyAdmin**
   - En cPanel, busca "phpMyAdmin"
   - Click para abrir

2. **Seleccionar tu base de datos**
   - En el panel izquierdo, click en `tunombre_cce_certificados`

3. **Importar schema.sql**
   - Click en la pestaña "Import"
   - Click en "Choose File"
   - Navega a: `database/schema.sql`
   - Click en "Go"

4. **Verificar tablas creadas**
   - Deberías ver 3 tablas:
     - `certificados`
     - `configuracion_plantillas`
     - `verificaciones`

---

### PASO 4: Subir Archivos al Servidor

#### Método 1: File Manager (recomendado para principiantes)

1. **Abrir File Manager**
   - En cPanel, busca "File Manager"
   - Navega a `public_html/`

2. **Crear carpeta (opcional)**
   ```
   public_html/certificados/
   ```
   O usa directamente `public_html/` si quieres en la raíz

3. **Subir ZIP**
   - Click en "Upload"
   - Selecciona `cce-certificados.zip`
   - Espera a que se complete

4. **Extraer ZIP**
   - Regresa a File Manager
   - Click derecho en `cce-certificados.zip`
   - Click en "Extract"
   - Click en "Extract Files"
   - Elimina el ZIP después de extraer

#### Método 2: FTP con FileZilla

```
Host: ftp.tudominio.com
Username: [tu_usuario_cpanel]
Password: [tu_password_cpanel]
Port: 21
```

Sube todos los archivos a `public_html/certificados/`

---

### PASO 5: Configurar Archivo de Base de Datos

1. **En File Manager**, navega a:
   ```
   public_html/certificados/config/
   ```

2. **Busca `database.example.php`**
   - Click derecho → "Copy"
   - Nombrar copia como: `database.php`

3. **Editar `database.php`**
   - Click derecho → "Edit"
   - Actualiza con tus credenciales:

```php
<?php
$config = [
    'host' => 'localhost',
    'database' => 'tunombre_cce_certificados',  // Tu BD real
    'username' => 'tunombre_cce_admin',          // Tu usuario real
    'password' => 'tu_password_seguro',          // Tu password real
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// IMPORTANTE: Cambia a tu dominio real
define('BASE_URL', 'https://tudominio.com/certificados');

return $pdo;
```

4. **Guardar cambios**

---

### PASO 6: Configurar Permisos

1. **En File Manager**, navega a `uploads/`
   - Click derecho → "Change Permissions"
   - Marca: `755` o `777` (si 755 no funciona)
   - Click en "Change Permissions"

2. **Verificar permisos de otras carpetas:**
   - `assets/templates/` → 755
   - `assets/fonts/` → 755
   - `assets/logos/` → 755

---

### PASO 7: Configurar .htaccess (Importante)

Verifica que el archivo `.htaccess` en la raíz del proyecto tenga:

```apache
# Redirigir a la carpeta public
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
```

**Si usas subcarpeta** (ej: `/certificados/`), actualiza `public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /certificados/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

---

### PASO 8: Subir Plantilla y Logo

1. **Plantilla de certificado**
   - Sube tu imagen PNG/JPG a: `assets/templates/default_template.png`
   - Tamaño recomendado: 3508x2480px (A4 landscape a 300dpi)

2. **Logo institucional**
   - Sube tu logo a: `assets/logos/logo-cce.png`
   - Formato PNG con fondo transparente (recomendado)

---

### PASO 9: Verificar Instalación

1. **Accede a tu sitio:**
   ```
   https://tudominio.com/certificados/
   ```
   Debería redirigir a: `https://tudominio.com/certificados/public/`

2. **Generar certificado de prueba:**
   - Llena el formulario
   - Click en "Generar Certificado"
   - Si todo está bien, se generará el certificado

3. **Verificar archivos generados:**
   - En File Manager, ve a `uploads/`
   - Deberías ver: `cert_CCE-XXXXXXXX.png` y `cert_CCE-XXXXXXXX.pdf`

4. **Probar código QR:**
   - Descarga el PDF generado
   - Escanea el QR con tu celular
   - Debería abrir: `https://tudominio.com/certificados/public/verify.php?code=CCE-XXXXXXXX`

---

## 🔧 Solución de Problemas

### Error 500 - Internal Server Error

**Causa:** Permisos incorrectos o error en `.htaccess`

**Solución:**
```bash
# Verificar permisos
uploads/ → 755 o 777
config/ → 755
.htaccess → 644
```

### Error: "Could not connect to database"

**Causa:** Credenciales incorrectas en `database.php`

**Solución:**
1. Verifica nombre de BD, usuario y password en cPanel
2. Asegúrate que el usuario tenga permisos sobre la BD

### Certificados no se generan

**Causa:** Falta plantilla o permisos de escritura

**Solución:**
```bash
# Verificar que existe:
assets/templates/default_template.png

# Verificar permisos:
uploads/ → 777
```

### QR redirige a URL incorrecta

**Causa:** `BASE_URL` mal configurado

**Solución:**
Edita `config/database.php`:
```php
define('BASE_URL', 'https://tudominio.com/certificados');
```

### Error: "Class not found"

**Causa:** Falta carpeta `vendor/` o no se instaló Composer

**Solución A (Con SSH):**
```bash
cd public_html/certificados
composer install --no-dev
```

**Solución B (Sin SSH):**
1. Instala las dependencias localmente
2. Sube la carpeta `vendor/` completa via FTP

---

## 🔒 Seguridad en Producción

### 1. Proteger archivo de configuración

Agrega al `.htaccess` principal:

```apache
<FilesMatch "database\.php">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 2. Cambiar permisos después de configurar

```bash
config/database.php → 600 (solo lectura del servidor)
```

### 3. Habilitar HTTPS

En cPanel:
1. Busca "SSL/TLS Status"
2. Instala certificado Let's Encrypt (gratis)
3. Fuerza HTTPS agregando a `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## ✅ Checklist Final

- [ ] Base de datos creada e importada
- [ ] Usuario MySQL con permisos
- [ ] Archivos subidos al servidor
- [ ] `config/database.php` configurado
- [ ] Permisos de `uploads/` configurados (755/777)
- [ ] Plantilla subida a `assets/templates/`
- [ ] Logo subido a `assets/logos/`
- [ ] `.htaccess` configurado correctamente
- [ ] `BASE_URL` apunta al dominio correcto
- [ ] Certificado de prueba generado exitosamente
- [ ] Código QR funciona correctamente
- [ ] HTTPS habilitado (recomendado)

---

## 📞 Soporte

Si necesitas ayuda adicional:

1. Revisa logs de error en cPanel → "Error Log"
2. Verifica `public_html/certificados/uploads/` para ver errores
3. Contacta a tu proveedor de hosting si tienes problemas con permisos

---

**¡Listo!** Tu sistema de certificados debería estar funcionando en producción.

🎉 **Próximos pasos:**
- Personaliza la plantilla de certificados
- Ajusta colores y estilos en `public/css/style.css`
- Configura backups automáticos de la base de datos
