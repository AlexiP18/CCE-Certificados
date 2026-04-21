# Sistema de Certificados CCE

Sistema web para generar certificados digitales con códigos QR de verificación.

## ✨ Características

- ✅ Generación automática de certificados en PDF y PNG
- ✅ Códigos QR de verificación únicos
- ✅ Configuración visual de plantillas por grupo y categoría
- ✅ Vista previa en tiempo real con drag & drop
- ✅ Soporte para fuentes personalizadas
- ✅ Verificación pública de certificados
- ✅ Gestión de grupos, categorías, períodos y estudiantes
- ✅ Exportación a Excel y PDF
- ✅ Sistema de roles y permisos

## 📋 Requisitos

### Para desarrollo local
- **PHP 7.4+** con extensiones: GD, PDO MySQL, mbstring
- **MySQL/MariaDB 5.7+**
- **Composer** (gestor de dependencias PHP)
- **XAMPP** (recomendado para Windows)

### Para producción (cPanel)
- Hosting con cPanel y PHP 7.4+
- MySQL
- Acceso SSH (recomendado, no obligatorio)

---

## 🚀 Instalación Local (XAMPP)

### 1. Instalar prerrequisitos
- XAMPP: https://www.apachefriends.org/
- Composer: https://getcomposer.org/Composer-Setup.exe

### 2. Configurar el proyecto

```powershell
# Copiar proyecto a htdocs
Copy-Item "ruta\del\proyecto\cce-certificados" "C:\xampp\htdocs\" -Recurse

# Navegar al proyecto e instalar dependencias
cd C:\xampp\htdocs\cce-certificados
composer install

# Configurar base de datos
Copy-Item config\database.example.php config\database.php
# Editar config\database.php con tus credenciales (root sin password por defecto en XAMPP)
```

### 3. Crear base de datos

```powershell
cd C:\xampp\mysql\bin

# Crear base de datos e importar schema
.\mysql -u root -e "CREATE DATABASE cce_certificados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
.\mysql -u root cce_certificados < "C:\xampp\htdocs\cce-certificados\database\schema\schema.sql"
```

### 4. Preparar archivos

- Plantilla de certificado: `assets/templates/default_template.png` (A4 horizontal, ~3508x2480 px)
- Logo institucional (opcional): `assets/logos/logo-cce.png`
- Fuentes personalizadas (opcional): `assets/fonts/MiFuente.ttf`

```powershell
# Dar permisos a carpeta de uploads
icacls uploads /grant Everyone:F
```

### 5. Probar

1. Inicia Apache y MySQL en XAMPP Control Panel
2. Abre: http://localhost/cce-certificados/public/auth/login.php
3. Genera un certificado de prueba

### ✅ Checklist de verificación local

- [ ] XAMPP Apache (puerto 80) y MySQL (puerto 3306) corriendo
- [ ] Base de datos `cce_certificados` creada e importada
- [ ] `config/database.php` configurado
- [ ] `uploads/` con permisos de escritura
- [ ] Plantilla en `assets/templates/`
- [ ] Dependencias instaladas (`vendor/` existe)

---

## 📦 Despliegue en cPanel (Producción)

### 1. Preparar archivos

```powershell
# Crear ZIP excluyendo archivos innecesarios
Compress-Archive -Path * -DestinationPath cce-certificados-produccion.zip -Force
```

Excluir: `.git/`, `config/database.php`

### 2. Crear base de datos en cPanel

1. En cPanel → **MySQL Databases**
2. Crear base de datos (ej: `tunombre_cce_certificados`)
3. Crear usuario MySQL y asignar **todos los privilegios**

### 3. Subir archivos

**Método A: File Manager**
1. En cPanel → **File Manager** → `public_html/`
2. Subir ZIP → Extraer

**Método B: FTP**
```
Host: tudominio.com
Puerto: 21
Usuario: tu_usuario_ftp
```

### 4. Instalar dependencias

**Con SSH:**
```bash
cd public_html/cce-certificados
composer install --no-dev --optimize-autoloader
```

**Sin SSH:** Sube la carpeta `vendor/` completa vía FTP/File Manager.

### 5. Configurar `database.php`

```php
$config = [
    'host' => 'localhost',
    'database' => 'tunombre_cce_certificados',
    'username' => 'tunombre_usuario',
    'password' => 'tu_password_seguro',
    'charset' => 'utf8mb4'
];

define('BASE_URL', 'https://tudominio.com/cce-certificados/public');
```

### 6. Importar schema

En cPanel → **phpMyAdmin** → Tu BD → **Import** → `database/schema/schema.sql`

### 7. Configurar permisos y .htaccess

```bash
# Permisos
uploads/ → 755 o 777
config/  → 755
```

Archivo `.htaccess` en la raíz del proyecto:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
```

### 8. Seguridad adicional en producción

1. **Proteger config/**
   ```apache
   # config/.htaccess
   Deny from all
   ```

2. **Habilitar HTTPS** en cPanel → SSL/TLS Status → Let's Encrypt

### ✅ Checklist de producción

- [ ] Base de datos creada e importada
- [ ] Usuario MySQL con permisos
- [ ] Archivos subidos al servidor
- [ ] `config/database.php` con credenciales de producción
- [ ] `BASE_URL` correctamente configurado
- [ ] Permisos en `uploads/`
- [ ] `.htaccess` configurado
- [ ] Certificado SSL activo
- [ ] Certificado de prueba generado exitosamente

---

## 🔧 Troubleshooting

| Problema | Solución |
|----------|----------|
| Class not found | `composer dump-autoload` |
| Cannot write to uploads/ | `icacls uploads /grant Everyone:F` |
| Error de conexión a BD | Verificar credenciales en `config/database.php` |
| Certificados no se generan | Verificar plantilla en `assets/templates/` y permisos de `uploads/` |
| QR no redirige | Verificar `BASE_URL` en `config/database.php` |
| Port 80 already in use | `net stop was /y` (detiene IIS) |
| Error extensión GD | En `php.ini`: cambiar `;extension=gd` a `extension=gd`, reiniciar Apache |
| Error 500 en producción | Verificar permisos y `.htaccess` |

---

## 📚 Estructura del Proyecto

```
cce-certificados/
├── app/
│   └── Views/              # Vistas (plantillas HTML)
│       ├── dashboard/
│       ├── grupos/
│       ├── estudiantes/
│       ├── categorias/
│       ├── usuarios/
│       ├── mi_perfil/
│       ├── admin_fuentes/
│       └── login/
├── assets/
│   ├── fonts/              # Fuentes personalizadas (.ttf, .otf)
│   ├── templates/          # Plantillas de certificados
│   └── logos/              # Logo institucional
├── config/
│   └── database.php        # Configuración de BD
├── database/
│   ├── schema/             # Esquemas base
│   ├── migrations/         # Migraciones SQL
│   ├── seeders/            # Scripts de datos iniciales
│   └── scripts/            # Utilidades de diagnóstico
├── includes/
│   ├── Auth.php            # Autenticación
│   └── Certificate.php     # Generación de certificados
├── public/                 # Carpeta pública
│   ├── api/                # Endpoints REST
│   ├── auth/               # Login, verify, logout
│   ├── dashboard/          # Panel principal
│   ├── grupos/             # Gestión de grupos
│   ├── categorias/         # Gestión de categorías
│   ├── estudiantes/        # Gestión de estudiantes
│   ├── certificados/       # Visualización y descarga
│   ├── usuarios/           # Gestión de usuarios
│   ├── perfil/             # Perfil de usuario
│   ├── admin/              # Administración de fuentes
│   ├── css/                # Estilos
│   └── js/                 # Scripts
├── uploads/                # Certificados generados
└── vendor/                 # Dependencias Composer
```

## 🔒 Seguridad

- Autenticación obligatoria con sistema de roles
- Consultas SQL con prepared statements (PDO)
- Sanitización de datos de entrada
- Carpeta `uploads/` protegida
- Archivos de configuración inaccesibles públicamente

## 📝 Licencia

Proyecto desarrollado para Casa de la Cultura CCE.

---

**¿Necesitas ayuda?** Contacta al administrador del sistema.
