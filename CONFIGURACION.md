# Guía de Configuración de Certificados

Esta guía cubre cómo configurar las plantillas de certificados a nivel de **grupo** y **categoría**.

---

## 📍 Resumen del Sistema

El sistema permite personalizar cómo se generan los certificados mediante una interfaz visual de drag & drop. La configuración sigue una jerarquía de herencia:

```
1. Configuración Global  (tabla configuracion_plantillas)
       ↓ sobrescribida por
2. Configuración del Grupo  (tabla grupos)
       ↓ sobrescribida por
3. Configuración de Categoría  (tabla categorias, si usar_plantilla_propia = 1)
```

---

## 🎨 Configuración por Grupo

Cada grupo puede tener su propia configuración personalizada de certificados.

### Acceder a la Configuración

1. Ve a **Grupos** desde el menú principal
2. Haz clic en **⚙️ Configurar** del grupo que deseas personalizar

### Elementos Configurables

| Elemento | Descripción |
|----------|-------------|
| **Plantilla** | Imagen de fondo del certificado (PNG/JPG, 1600x1120px recomendado) |
| **Nombre** | Posición, fuente, tamaño y color del nombre del participante |
| **Código QR** | Posición y tamaño del código QR (50-400px) |
| **Firma** | Nombre del firmante, cargo, imagen de firma y tamaño |
| **Razón** | Texto predeterminado de la razón del certificado |
| **Fecha** | Posición de la fecha de emisión |
| **Variables** | Selección de qué elementos mostrar en el certificado |

### Usar el Lienzo Visual

Los elementos se posicionan con drag & drop sobre la plantilla:

- 🔵 **Marcador azul**: Posición del nombre
- 🟢 **Marcador verde**: Posición del código QR
- 🟣 **Marcador morado**: Posición de la firma

Las coordenadas se actualizan en tiempo real. También puedes ingresar valores numéricos manualmente.

### Flujo de Trabajo

```
1. Abrir configuración del grupo
2. Subir o seleccionar plantilla
3. Arrastrar marcadores a posiciones deseadas
4. Configurar fuente, tamaño y color
5. Habilitar/deshabilitar variables
6. Configurar firma (nombre, cargo, imagen)
7. Guardar configuración
```

### Valores Predeterminados

Si no configuras un grupo, usará estos valores:

| Campo | Valor |
|-------|-------|
| Fuente | Arial |
| Tamaño fuente | 48pt |
| Color texto | #000000 |
| Nombre (X, Y) | 400, 300 |
| QR (X, Y) | 920, 419 |
| Firma (X, Y) | 800, 850 |
| Tamaño QR | 200px |
| Tamaño firma | 150px |

---

## 📄 Configuración por Categoría

Cada categoría puede tener su propia configuración **independiente** del grupo.

### Modos de Operación

**Modo Herencia** (`usar_plantilla_propia = 0`):
- La categoría hereda toda la configuración del grupo
- No requiere configuración adicional

**Modo Personalizado** (`usar_plantilla_propia = 1`):
- Configuración completa e independiente
- Sobrescribe la configuración del grupo

### Activar Configuración Personalizada

1. Navegar a un grupo → entrar a una categoría
2. Clic en **⚙️ Configurar Plantilla**
3. Activar el toggle **"Usar configuración personalizada"**
4. Configurar todos los parámetros
5. Guardar

Para volver a heredar del grupo, simplemente desactivar el toggle y guardar.

### Casos de Uso

**Talleres con diferentes estilos:**
- Grupo: "Talleres 2025"
- Categoría "Pintura": Plantilla con fondo artístico
- Categoría "Música": Plantilla con notas musicales
- Cada una con configuración independiente

---

## 📐 Sistema de Coordenadas

```
(0,0) ────────────────→ X (ancho)
  │
  │    [Tu Plantilla]
  │
  ↓
  Y (alto)
```

- **X = 0**: Borde izquierdo
- **Y = 0**: Borde superior
- Los valores aumentan hacia la derecha (X) y hacia abajo (Y)

### Posiciones Recomendadas (A4 horizontal, 3508×2480px)

| Elemento | Posición | X | Y |
|----------|----------|---|---|
| Nombre centrado | Centro | 1754 | 1000 |
| QR inferior derecha | Esquina | 3300 | 2300 |
| QR inferior izquierda | Esquina | 200 | 2300 |
| QR superior derecha | Esquina | 3300 | 200 |

> **Nota:** El código QR mide 150×150px por defecto. El nombre se centra horizontalmente en la posición especificada.

---

## 🎨 Personalización de Texto

### Fuentes
- **Sistema**: Arial, Helvetica, Times New Roman, Georgia, Courier New, Verdana
- **Personalizadas**: Coloca archivos `.ttf` o `.otf` en `assets/fonts/`

### Tamaño de Fuente
- Rango: 10–200 píxeles (recomendado: 40–60 para A4)

### Colores Profesionales

| Estilo | Color |
|--------|-------|
| Elegante | `#1a1a1a` |
| Corporativo | `#003366` |
| Lujo | `#8B7355` |
| Oro envejecido | `#C19A6B` |
| Dorado | `#FFD700` |

---

## 💾 Archivos Generados

| Tipo | Ruta | Nombrado |
|------|------|----------|
| Plantillas de grupo | `assets/templates/` | `grupo_{id}_template.{ext}` |
| Firmas de grupo | `assets/firmas/` | `grupo_{id}_firma.{ext}` |
| Plantillas de categoría | `assets/templates/` | `categoria_{id}_template.{ext}` |
| Certificados | `uploads/` | PDF + PNG generados |

---

## ❓ Solución de Problemas

### La plantilla no se carga
- Verificar que el archivo sea PNG o JPG
- Tamaño máximo: 5MB
- Revisar permisos de `assets/templates/` (755)

### El nombre no aparece donde lo puse
- Verificar que X < ancho de la imagen e Y < alto
- Usar la vista previa para validar

### La fuente no cambia
- Verificar que el archivo `.ttf`/`.otf` esté en `assets/fonts/`
- Recargar la página de configuración

### El QR se corta
- Mover al menos 75px hacia dentro del borde (QR = 150×150px)

### Los elementos no aparecen en el certificado
- Verificar que las variables estén habilitadas (☑️)
- Revisar que las coordenadas estén dentro del lienzo
- Asegurar que se haya guardado la configuración

### El drag & drop no funciona
- Recargar la página (F5)
- Verificar que la imagen de plantilla se haya cargado
- Usar Chrome o Firefox

---

**¿Necesitas ayuda?** Contacta al administrador del sistema.
