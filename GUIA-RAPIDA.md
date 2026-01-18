# 🎯 Guía Rápida: Configuración Visual de Certificados

## ¿Qué puedes hacer?

Con la nueva interfaz de configuración, puedes:

✅ **Posicionar el nombre** exactamente donde quieras en el certificado
✅ **Posicionar el código QR** en cualquier lugar de la plantilla
✅ **Ajustar fuente, tamaño y color** del texto del nombre
✅ **Ver una vista previa** antes de generar certificados reales
✅ **Arrastrar y soltar** para posicionamiento visual intuitivo

---

## 🚀 Acceso Rápido

1. Abre: `http://localhost/cce-certificados/public/`
2. Haz clic en: **⚙️ Configurar Plantilla**

---

## 📍 Posicionar Elementos

### Método 1: Arrastrar y Soltar (Recomendado)

```
1. Haz clic en el marcador azul (N) o verde (QR)
2. Mantén presionado el botón del mouse
3. Arrastra el marcador a la posición deseada
4. Suelta el botón
5. ¡Listo! Las coordenadas se actualizan automáticamente
```

### Método 2: Valores Numéricos (Precisión)

```
1. Ve a los campos "Posición X" y "Posición Y"
2. Ingresa los valores numéricos
3. El marcador se moverá instantáneamente
4. Útil para alineaciones exactas
```

---

## 🎨 Personalización

### Fuente del Nombre
- **Sistema (por defecto)**: Usa la fuente predeterminada de GD
- **Fuentes personalizadas**: Coloca archivos `.ttf` o `.otf` en `assets/fonts/`

### Tamaño de Fuente
- Rango: 10-200 píxeles
- Recomendado: 40-60 para certificados A4

### Color del Texto
- Selector de color visual
- Formato HEX (#000000)
- Colores comunes:
  - Negro: `#000000`
  - Dorado: `#FFD700`
  - Azul oscuro: `#003366`

---

## 🔍 Vista Previa

### Generar Vista Previa

1. Ajusta la configuración como desees
2. Haz clic en **"Ver Vista Previa"**
3. Se generará un certificado de ejemplo con:
   - Texto: "NOMBRE DE EJEMPLO"
   - QR simulado (rectángulo verde)
4. La vista previa aparece debajo del botón

### Interpretar la Vista Previa

- **Texto visible**: Posición y estilo del nombre correcto ✅
- **QR verde**: Posición donde aparecerá el código QR ✅
- **Texto cortado**: Ajusta la posición o reduce el tamaño de fuente ⚠️
- **QR fuera de límites**: Mueve el marcador hacia el centro ⚠️

---

## 💾 Guardar Cambios

```
1. Verifica que todo esté como lo deseas (usa vista previa)
2. Haz clic en "💾 Guardar Configuración"
3. Aparecerá un mensaje de confirmación
4. Los nuevos certificados usarán esta configuración
```

**Nota:** Los certificados ya generados NO se modifican.

---

## 🔄 Resetear Configuración

Si cometiste un error o quieres empezar de nuevo:

1. Haz clic en **"🔄 Resetear"**
2. La página se recargará con la última configuración guardada

---

## 📐 Sistema de Coordenadas

```
Esquina Superior Izquierda = (0, 0)
     ↓
  0,0 ────────────────→ X (ancho)
   │
   │    [Tu Plantilla]
   │
   ↓
   Y (alto)
```

### Ejemplos de Posiciones

**Para plantilla A4 horizontal (3508 x 2480 px):**

| Elemento | Posición | X | Y |
|----------|----------|---|---|
| Nombre Centro | Centro | 1754 | 1000 |
| QR Inferior Derecha | Esquina | 3300 | 2300 |
| QR Inferior Izquierda | Esquina | 200 | 2300 |
| QR Superior Derecha | Esquina | 3300 | 200 |

---

## 🎓 Consejos y Trucos

### 💡 Alineación del Nombre

El texto siempre se centra en la posición que especificas:

```
Posición X: 1754 (centro de la imagen)
         ↓
    ┌─────────┐
    │ [NOMBRE]│  ← Centrado
    └─────────┘
```

### 💡 Espacio para el QR

El código QR mide **150 x 150 píxeles**. Deja espacio suficiente:

```
Posición elegida: (X, Y)
                   ↓
              ┌───────┐
        75px →│  QR   │← 75px
              │150x150│
              └───────┘
```

### 💡 Fuentes Personalizadas

Para agregar tu propia fuente:

1. Consigue un archivo `.ttf` o `.otf`
2. Nómbralo sin espacios: `MiFuente.ttf`
3. Cópialo a: `assets/fonts/MiFuente.ttf`
4. Recarga la página de configuración
5. Selecciónalo en el menú "Fuente"

### 💡 Colores Profesionales

Algunos colores que funcionan bien:

- **Elegante**: `#1a1a1a` (gris muy oscuro)
- **Corporativo**: `#003366` (azul corporativo)
- **Lujo**: `#8B7355` (bronce)
- **Certificación**: `#C19A6B` (oro envejecido)

---

## ❓ Solución de Problemas

### El nombre no aparece donde lo puse

**Causa:** Las coordenadas pueden estar fuera de los límites de la imagen.

**Solución:** 
- Verifica que X < ancho de la imagen
- Verifica que Y < alto de la imagen
- Usa la vista previa para validar

### La fuente no cambia

**Causa:** El archivo de fuente no está en la carpeta correcta o tiene un nombre incorrecto.

**Solución:**
1. Verifica: `assets/fonts/TuFuente.ttf` existe
2. Recarga la página de configuración
3. Si no aparece, verifica que sea `.ttf` o `.otf`

### El QR se corta

**Causa:** El QR está muy cerca del borde de la plantilla.

**Solución:**
- Mueve el marcador al menos 75 píxeles hacia dentro del borde
- El QR necesita 150x150 px de espacio

### Los cambios no se guardan

**Causa:** Error de conexión o permisos de base de datos.

**Solución:**
1. Verifica la consola del navegador (F12) para errores
2. Verifica que el usuario de BD tenga permisos UPDATE
3. Verifica `save_config.php` esté accesible

---

## 🎬 Flujo de Trabajo Recomendado

```
1. Abrir página de configuración
   ↓
2. Arrastrar marcadores a posiciones aproximadas
   ↓
3. Ajustar fuente, tamaño y color
   ↓
4. Generar vista previa
   ↓
5. Hacer ajustes finos si es necesario
   ↓
6. Generar vista previa nuevamente
   ↓
7. Si se ve bien → Guardar configuración
   ↓
8. Generar certificado de prueba real
   ↓
9. ¡Listo para producción!
```

---

## 📞 ¿Necesitas Ayuda?

Si tienes problemas:

1. Ejecuta: `php verify_system.php`
2. Revisa las instrucciones en: `README.md`
3. Consulta: `CONFIGURACION-PLANTILLA.md` para detalles técnicos

---

**¡Disfruta generando certificados hermosos y profesionales! 🎉**
