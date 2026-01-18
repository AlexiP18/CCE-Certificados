# GUÍA RÁPIDA DE INSTALACIÓN LOCAL

## ⚡ Pasos Rápidos (Windows)

### 1. Instalar XAMPP
- Descarga: https://www.apachefriends.org/
- Instala en: `C:\xampp`
- Inicia Apache y MySQL desde XAMPP Control Panel

### 2. Instalar Composer
- Descarga: https://getcomposer.org/Composer-Setup.exe
- Ejecuta el instalador (detectará automáticamente PHP de XAMPP)

### 3. Configurar el Proyecto

Abre PowerShell como Administrador:

```powershell
# 1. Copiar proyecto a htdocs de XAMPP
Copy-Item "C:\Users\alexi\Desktop\Casa de la Cultura CCE\cce-certificados" "C:\xampp\htdocs\" -Recurse

# 2. Navegar al proyecto
cd C:\xampp\htdocs\cce-certificados

# 3. Instalar dependencias PHP
composer install

# 4. Configurar base de datos
Copy-Item config\database.example.php config\database.php

# 5. Dar permisos a uploads
icacls uploads /grant Everyone:F
```

### 4. Crear Base de Datos

```powershell
cd C:\xampp\mysql\bin

# Crear base de datos
.\mysql -u root -e "CREATE DATABASE cce_certificados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar schema
.\mysql -u root cce_certificados < "C:\xampp\htdocs\cce-certificados\database\schema.sql"
```

### 5. Preparar Plantilla

```powershell
cd C:\xampp\htdocs\cce-certificados

# Crear una plantilla de ejemplo (imagen de 1754x1240px en blanco)
# O copia tu plantilla real a: assets\templates\default_template.png
```

**Importante:** Necesitas crear o copiar:
- `assets\templates\default_template.png` - Tu plantilla de certificado
- `assets\logos\logo-cce.png` - Logo de tu institución (opcional)

### 6. Probar la Aplicación

Abre tu navegador en:
```
http://localhost/cce-certificados/public/
```

## 🎯 Verificación Rápida

✅ **Checklist antes de probar:**

1. XAMPP Apache ✓ (puerto 80)
2. XAMPP MySQL ✓ (puerto 3306)
3. Base de datos `cce_certificados` creada ✓
4. Archivo `config/database.php` configurado ✓
5. Carpeta `uploads/` con permisos de escritura ✓
6. Plantilla `assets/templates/default_template.png` existe ✓
7. Dependencias instaladas (`vendor/` existe) ✓

## 🐛 Solución de Problemas Comunes

### Error: "Port 80 already in use"
```powershell
# Detener IIS si está corriendo
net stop was /y
```

### Error: "composer: command not found"
```powershell
# Reinicia PowerShell después de instalar Composer
# O usa la ruta completa:
C:\ProgramData\ComposerSetup\bin\composer install
```

### Error: "Access denied for user 'root'"
Edita `config/database.php`:
```php
'username' => 'root',
'password' => '',  // Vacío por defecto en XAMPP
```

### La página no carga
```powershell
# Verifica que Apache esté corriendo
netstat -ano | findstr :80

# Accede por IP local si es necesario
# http://127.0.0.1/cce-certificados/public/
```

## 📱 Generar Certificado de Prueba

Una vez que la aplicación esté funcionando:

1. Ve a: http://localhost/cce-certificados/public/
2. Llena el formulario:
   - **Nombre:** Juan Pérez García
   - **Razón:** Por su participación en el taller de fotografía 2025
   - **Fecha:** (fecha actual)
3. Click en "Generar Certificado"
4. Descarga el PDF y PNG generados
5. Escanea el código QR para verificar

## 🚀 Siguiente Paso: Subir a cPanel

Una vez que funcione localmente, sigue la sección **"Subir a cPanel"** del README.md principal.

---

**Tiempo estimado:** 15-20 minutos
