# 🚀 INICIO RÁPIDO - NUEVA FUNCIONALIDAD

## ¡Bienvenido a la Configuración Visual de Certificados!

Tu sistema de certificados ahora incluye una interfaz visual para configurar dónde se mostrará el nombre y el código QR en tus certificados.

---

## ⚡ Empezar en 3 Pasos

### Paso 1: Abrir el Sistema
```
http://localhost/cce-certificados/public/
```

### Paso 2: Configurar Plantilla
Haz clic en el botón:
```
⚙️ Configurar Plantilla
```

### Paso 3: ¡Arrastrar y Listo!
1. Arrastra el marcador azul (N) donde quieres el NOMBRE
2. Arrastra el marcador verde (QR) donde quieres el CÓDIGO QR
3. Haz clic en "💾 Guardar Configuración"

---

## 🎨 Funciones Principales

### Arrastrar y Soltar
- **Marcador Azul (N)**: Posición del nombre del certificado
- **Marcador Verde (QR)**: Posición del código QR

### Personalización
- **Fuente**: Elige entre fuentes del sistema o personalizadas
- **Tamaño**: Ajusta de 10 a 200 píxeles
- **Color**: Selector de color visual

### Vista Previa
- Haz clic en "Ver Vista Previa" para generar un certificado de ejemplo
- Verifica que todo se vea como esperas
- Ajusta si es necesario

---

## 📋 Primeros Pasos Recomendados

1. **Verificar el Sistema**
   ```powershell
   php verify_system.php
   ```
   Debe mostrar: ✅ ¡Todo está configurado correctamente!

2. **Abrir Configuración**
   - Ve a la página principal
   - Haz clic en "⚙️ Configurar Plantilla"

3. **Posicionar Elementos**
   - Arrastra el marcador azul al centro del certificado
   - Arrastra el marcador verde a una esquina

4. **Probar Vista Previa**
   - Haz clic en "Ver Vista Previa"
   - Verifica que todo esté bien posicionado

5. **Guardar y Probar**
   - Haz clic en "💾 Guardar Configuración"
   - Genera un certificado real
   - ¡Listo!

---

## 🎯 Posiciones Recomendadas

Para plantilla A4 horizontal estándar:

### Nombre Centrado
- **X:** 1754 (centro horizontal)
- **Y:** 1000 (parte superior-media)

### QR en Esquina Inferior Derecha
- **X:** 3300
- **Y:** 2300

### QR en Esquina Inferior Izquierda
- **X:** 200
- **Y:** 2300

---

## 📚 Más Información

- **Guía Rápida:** `GUIA-RAPIDA.md`
- **Configuración Detallada:** `CONFIGURACION-PLANTILLA.md`
- **Resumen de Implementación:** `RESUMEN-IMPLEMENTACION.md`
- **Instalación:** `README.md`

---

## ❓ ¿Problemas?

### El sistema no abre
```powershell
# Verificar XAMPP
# 1. Abrir XAMPP Control Panel
# 2. Iniciar Apache y MySQL
```

### Error de extensión GD
```
# Editar php.ini
# Buscar: ;extension=gd
# Cambiar a: extension=gd
# Reiniciar Apache
```

### Certificado no se genera
```powershell
# Ejecutar diagnóstico
php verify_system.php
```

---

## ✅ Checklist de Primera Vez

- [ ] XAMPP instalado y corriendo
- [ ] Base de datos creada e importada
- [ ] GD extension habilitada
- [ ] Plantilla PNG/JPG en `assets/templates/`
- [ ] Permisos de escritura en `uploads/`
- [ ] Sistema verificado con `verify_system.php`
- [ ] Configuración visual probada
- [ ] Certificado de prueba generado

---

## 🎉 ¡Listo para Usar!

Tu sistema está completamente funcional. Puedes:

✅ Generar certificados con posicionamiento personalizado
✅ Usar fuentes personalizadas
✅ Ver vista previa antes de generar
✅ Verificar certificados públicamente
✅ Gestionar todos los certificados

---

**¡Disfruta generando certificados profesionales! 🎓**

Para cualquier duda, consulta la documentación incluida.
